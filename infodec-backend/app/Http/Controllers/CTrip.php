<?php

namespace App\Http\Controllers;

use App\Models\MCity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class CTrip extends Controller
{
    /**
     * POST /api/finalize   { "idCity": 1, "lang":"es" | "de", "log": true|false? }
     */
    public function finalize(Request $request)
    {
        try {
            $request->validate([
                'idCity' => 'required|integer',
                'lang'   => 'required|in:es,de',
                'log'    => 'nullable|boolean',
            ]);

            // 1) presupuesto
            $budget = Session::get('presupuesto');
            if (!$budget || $budget <= 0) {
                return response()->json([
                    'statusCode' => 422,
                    'error'      => 'No hay presupuesto en sesión. Guarda el presupuesto primero.'
                ], 422);
            }

            // 2) ciudad + país
            $city = MCity::with(['country:idCountry,conNameSpa,conNameGer'])
                ->select('idCity', 'citNameSpa', 'citNameGer', 'country_idCountry')
                ->find($request->idCity);

            if (!$city || !$city->country) {
                return response()->json([
                    'statusCode' => 404,
                    'error'      => 'Ciudad no encontrada'
                ], 404);
            }

            $lang        = $request->lang;
            $countryEs   = $city->country->conNameSpa;
            $countryDe   = $city->country->conNameGer;
            $cityEs      = $city->citNameSpa;
            $cityDe      = $city->citNameGer;
            $countryName = $lang === 'de' ? $countryDe : $countryEs;
            $cityName    = $lang === 'de' ? $cityDe    : $cityEs;

            // 3) ISO + moneda
            $map = [
                'Inglaterra' => ['iso2' => 'GB', 'cur' => 'GBP', 'sym' => '£',  'dec' => 2],
                'Japón'      => ['iso2' => 'JP', 'cur' => 'JPY', 'sym' => '¥',  'dec' => 0],
                'India'      => ['iso2' => 'IN', 'cur' => 'INR', 'sym' => '₹',  'dec' => 2],
                'Dinamarca'  => ['iso2' => 'DK', 'cur' => 'DKK', 'sym' => 'kr', 'dec' => 2],
            ];
            $m = $map[$countryEs] ?? null;
            if (!$m) {
                return response()->json(['statusCode' => 500, 'error' => 'País no mapeado para ISO/moneda'], 500);
            }

            // 4) Clima (localizado) + clave
            $weather   = $this->fetchWeather($cityEs, $m['iso2'], $lang);
            if (!empty($weather['error'])) {
                return response()->json(['statusCode' => 502, 'error' => $weather['message'] ?? 'No se pudo obtener el clima'], 502);
            }

            // versiones ES/DE (para historial) 
            $weatherEs = $lang === 'es' ? $weather : $this->fetchWeather($cityEs, $m['iso2'], 'es');
            $weatherDe = $lang === 'de' ? $weather : $this->fetchWeather($cityEs, $m['iso2'], 'de');

            // 5) FX
            $fx = $this->getFxRate($m['cur']);
            if (!empty($fx['error'])) {
                $code = $fx['code'] ?? 502;
                return response()->json(['statusCode' => $code, 'error' => $fx['message'] ?? 'No se pudo obtener la tasa de cambio'], $code);
            }
            $rate = (float)$fx['rate'];
            $conv = (float)$budget * $rate;
            $convertedFmt = $m['sym'] . ' ' . number_format($conv, $m['dec']);

            // 6) Historial
            if ($request->boolean('log', true)) {
                $entry = [
                    'country'          => $countryName,
                    'city'             => $cityName,
                    'lang'             => $lang,
                    'country_es'       => $countryEs,
                    'country_de'       => $countryDe,
                    'city_es'          => $cityEs,
                    'city_de'          => $cityDe,
                    'budget_cop'       => (float)$budget,
                    'currency'         => $m['cur'],
                    'symbol'           => $m['sym'],
                    'rate'             => $rate,
                    'converted'        => $conv,
                    'converted_fmt'    => $convertedFmt,
                    'temp_c'           => $weather['tempC'],
                    'weather_key'      => $weather['key'] ?? null,
                    'weather_desc'     => $weather['desc'],
                    'weather_desc_es'  => $weatherEs['desc'] ?? null,
                    'weather_desc_de'  => $weatherDe['desc'] ?? null,

                    'when'             => now()->toDateTimeString(),
                ];
                $hist = Session::get('history', []);
                array_unshift($hist, $entry);
                Session::put('history', array_slice($hist, 0, 5));
            }

            // 7) Resumen
            return response()->json([
                'statusCode'       => 200,
                'message'          => 'Resumen generado con éxito.',
                'country'          => $countryName,
                'city'             => $cityName,
                'budget_cop'       => (float)$budget,
                'currency_code'    => $m['cur'],
                'currency_symbol'  => $m['sym'],
                'rate'             => $rate,
                'converted'        => $conv,
                'converted_fmt'    => $convertedFmt,
                'weather_c'        => $weather['tempC'],
                'weather_desc'     => $weather['desc'],
                'weather_key'      => $weather['key'] ?? null,
                'country_es'       => $countryEs,
                'country_de'       => $countryDe,
                'city_es'          => $cityEs,
                'city_de'          => $cityDe,
                'weather_desc_es'  => $weatherEs['desc'] ?? null,
                'weather_desc_de'  => $weatherDe['desc'] ?? null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'statusCode' => 500,
                'error'      => 'Error en Pantalla 3.',
                'details'    => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/weather?city=London&country=GB&lang=es|de
     */
    public function weather(Request $request)
    {
        try {
            $request->validate([
                'city'    => 'required|string|min:2',
                'country' => 'required|string|size:2',
                'lang'    => 'nullable|in:es,de',
            ]);
            $lang = $request->lang ?? 'es';

            $result = $this->fetchWeather($request->city, strtoupper($request->country), $lang);
            if (!empty($result['error'])) {
                return response()->json([
                    'statusCode' => 502,
                    'error'      => $result['message'] ?? 'No se pudo obtener el clima'
                ], 502);
            }

            return response()->json([
                'statusCode' => 200,
                'message'    => 'Clima recuperado con éxito.',
                'weather'    => [
                    'city'  => $request->city,
                    'tempC' => $result['tempC'],
                    'desc'  => $result['desc'],
                    'key'   => $result['key'] ?? null,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'statusCode' => 500,
                'error'      => 'Error al consultar clima.',
                'details'    => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/fx?to=GBP&amount=1000000
     */
    public function fx(Request $request)
    {
        try {
            $request->validate([
                'to'     => 'required|string|in:GBP,JPY,INR,DKK',
                'amount' => 'required|numeric|min:1',
            ]);

            $fx = $this->getFxRate(strtoupper($request->to));
            if (!empty($fx['error'])) {
                $code = $fx['code'] ?? 502;
                return response()->json([
                    'statusCode' => $code,
                    'error'      => $fx['message'] ?? 'No se pudo obtener la tasa de cambio'
                ], $code);
            }

            $rate      = (float)$fx['rate'];
            $amount    = (float)$request->amount;
            $converted = $amount * $rate;

            return response()->json([
                'statusCode' => 200,
                'message'    => 'Conversión realizada con éxito.',
                'fx'         => [
                    'from'      => 'COP',
                    'to'        => strtoupper($request->to),
                    'amount'    => $amount,
                    'rate'      => $rate,
                    'converted' => $converted,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'statusCode' => 500,
                'error'      => 'Error al convertir moneda.',
                'details'    => $e->getMessage()
            ], 500);
        }
    }

    /** Genera clave **/
    private function descKey(?string $desc): ?string
    {
        if ($desc === null) return null;
        $s = mb_strtolower(trim($desc));
      
        $s = str_replace(['nearby', ' at times'], ['near', ''], $s);
        $s = preg_replace('/[^a-z0-9]+/', '_', $s);
        return trim($s, '_');
    }

    /** Traducción de data si wttr.in devuelve inglés para que manejemos es/de */

    private function translateWeatherDesc(?string $desc, string $lang = 'es'): ?string
    {
        if ($desc === null) return null;
        $key = mb_strtolower(trim($desc));
        $map = [
            'es' => [
                'partly cloudy'                 => 'Parcialmente nublado',
                'patchy rain nearby'            => 'Lluvia dispersa cercana',
                'patchy rain possible'          => 'Posible lluvia dispersa',
                'patchy light rain'             => 'Lluvia ligera dispersa',
                'light rain shower'             => 'Chubascos ligeros',
                'moderate or heavy rain shower' => 'Chubascos moderados o fuertes',
                'heavy rain shower'             => 'Chubascos fuertes',
                'light drizzle'                 => 'Llovizna',
                'light rain'                    => 'Lluvia ligera',
                'moderate rain'                 => 'Lluvia moderada',
                'heavy rain'                    => 'Lluvia fuerte',
                'heavy rain at times'           => 'Lluvia fuerte a ratos',
                'moderate rain at times'        => 'Lluvia moderada a ratos',
                'thundery outbreaks possible'   => 'Posibles tormentas eléctricas',
                'sunny'                         => 'Soleado',
                'clear'                         => 'Despejado',
                'cloudy'                        => 'Nublado',
                'overcast'                      => 'Cubierto',
                'mist'                          => 'Neblina',
                'fog'                           => 'Niebla',
            ],
            'de' => [
                'partly cloudy'                 => 'Teilweise bewölkt',
                'patchy rain nearby'            => 'Vereinzelt Regen in der Nähe',
                'patchy rain possible'          => 'Vereinzelt Regen möglich',
                'patchy light rain'             => 'Leichter Regen, örtlich',
                'light rain shower'             => 'Leichter Regenschauer',
                'moderate or heavy rain shower' => 'Mäßiger oder starker Regenschauer',
                'heavy rain shower'             => 'Starker Regenschauer',
                'light drizzle'                 => 'Leichter Nieselregen',
                'light rain'                    => 'Leichter Regen',
                'moderate rain'                 => 'Mäßiger Regen',
                'heavy rain'                    => 'Starker Regen',       
                'heavy rain at times'           => 'Zeitweise starker Regen',
                'moderate rain at times'        => 'Zeitweise mäßiger Regen',
                'thundery outbreaks possible'   => 'Gewitter möglich',
                'sunny'                         => 'Sonnig',
                'clear'                         => 'Klar',
                'cloudy'                        => 'Bewölkt',
                'overcast'                      => 'Bedeckt',
                'mist'                          => 'Dunst',
                'fog'                           => 'Nebel',
            ],
        ];
        return $map[$lang][$key] ?? $desc;
    }

    /**
     * Clima localizado + clave:
     *  - 1ª llamada: lang solicitado (para mostrar)
     *  - 2ª llamada: lang='en' (para construir weather_key estable)
     */
    private function fetchWeather(string $cityName, string $countryIso2, string $lang = 'es'): array
    {
        try {
            $q   = rawurlencode("{$cityName},{$countryIso2}");
            $url = "https://wttr.in/{$q}";


            $resLoc = Http::timeout(8)->get($url, [
                'format' => 'j1',
                'lang'   => $lang === 'de' ? 'de' : 'es',
            ]);
            if (!$resLoc->ok()) return ['error' => true, 'message' => 'Fallo al consultar clima'];

            $dataLoc = $resLoc->json();
            if (empty($dataLoc['current_condition'][0])) {
                return ['error' => true, 'message' => 'Respuesta inválida del proveedor'];
            }
            $currL   = $dataLoc['current_condition'][0];
            $tempC   = isset($currL['temp_C']) ? (float)$currL['temp_C'] : null;
            $descLoc = $currL['weatherDesc'][0]['value'] ?? null;
            $descLoc = $this->translateWeatherDesc($descLoc, $lang);

            $resEn = Http::timeout(8)->get($url, [
                'format' => 'j1',
                'lang'   => 'en',
            ]);
            $key = null;
            if ($resEn->ok()) {
                $dataEn = $resEn->json();
                if (!empty($dataEn['current_condition'][0])) {
                    $descEn = $dataEn['current_condition'][0]['weatherDesc'][0]['value'] ?? null;
                    $key    = $this->descKey($descEn);
                }
            }
            // Si falló la llamada en/en, como último recurso genera la key desde el texto localizado
            if ($key === null) {
                $key = $this->descKey($descLoc);
            }

            return ['error' => false, 'tempC' => $tempC, 'desc' => $descLoc, 'key' => $key];
        } catch (\Exception $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    private function getFxRate(string $to): array
    {
        try {
            $to = strtoupper($to);
            if (!in_array($to, ['GBP', 'JPY', 'INR', 'DKK'], true)) {
                return ['error' => true, 'code' => 422, 'message' => "Tasa no disponible para {$to}"];
            }

            $res = Http::timeout(8)->get('https://open.er-api.com/v6/latest/COP');
            if (!$res->ok()) {
                return ['error' => true, 'code' => 502, 'message' => 'Fallo al consultar tasas (ER-API)'];
            }

            $json = $res->json();
            if (($json['result'] ?? '') !== 'success' || empty($json['rates'])) {
                return ['error' => true, 'code' => 502, 'message' => 'Respuesta inválida del proveedor de tasas'];
            }

            if (!isset($json['rates'][$to])) {
                return ['error' => true, 'code' => 422, 'message' => "Tasa no disponible para {$to}"];
            }

            return ['error' => false, 'rate' => (float)$json['rates'][$to]];
        } catch (\Exception $e) {
            return ['error' => true, 'code' => 500, 'message' => $e->getMessage()];
        }
    }
}

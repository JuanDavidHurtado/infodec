<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;

class CHistory extends Controller
{
    /**
     * GET /api/history?lang=es|de
     */
    public function history(Request $request)
    {
        try {
            $lang = $request->query('lang', 'es');
            if (!in_array($lang, ['es','de'], true)) {
                $lang = 'es';
            }

            $hist = Session::get('history', []);
            $hist = array_slice($hist, 0, 5);

            $mapped = array_map(function ($h) use ($lang) {
                $country = $lang === 'de'
                    ? ($h['country_de'] ?? $h['country'] ?? null)
                    : ($h['country_es'] ?? $h['country'] ?? null);

                $city = $lang === 'de'
                    ? ($h['city_de'] ?? $h['city'] ?? null)
                    : ($h['city_es'] ?? $h['city'] ?? null);

                $weather = $lang === 'de'
                    ? ($h['weather_desc_de'] ?? $h['weather_desc'] ?? null)
                    : ($h['weather_desc_es'] ?? $h['weather_desc'] ?? null);

                $dec = ($h['currency'] ?? '') === 'JPY' ? 0 : 2;
                $convertedFmt = $h['converted_fmt']
                    ?? (($h['symbol'] ?? '').' '.number_format((float)($h['converted'] ?? 0), $dec));

                return [
                    'when'          => $h['when']          ?? null,
                    'country'       => $country,
                    'city'          => $city,
                    'budget_cop'    => (float)($h['budget_cop'] ?? 0),
                    'converted'     => (float)($h['converted'] ?? 0),
                    'converted_fmt' => $convertedFmt,
                    'currency'      => $h['currency']      ?? null,
                    'symbol'        => $h['symbol']        ?? null,
                    'rate'          => (float)($h['rate']  ?? 0),
                    'temp_c'        => $h['temp_c']        ?? null,
                    'weather_desc'  => $weather,
                ];
            }, $hist);

            return response()->json([
                'statusCode' => 200,
                'message'    => 'Historial recuperado con éxito.',
                'history'    => $mapped,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'statusCode' => 500,
                'error'      => 'Error al recuperar historial.',
                'details'    => $e->getMessage()
            ], 500);
        }
    }
}

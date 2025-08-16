<?php

namespace App\Http\Controllers;

use App\Models\MCountry;
use Illuminate\Http\Request;

class CCountry extends Controller
{
    /**
     * Obtener todos los países con sus ciudades (ES/DE).
     */
    public function list()
    {
        try {
            $countries = MCountry::with([
                'cities:idCity,citNameSpa,citNameGer,country_idCountry'
            ])->get(['idCountry', 'conNameSpa', 'conNameGer']);

            $countryList = $countries->map(function ($country) {
                return [
                    'idCountry'   => $country->idCountry,
                    'nameSpa'     => $country->conNameSpa,
                    'nameGer'     => $country->conNameGer,
                    'cities'      => $country->cities->map(function ($city) {
                        return [
                            'idCity'      => $city->idCity,
                            'nameSpa'     => $city->citNameSpa,
                            'nameGer'     => $city->citNameGer,
                        ];
                    })->values(),
                ];
            })->values();

            return response()->json([
                'statusCode' => 200,
                'message'    => 'Países con ciudades recuperados con éxito.',
                'countries'  => $countryList
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'statusCode' => 500,
                'error'      => 'Error al recuperar países con ciudades.',
                'details'    => $e->getMessage()
            ], 500);
        }
    }
}

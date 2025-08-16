<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CBudget extends Controller
{
    /**
     * Guardar el presupuesto (COP) en sesión.
     */
    public function save(Request $request)
    {
        try {
            $request->validate([
                'presupuesto' => 'required|numeric|min:1',
            ]);

            Session::put('presupuesto', (float)$request->presupuesto);

            return response()->json([
                'statusCode'   => 200,
                'message'      => 'Presupuesto guardado correctamente.',
                'presupuesto'  => Session::get('presupuesto'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'statusCode' => 500,
                'error'      => 'Ocurrió un error al guardar el presupuesto.',
                'details'    => $e->getMessage()
            ], 500);
        }
    }
}

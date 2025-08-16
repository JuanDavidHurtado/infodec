<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MCountry extends Model
{
    // Tabla
    protected $table = 'country';

    // Primary key personalizada
    protected $primaryKey = 'idCountry';

    // No timestamps
    public $timestamps = false;

    // Campos asignables
    protected $fillable = [
        'conNameSpa',
        'conNameGer',
        'conCurrency',
        'conSymbol'
    ];

    /**
     * Relación con City
     */
    public function cities()
    {
        return $this->hasMany(MCity::class, 'country_idCountry', 'idCountry');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MCity extends Model
{
    protected $table = 'city';
    protected $primaryKey = 'idCity';
    public $timestamps = false;

    protected $fillable = [
        'citNameSpa',
        'citNameGer',
        'country_idCountry'
    ];

    public function country()
    {
        return $this->belongsTo(MCountry::class, 'country_idCountry', 'idCountry');
    }
}

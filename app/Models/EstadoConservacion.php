<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoConservacion extends Model
{
    protected $table = 'estados_conservacion';

    public $timestamps = false;

    protected $fillable = ['nombre', 'orden'];

    public static function ordenados()
    {
        return static::orderBy('orden')->get();
    }
}

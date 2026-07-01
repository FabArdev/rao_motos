<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Moto extends Model
{
    protected $table = 'moto';

    protected $fillable = ['cliente_id', 'placa', 'marca', 'modelo', 'anio'];

    protected $casts = ['anio' => 'integer'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function ordenesTrabajo()
    {
        return $this->hasMany(OrdenTrabajo::class, 'moto_id');
    }
}

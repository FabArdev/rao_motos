<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
    protected $table = 'bitacora';

    public $timestamps = false;

    protected $fillable = ['usuario_id', 'correo', 'accion', 'recurso', 'ip', 'agente_usuario', 'fecha'];

    protected $casts = ['fecha' => 'datetime'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}

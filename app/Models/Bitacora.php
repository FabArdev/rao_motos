<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
    protected $table = 'bitacora';

    public $timestamps = false;

    protected $fillable = ['usuario_id', 'email', 'accion', 'recurso', 'ip', 'user_agent', 'fecha'];

    protected $casts = ['fecha' => 'datetime'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}

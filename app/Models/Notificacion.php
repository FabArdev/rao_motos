<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificacion';

    public $timestamps = false;

    protected $fillable = ['usuario_id', 'tipo', 'mensaje', 'recurso', 'leido', 'fecha'];

    protected $casts = [
        'leido' => 'boolean',
        'fecha' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasProfilePhoto, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'nombre',
        'apellidos',
        'ci',
        'telefono',
        'direccion',
        'email',
        'password',
        'role_id',
        'estado',
        'fecha_nacimiento',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = [
        'name',
        'profile_photo_url',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'estado' => 'boolean',
        'fecha_nacimiento' => 'date',
    ];

    /* ---------------------------------------------------------------------
     | Relaciones
     * ------------------------------------------------------------------- */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /** Subtabla 1:1 (solo si el usuario es cliente). */
    public function cliente()
    {
        return $this->hasOne(Cliente::class, 'id');
    }

    /** Ventas en las que este usuario fue el vendedor. */
    public function ventasComoVendedor()
    {
        return $this->hasMany(Venta::class, 'vendedor_id');
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'usuario_id');
    }

    /* ---------------------------------------------------------------------
     | Helpers de rol  (RAO MOTOS: admin | vendedor | almacenero | cliente)
     * ------------------------------------------------------------------- */
    public function tieneRol($rol): bool
    {
        return $this->role && strcasecmp($this->role->nombre, $rol) === 0;
    }

    public function esAdmin(): bool
    {
        return $this->tieneRol('admin');
    }

    public function esVendedor(): bool
    {
        return $this->tieneRol('vendedor');
    }

    public function esAlmacenero(): bool
    {
        return $this->tieneRol('almacenero');
    }

    public function esCliente(): bool
    {
        return $this->tieneRol('cliente');
    }

    /* ---------------------------------------------------------------------
     | Mutators / Accessors
     * ------------------------------------------------------------------- */
    protected function nombre(): Attribute
    {
        return Attribute::make(set: fn ($value) => ucwords(strtolower($value)));
    }

    protected function apellidos(): Attribute
    {
        return Attribute::make(set: fn ($value) => ucwords(strtolower($value)));
    }

    protected function email(): Attribute
    {
        return Attribute::make(set: fn ($value) => $value ? strtolower($value) : $value);
    }

    protected function name(): Attribute
    {
        return Attribute::make(get: fn () => trim($this->nombre.' '.$this->apellidos));
    }
}

<?php

/**
 * ─────────────────────────────────────────────────────────────
 *  Usuario — Persona que usa el sistema (y login)
 * ─────────────────────────────────────────────────────────────
 *  EXPLICACIÓN
 *  Representa a cualquier persona con cuenta: administrador,
 *  vendedor, almacenero o cliente. Guarda sus datos personales y
 *  es con lo que se inicia sesión (por correo). Sabe qué rol tiene
 *  y ofrece atajos como esAdmin() o esCliente().
 *
 *  IMPLEMENTACIÓN
 *  - Tabla: usuario. Extiende Authenticatable (no ModeloBase)
 *    porque Fortify lo exige; repite CREATED_AT/UPDATED_AT en español.
 *  - Traits: HasApiTokens (Sanctum), HasFactory, HasProfilePhoto
 *    (Jetstream), Notifiable, TwoFactorAuthenticatable (Fortify).
 *  - Login por 'correo'; token "recuérdame" = token_recordar.
 *  - Relaciones: rol(), cliente() (1:1), ventasComoVendedor(),
 *    notificaciones().
 *  - Helpers de rol: tieneRol(), esAdmin(), esVendedor(),
 *    esAlmacenero(), esCliente().
 *  - Accessors/mutators: nombre/apellidos con ucwords, correo en
 *    minúsculas, nombre_completo (get), defaultProfilePhotoUrl()
 *    (avatar de iniciales, sobrescribe el de Jetstream).
 * ─────────────────────────────────────────────────────────────
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, HasProfilePhoto, Notifiable, TwoFactorAuthenticatable;

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    protected $table = 'usuario';

    protected $rememberTokenName = 'token_recordar';

    protected $fillable = [
        'nombre',
        'apellidos',
        'ci',
        'telefono',
        'direccion',
        'correo',
        'password',
        'rol_id',
        'estado',
        'fecha_nacimiento',
    ];

    protected $hidden = [
        'password',
        'token_recordar',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = [
        'nombre_completo',
        'profile_photo_url',
    ];

    protected $casts = [
        'correo_verificado_en' => 'datetime',
        'estado' => 'boolean',
        'fecha_nacimiento' => 'date',
    ];

    public function rol()
    {
        return $this->belongsTo(Rol::class);
    }

    public function cliente()
    {
        return $this->hasOne(Cliente::class, 'id');
    }

    public function ventasComoVendedor()
    {
        return $this->hasMany(Venta::class, 'vendedor_id');
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'usuario_id');
    }

    public function tieneRol($rol): bool
    {
        return $this->rol && strcasecmp($this->rol->nombre, $rol) === 0;
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

    protected function nombre(): Attribute
    {
        return Attribute::make(set: fn ($value) => ucwords(strtolower($value)));
    }

    protected function apellidos(): Attribute
    {
        return Attribute::make(set: fn ($value) => ucwords(strtolower($value)));
    }

    protected function correo(): Attribute
    {
        return Attribute::make(set: fn ($value) => $value ? strtolower($value) : $value);
    }

    protected function nombreCompleto(): Attribute
    {
        return Attribute::make(get: fn () => trim($this->nombre.' '.$this->apellidos));
    }

    protected function defaultProfilePhotoUrl(): string
    {
        $iniciales = trim(collect(explode(' ', $this->nombre_completo))
            ->map(fn ($parte) => mb_substr($parte, 0, 1))
            ->join(' '));

        return 'https://ui-avatars.com/api/?name='.urlencode($iniciales).'&color=7F9CF5&background=EBF4FF';
    }
}

<?php

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

    // Usuario no puede extender ModeloBase (Fortify exige Authenticatable),
    // así que repite aquí las marcas de tiempo en español del resto del dominio.
    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    protected $table = 'usuario';

    /** La columna de "recuérdame" también va en español. */
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

    /* ---------------------------------------------------------------------
     | Relaciones
     * ------------------------------------------------------------------- */
    public function rol()
    {
        return $this->belongsTo(Rol::class);
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

    protected function correo(): Attribute
    {
        return Attribute::make(set: fn ($value) => $value ? strtolower($value) : $value);
    }

    protected function nombreCompleto(): Attribute
    {
        return Attribute::make(get: fn () => trim($this->nombre.' '.$this->apellidos));
    }

    /**
     * Avatar por defecto (iniciales) cuando el usuario no subió foto.
     *
     * Se sobrescribe el de Jetstream porque el original lee $this->name,
     * atributo que aquí se llama nombre_completo.
     */
    protected function defaultProfilePhotoUrl(): string
    {
        $iniciales = trim(collect(explode(' ', $this->nombre_completo))
            ->map(fn ($parte) => mb_substr($parte, 0, 1))
            ->join(' '));

        return 'https://ui-avatars.com/api/?name='.urlencode($iniciales).'&color=7F9CF5&background=EBF4FF';
    }
}

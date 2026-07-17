<?php

namespace Database\Factories;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    /**
     * Estado por defecto del modelo, acorde al esquema de RAO MOTOS.
     */
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->firstName(),
            'apellidos' => $this->faker->lastName().' '.$this->faker->lastName(),
            'ci' => (string) $this->faker->unique()->numberBetween(1000000, 9999999),
            'telefono' => (string) $this->faker->numberBetween(70000000, 79999999),
            'direccion' => $this->faker->streetAddress(),
            'correo' => $this->faker->unique()->safeEmail(),
            'correo_verificado_en' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'token_recordar' => Str::random(10),
            'estado' => true,
            'fecha_nacimiento' => $this->faker->dateTimeBetween('-60 years', '-18 years'),
            'rol_id' => Rol::where('nombre', 'cliente')->value('id'),
        ];
    }

    /** Usuario con el correo sin verificar. */
    public function sinVerificar(): static
    {
        return $this->state(fn (array $atributos) => [
            'correo_verificado_en' => null,
        ]);
    }

    /** Usuario con un rol concreto (admin, vendedor, almacenero, cliente). */
    public function conRol(string $nombreRol): static
    {
        return $this->state(fn (array $atributos) => [
            'rol_id' => Rol::where('nombre', $nombreRol)->value('id'),
        ]);
    }
}

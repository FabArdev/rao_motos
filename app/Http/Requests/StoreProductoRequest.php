<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // protegido por middleware rol:admin,almacenero
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:50', 'unique:producto,codigo'],
            'nombre' => ['required', 'string', 'max:200'],
            'marca' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'precio_venta_base' => ['required', 'numeric', 'gt:0'],
            'precio_mayorista' => ['required', 'numeric', 'gt:0'],
            'cantidad_minima_mayorista' => ['required', 'integer', 'min:1'],
            'stock_minimo' => ['nullable', 'integer', 'min:0'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'imagenes' => ['nullable', 'array', 'max:6'],
            'imagenes.*' => ['image', 'max:2048'],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.unique' => 'Ya existe un producto con ese código.',
            'nombre.required' => 'El nombre es obligatorio.',
            'precio_venta_base.required' => 'El precio minorista es obligatorio.',
            'precio_venta_base.gt' => 'El precio minorista debe ser mayor a 0.',
            'precio_mayorista.required' => 'El precio mayorista es obligatorio.',
            'precio_mayorista.gt' => 'El precio mayorista debe ser mayor a 0.',
            'cantidad_minima_mayorista.required' => 'La cantidad mínima para mayoreo es obligatoria.',
            'cantidad_minima_mayorista.min' => 'La cantidad mínima para mayoreo debe ser al menos 1.',
            'foto.image' => 'El archivo debe ser una imagen.',
            'foto.max' => 'La imagen no debe superar los 2MB.',
            'imagenes.max' => 'Máximo 6 imágenes adicionales.',
            'imagenes.*.image' => 'Cada archivo debe ser una imagen.',
            'imagenes.*.max' => 'Cada imagen no debe superar los 2MB.',
        ];
    }
}

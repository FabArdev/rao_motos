<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proveedor_id' => ['required', 'exists:proveedor,id'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:producto,id'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1'],
            'detalles.*.precio_unitario' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'proveedor_id.required' => 'Debe seleccionar un proveedor.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'detalles.required' => 'La compra debe tener al menos un producto.',
            'detalles.min' => 'La compra debe tener al menos un producto.',
            'detalles.*.producto_id.required' => 'Seleccione un producto en cada línea.',
            'detalles.*.producto_id.exists' => 'Uno de los productos no existe.',
            'detalles.*.cantidad.required' => 'Indique la cantidad.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser al menos 1.',
            'detalles.*.precio_unitario.required' => 'Indique el precio unitario.',
            'detalles.*.precio_unitario.min' => 'El precio unitario debe ser mayor a 0.',
        ];
    }
}

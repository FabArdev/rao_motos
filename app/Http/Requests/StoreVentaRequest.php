<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'exists:cliente,id'],
            'tipo_venta' => ['required', 'in:CONTADO,CREDITO'],
            'metodo_pago' => ['required', 'in:EFECTIVO,QR'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:producto,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'numero_cuotas' => ['required_if:tipo_venta,CREDITO', 'nullable', 'integer', 'min:2'],
            'tasa_interes' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' => 'Debe seleccionar un cliente.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'tipo_venta.required' => 'Indique si la venta es al contado o a crédito.',
            'metodo_pago.required' => 'Indique el método de pago.',
            'items.required' => 'La venta debe tener al menos un producto.',
            'items.min' => 'La venta debe tener al menos un producto.',
            'items.*.producto_id.required' => 'Seleccione un producto en cada línea.',
            'items.*.cantidad.min' => 'La cantidad debe ser al menos 1.',
            'numero_cuotas.required_if' => 'Una venta a crédito requiere el número de cuotas.',
            'numero_cuotas.min' => 'El crédito debe tener al menos 2 cuotas.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSimpatizanteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Simpatizante::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'campana_id' => 'required|uuid|exists:campanas,id',
            'territorio_id' => 'required|uuid|exists:territorios,id',
            'nombres' => 'required|string|max:100|regex:/^[a-záéíóúñ\s]+$/i',
            'apellidos' => 'required|string|max:100|regex:/^[a-záéíóúñ\s]+$/i',
            'cedula' => 'required|string|max:20|regex:/^[0-9]+$/',
            'telefono' => 'nullable|string|max:20|regex:/^[0-9\+\-\s\(\)]+$/',
            'correo' => 'nullable|email|max:100',
            'direccion' => 'nullable|string|max:255',
            'puesto_id' => 'nullable|uuid|exists:puestos_votacion,id',
            'mesa' => 'nullable|integer|min:1|max:999',
            'lider_id' => 'nullable|uuid|exists:users,id',
            'candidato_ids' => 'nullable|array|min:1',
            'candidato_ids.*' => 'uuid|exists:candidatos,id',
            'observaciones' => 'nullable|string|max:500',
            'lote_id' => 'nullable|uuid|exists:lotes,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombres.regex' => 'El campo nombres solo puede contener letras y espacios',
            'apellidos.regex' => 'El campo apellidos solo puede contener letras y espacios',
            'cedula.regex' => 'El campo cédula solo puede contener números',
            'telefono.regex' => 'El formato del teléfono no es válido',
            'candidato_ids.min' => 'Debe seleccionar al menos un candidato',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'campana_id' => 'campaña',
            'territorio_id' => 'territorio',
            'puesto_id' => 'puesto de votación',
            'lider_id' => 'líder',
            'candidato_ids' => 'candidatos',
            'lote_id' => 'lote',
        ];
    }
}

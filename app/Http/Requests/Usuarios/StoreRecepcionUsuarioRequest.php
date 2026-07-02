<?php

namespace App\Http\Requests\Usuarios;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use App\Models\Servicios\Cliente;
use App\Support\RolCliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRecepcionUsuarioRequest extends FormRequest
{
    use AutorizaPermiso;

    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::CREAR_CLIENTES);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('role_id') && (int) $this->input('role_id') !== RolCliente::id()) {
                $validator->errors()->add(
                    'role_id',
                    'En recepción solo se puede crear un usuario con rol cliente.'
                );
            }
        });
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:usuarios,email',
            'cliente_id' => [
                'required',
                'exists:clientes,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (Cliente::query()->whereKey($value)->whereNotNull('usuario_id')->exists()) {
                        $fail('Este cliente ya tiene una cuenta de acceso.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'El nombre es obligatorio.',
            'last_name.required' => 'El apellido es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'Este correo ya está registrado.',
            'cliente_id.required' => 'Debe seleccionar el cliente.',
            'cliente_id.exists' => 'El cliente seleccionado no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'nombre',
            'last_name' => 'apellido',
            'email' => 'correo electrónico',
            'cliente_id' => 'cliente',
        ];
    }
}

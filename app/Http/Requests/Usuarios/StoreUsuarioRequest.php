<?php

namespace App\Http\Requests\Usuarios;

use App\Enums\PermisoEnum;
use App\Enums\RolEnum;
use App\Models\Usuarios\Rol;
use App\Support\PermisoBd;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (
            $user->esRolConAccesoTotal()
            || $user->tienePermisoBd(config('permisos-bd.admin'))
            || $user->tienePermisoBd(PermisoBd::resolver(PermisoEnum::CREAR_USUARIOS->value))
        );
    }

    protected function prepareForValidation(): void
    {
        $role = Rol::find($this->input('role_id'));
        $nombreRol = $role?->nombre;

        if ($nombreRol !== RolEnum::CLIENTE->value) {
            $this->merge(['cliente_id' => null]);
        }

        if ($nombreRol !== RolEnum::VETERINARIO->value) {
            $this->merge(['veterinario_id' => null]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $role = Rol::find($this->input('role_id'));
            if (! $role) {
                return;
            }

            $actor = $this->user();
            $rolesElevados = [
                RolEnum::PROPIETARIO->value,
                RolEnum::ADMINISTRADOR->value,
            ];

            if (in_array($role->nombre, $rolesElevados, true) && ! $actor?->esRolConAccesoTotal()) {
                $validator->errors()->add(
                    'role_id',
                    'Solo propietario o administrador pueden crear usuarios con ese rol.'
                );
            }
        });
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => [
                'required',
                'email',
                Rule::unique('usuarios', 'email')->ignore($this->input('reactivar_usuario_id')),
            ],
            'role_id' => 'required|exists:roles,id',
            'cliente_id' => 'nullable|exists:clientes,id',
            'veterinario_id' => 'nullable|exists:veterinarios,id',
            'reactivar_usuario_id' => 'nullable|exists:usuarios,id',
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'El nombre es obligatorio.',
            'last_name.required' => 'El apellido es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'role_id.required' => 'Debe seleccionar un rol.',
            'role_id.exists' => 'El rol seleccionado no es válido.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'nombre',
            'last_name' => 'apellido',
            'email' => 'correo electrónico',
            'role_id' => 'rol',
            'password' => 'contraseña',
        ];
    }
}

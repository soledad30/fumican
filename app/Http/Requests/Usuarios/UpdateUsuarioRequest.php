<?php

namespace App\Http\Requests\Usuarios;

use App\Enums\PermisoEnum;
use App\Enums\RolEnum;
use App\Models\Usuarios\Rol;
use App\Support\PermisoBd;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (
            $user->esRolConAccesoTotal()
            || $user->tienePermisoBd(config('permisos-bd.admin'))
            || $user->tienePermisoBd(PermisoBd::resolver(PermisoEnum::EDITAR_USUARIOS->value))
        );
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
                    'Solo propietario o administrador pueden asignar ese rol.'
                );
            }
        });
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:usuarios,email,'.$userId,
            'role_id' => 'required|exists:roles,id',
            'esta_activo' => 'sometimes|boolean',
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'El nombre es obligatorio.',
            'last_name.required' => 'El apellido es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'Este correo ya está en uso por otro usuario.',
            'role_id.required' => 'Debe seleccionar un rol.',
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
        ];
    }
}

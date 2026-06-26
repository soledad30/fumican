<?php

namespace App\Http\Requests\Usuarios;

use App\Enums\PermisoEnum;
use App\Enums\RolEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use App\Models\Servicios\Cliente;
use App\Models\Usuarios\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRecepcionUsuarioRequest extends FormRequest
{
    use AutorizaPermiso;

    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::CREAR_CLIENTES);
    }

    protected function prepareForValidation(): void
    {
        $rolCliente = Rol::where('nombre', RolEnum::CLIENTE->value)->first();

        $this->merge([
            'role_id' => $rolCliente?->id,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $rolCliente = Rol::where('nombre', RolEnum::CLIENTE->value)->first();
            if ($rolCliente && (int) $this->input('role_id') !== (int) $rolCliente->id) {
                $validator->errors()->add('role_id', 'En recepción solo se puede crear un usuario con rol cliente.');
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
            'role_id' => [
                'required',
                Rule::exists('roles', 'id')->where(fn ($q) => $q->where('nombre', RolEnum::CLIENTE->value)),
            ],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if (is_array($data) && isset($data['role_id'])) {
            $data['rol_id'] = $data['role_id'];
            unset($data['role_id']);
        }

        return $data;
    }

    public function messages(): array
    {
        return [
            'cliente_id.unique' => 'Este cliente ya tiene una cuenta de acceso.',
        ];
    }
}

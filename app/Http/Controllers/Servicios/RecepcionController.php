<?php

namespace App\Http\Controllers\Servicios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Servicios\RecepcionStoreMascotaRequest;
use App\Http\Requests\Servicios\RecepcionUpdateMascotaRequest;
use App\Http\Requests\Servicios\StoreClienteRequest;
use App\Http\Requests\Servicios\UpdateClienteRequest;
use App\Http\Requests\Usuarios\StoreRecepcionUsuarioRequest;
use App\Models\Servicios\Cliente;
use App\Models\Servicios\Mascota;
use App\Support\RolCliente;
use App\Services\Servicios\ClienteService;
use App\Services\Servicios\EspecieService;
use App\Services\Servicios\MascotaService;
use App\Services\Servicios\RazaService;
use App\Services\Usuarios\UsuarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class RecepcionController extends Controller
{
    public function __construct(
        protected EspecieService $especieService,
        protected RazaService $razaService,
        protected MascotaService $mascotaService,
        protected UsuarioService $usuarioService,
        protected ClienteService $clienteService
    ) {}

    public function index(): InertiaResponse
    {
        return Inertia::render('Servicios/Recepcion/Index', [
            'especies' => $this->especieService->listAll(),
            'clientesSinUsuario' => $this->clienteService->sinUsuario(),
            'rolClienteId' => RolCliente::id(),
        ]);
    }

    public function clientes(Request $request): JsonResponse
    {
        return response()->json(
            $this->clienteService->autocompleteSearch($request->input('search', ''))
        );
    }

    public function clientesSinUsuario(): JsonResponse
    {
        return response()->json($this->clienteService->sinUsuario());
    }

    public function showCliente(Cliente $cliente): JsonResponse
    {
        $cliente->load(['mascotas.raza.especie', 'usuario']);

        return response()->json($cliente);
    }

    public function mascotasCliente(Cliente $cliente): JsonResponse
    {
        $mascotas = $cliente->mascotas()
            ->with('raza.especie')
            ->orderBy('nombre')
            ->get();

        return response()->json($mascotas);
    }

    public function razas(Request $request): JsonResponse
    {
        return response()->json($this->razaService->search());
    }

    public function prepararDatos(Request $request): JsonResponse
    {
        try {
            $specie = $this->especieService->findOrCreate($request->specie);
            $breed = $this->razaService->findOrCreate($request->breed, $specie->id);

            return response()->json(['specie_id' => $specie->id, 'breed_id' => $breed->id]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function storeCliente(StoreClienteRequest $request): JsonResponse
    {
        $customer = $this->clienteService->createCustomer($request->validated());

        return response()->json([
            'message' => 'Cliente registrado correctamente.',
            'customer' => $customer,
        ], 201);
    }

    public function updateCliente(UpdateClienteRequest $request, Cliente $cliente): JsonResponse
    {
        $this->clienteService->update($request->validated(), $cliente->id);

        return response()->json([
            'message' => 'Cliente actualizado correctamente.',
            'customer' => $cliente->fresh(),
        ]);
    }

    public function storeMascota(RecepcionStoreMascotaRequest $request): JsonResponse
    {
        $pet = $this->mascotaService->create($request->validated(), $request->file('photo'));

        return response()->json(['message' => 'Mascota registrada correctamente.', 'pet' => $pet], 201);
    }

    public function updateMascota(RecepcionUpdateMascotaRequest $request, Mascota $mascota): JsonResponse
    {
        $pet = $this->mascotaService->update($mascota->id, $request->validated(), $request->file('photo'));

        return response()->json(['message' => 'Mascota actualizada correctamente.', 'pet' => $pet]);
    }

    public function storeUsuario(StoreRecepcionUsuarioRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['rol_id'] = RolCliente::id();
        $user = $this->usuarioService->create($data);

        if (! empty($data['email']) && ! empty($data['cliente_id'])) {
            Cliente::query()
                ->whereKey($data['cliente_id'])
                ->update(['email' => $data['email']]);
        }

        return response()->json([
            'message' => 'Usuario cliente creado correctamente. Ya puede ingresar al portal.',
            'usuario' => $user->load('rol:id,nombre'),
            'password_generada' => $user->getAttribute('password_generada'),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Servicios\Cliente;
use App\Models\Servicios\ConsultaMedica;
use App\Models\Servicios\Mascota;
use App\Models\Servicios\Especie;
use App\Models\Servicios\Servicio;
use App\Models\Usuario;
use App\Models\Ventas\Categoria;
use App\Models\Ventas\NotaCompra;
use App\Models\Ventas\NotaVenta;
use App\Models\Ventas\Producto;
use App\Models\Ventas\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GlobalSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $term = $request->input('term', '');

        if (strlen($term) < 2) {
            return response()->json([]);
        }

        $results = collect();

        $this->findClientes($term, $results);
        $this->findMascotas($term, $results);
        $this->findConsultasMedicas($term, $results);
        $this->findProductosYCategorias($term, $results);
        $this->findServicios($term, $results);
        $this->findEspecies($term, $results);
        $this->findProveedores($term, $results);

        if (is_numeric($term)) {
            $this->findNotasCompra($term, $results);
            $this->findNotasVenta($term, $results);
        }

        $this->findUsuarios($term, $results);

        return response()->json($results->sortBy('title')->values()->take(10));
    }

    private function findClientes(string $term, Collection &$results): void
    {
        $clientes = Cliente::where('nombre', 'LIKE', "%{$term}%")
            ->orWhere('apellido', 'LIKE', "%{$term}%")
            ->orWhere('ci', 'LIKE', "%{$term}%")
            ->limit(5)->get();

        $results->push(...$clientes->map(fn ($cliente) => [
            'type' => 'Cliente',
            'title' => trim($cliente->nombre.' '.$cliente->apellido),
            'description' => 'CI: '.$cliente->ci,
            'url' => route('clientes.search', ['search_term' => $cliente->nombre]),
        ]));
    }

    private function findMascotas(string $term, Collection &$results): void
    {
        $mascotas = Mascota::where('nombre', 'LIKE', "%{$term}%")
            ->with('propietario:id,nombre,apellido')
            ->limit(5)->get();

        $results->push(...$mascotas->map(fn ($mascota) => [
            'type' => 'Mascota',
            'title' => $mascota->nombre,
            'description' => 'Propietario: '.trim(($mascota->propietario?->nombre ?? '').' '.($mascota->propietario?->apellido ?? 'N/A')),
            'url' => route('consultas-medicas.search', ['search_term' => $mascota->nombre]),
        ]));
    }

    private function findConsultasMedicas(string $term, Collection &$results): void
    {
        $query = ConsultaMedica::query();
        if (is_numeric($term)) {
            $query->where('id', $term);
        } else {
            $query->where('motivo', 'LIKE', "%{$term}%");
        }
        $consultas = $query->with('mascota:id,nombre')->limit(5)->get();

        $results->push(...$consultas->map(fn ($consulta) => [
            'type' => 'Consulta Médica',
            'title' => 'Consulta #'.$consulta->id.' para '.($consulta->mascota?->nombre ?? 'N/A'),
            'description' => 'Motivo: '.substr($consulta->motivo ?? '', 0, 50).'...',
            'url' => route('consultas-medicas.search', ['search_term' => $consulta->id]),
        ]));
    }

    private function findProductosYCategorias(string $term, Collection &$results): void
    {
        $productos = Producto::where('nombre', 'LIKE', "%{$term}%")
            ->orWhere('fabricante', 'LIKE', "%{$term}%")
            ->with('categoria:id,nombre')
            ->limit(5)->get();

        $results->push(...$productos->map(fn ($producto) => [
            'type' => 'Producto',
            'title' => $producto->nombre,
            'description' => 'Categoría: '.($producto->categoria?->nombre ?? 'N/A'),
            'url' => route('productos.search', ['name' => $producto->nombre]),
        ]));

        $categorias = Categoria::where('nombre', 'LIKE', "%{$term}%")->limit(3)->get();
        $results->push(...$categorias->map(fn ($categoria) => [
            'type' => 'Categoría',
            'title' => 'Categoría: '.$categoria->nombre,
            'description' => 'Buscar productos de esta categoría',
            'url' => route('productos.search', ['category_id' => $categoria->id]),
        ]));
    }

    private function findServicios(string $term, Collection &$results): void
    {
        $servicios = Servicio::where('esta_activo', true)
            ->where(function ($q) use ($term) {
                $q->where('nombre', 'LIKE', "%{$term}%")
                    ->orWhere('descripcion', 'LIKE', "%{$term}%");
            })
            ->limit(5)
            ->get();

        $results->push(...$servicios->map(fn ($servicio) => [
            'type' => 'Servicio',
            'title' => $servicio->nombre,
            'description' => 'Bs. '.number_format((float) $servicio->precio, 2),
            'url' => route('servicios.search', ['search_term' => $servicio->nombre]),
        ]));
    }

    private function findEspecies(string $term, Collection &$results): void
    {
        $especies = Especie::where('nombre', 'LIKE', "%{$term}%")
            ->limit(5)
            ->get();

        $results->push(...$especies->map(fn ($especie) => [
            'type' => 'Especie',
            'title' => $especie->nombre,
            'description' => 'Catálogo de especies',
            'url' => route('especies.index'),
        ]));
    }

    private function findProveedores(string $term, Collection &$results): void
    {
        $proveedores = Proveedor::where('nombre', 'LIKE', "%{$term}%")
            ->orWhere('email', 'LIKE', "%{$term}%")
            ->limit(5)->get();

        $results->push(...$proveedores->map(fn ($proveedor) => [
            'type' => 'Proveedor',
            'title' => $proveedor->nombre,
            'description' => 'Email: '.($proveedor->email ?? 'N/A'),
            'url' => route('proveedores.search', ['search_term' => $proveedor->nombre]),
        ]));
    }

    private function findNotasCompra(string $term, Collection &$results): void
    {
        $notas = NotaCompra::where('id', $term)->with('proveedor:id,nombre')->limit(1)->get();

        $results->push(...$notas->map(fn ($nota) => [
            'type' => 'Nota de Compra',
            'title' => 'Compra #'.$nota->id,
            'description' => 'Proveedor: '.($nota->proveedor?->nombre ?? 'N/A'),
            'url' => route('notas-compra.index'),
        ]));
    }

    private function findNotasVenta(string $term, Collection &$results): void
    {
        $notas = NotaVenta::where('id', $term)->with('cliente:id,nombre,apellido')->limit(1)->get();

        $results->push(...$notas->map(fn ($nota) => [
            'type' => 'Nota de Venta',
            'title' => 'Venta #'.$nota->id,
            'description' => 'Cliente: '.trim(($nota->cliente?->nombre ?? 'N/A').' '.($nota->cliente?->apellido ?? '')),
            'url' => route('notas-venta.index'),
        ]));
    }

    private function findUsuarios(string $term, Collection &$results): void
    {
        $usuarios = Usuario::where('nombre', 'LIKE', "%{$term}%")
            ->orWhere('email', 'LIKE', "%{$term}%")
            ->limit(5)->get();

        $results->push(...$usuarios->map(fn ($usuario) => [
            'type' => 'Usuario',
            'title' => $usuario->nombre ?? $usuario->email,
            'description' => 'Email: '.$usuario->email,
            'url' => route('usuarios.search', ['search_term' => $usuario->email]),
        ]));
    }
}

<?php

namespace Database\Seeders;

use App\Enums\EstadoConsultaEnum;
use App\Enums\RolEnum;
use App\Models\Servicios\Cliente;
use App\Models\Servicios\ConsultaMedica;
use App\Models\Servicios\HistorialVacunacion;
use App\Models\Servicios\Mascota;
use App\Models\Servicios\Pago;
use App\Models\Servicios\Servicio;
use App\Models\Servicios\Tratamiento;
use App\Models\Servicios\Vacuna;
use App\Models\Usuario;
use App\Models\Ventas\Almacen;
use App\Models\Ventas\Categoria;
use App\Models\Ventas\DetalleNotaCompra;
use App\Models\Ventas\DetalleNotaVenta;
use App\Models\Ventas\Inventario;
use App\Models\Ventas\NotaCompra;
use App\Models\Ventas\NotaVenta;
use App\Models\Ventas\Producto;
use App\Models\Ventas\Proveedor;
use App\Services\Ventas\InventarioService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoCompletoSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureCatalogos();
        $this->seedNotasCompraEInventario();
        $this->seedConsultasYTratamientos();
        $this->seedVacunasEHistorial();
        $this->seedNotasVenta();
        $this->seedPagos();
    }

    private function ensureCatalogos(): void
    {
        $categorias = ['Antibióticos', 'Analgésicos', 'Vacunas', 'Alimentos', 'Higiene', 'Antiparasitarios'];
        foreach ($categorias as $nombre) {
            Categoria::firstOrCreate(['nombre' => $nombre]);
        }

        $categoriaIds = Categoria::pluck('id', 'nombre');

        $productos = [
            ['nombre' => 'Amoxicilina 250 mg', 'unidad_medida' => 'comprimido', 'presentacion' => 'Caja x 20 comp.', 'categoria' => 'Antibióticos', 'dosificacion' => '1 comp / 12 h', 'fabricante' => 'VetPharma'],
            ['nombre' => 'Meloxicam 1.5 mg', 'unidad_medida' => 'comprimido', 'presentacion' => 'Blister x 10', 'categoria' => 'Analgésicos', 'dosificacion' => '1 comp / 24 h', 'fabricante' => 'LabVet'],
            ['nombre' => 'Vacuna antirrábica', 'unidad_medida' => 'ampolla', 'presentacion' => 'Ampolla 1 ml', 'categoria' => 'Vacunas', 'dosificacion' => '1 dosis anual', 'fabricante' => 'BioVet'],
            ['nombre' => 'Vacuna séxtuple canina', 'unidad_medida' => 'ampolla', 'presentacion' => 'Dosis única', 'categoria' => 'Vacunas', 'dosificacion' => '1 dosis', 'fabricante' => 'BioVet'],
            ['nombre' => 'Alimento premium perro 15 kg', 'unidad_medida' => 'bolsa', 'presentacion' => 'Saco 15 kg', 'categoria' => 'Alimentos', 'dosificacion' => 'Según peso', 'fabricante' => 'NutriPet'],
            ['nombre' => 'Shampoo antipulgas', 'unidad_medida' => 'frasco', 'presentacion' => 'Frasco 250 ml', 'categoria' => 'Higiene', 'dosificacion' => 'Uso tópico', 'fabricante' => 'CleanPet'],
            ['nombre' => 'Ivermectina gotas', 'unidad_medida' => 'frasco', 'presentacion' => 'Frasco 10 ml', 'categoria' => 'Antiparasitarios', 'dosificacion' => '1 gota / kg', 'fabricante' => 'VetPharma'],
            ['nombre' => 'Pipeta antipulgas', 'unidad_medida' => 'pipeta', 'presentacion' => 'Pipeta individual', 'categoria' => 'Antiparasitarios', 'dosificacion' => '1 pipeta mensual', 'fabricante' => 'FleaStop'],
            ['nombre' => 'Suero fisiológico 500 ml', 'unidad_medida' => 'frasco', 'presentacion' => 'Frasco 500 ml', 'categoria' => 'Higiene', 'dosificacion' => 'Uso IV/SC', 'fabricante' => 'MediVet'],
            ['nombre' => 'Complejo vitamínico B', 'unidad_medida' => 'frasco', 'presentacion' => 'Frasco 100 ml', 'categoria' => 'Alimentos', 'dosificacion' => '1 ml / 10 kg', 'fabricante' => 'NutriPet'],
        ];

        foreach ($productos as $p) {
            Producto::firstOrCreate(
                ['nombre' => $p['nombre']],
                [
                    'unidad_medida' => $p['unidad_medida'],
                    'presentacion' => $p['presentacion'],
                    'dosificacion' => $p['dosificacion'],
                    'fabricante' => $p['fabricante'],
                    'fecha_vencimiento' => now()->addMonths(18)->toDateString(),
                    'sustancia_controlada' => false,
                    'stock_minimo' => 5,
                    'categoria_id' => $categoriaIds[$p['categoria']] ?? $categoriaIds->first(),
                ]
            );
        }

        $proveedores = [
            ['nombre' => 'Distribuidora VetBolivia', 'pais' => 'Bolivia', 'telefono' => '70111222', 'email' => 'ventas@vetbolivia.bo'],
            ['nombre' => 'Farmacéutica Animal SA', 'pais' => 'Bolivia', 'telefono' => '70222333', 'email' => 'pedidos@farmanimal.bo'],
            ['nombre' => 'Importadora MediPet', 'pais' => 'Bolivia', 'telefono' => '70333444', 'email' => 'compras@medipet.bo'],
            ['nombre' => 'Laboratorios Andinos', 'pais' => 'Bolivia', 'telefono' => '70444555', 'email' => 'info@andinos.bo'],
            ['nombre' => 'Suministros Clínicos del Sur', 'pais' => 'Bolivia', 'telefono' => '70555666', 'email' => 'contacto@clinicosur.bo'],
        ];

        foreach ($proveedores as $prov) {
            Proveedor::firstOrCreate(['nombre' => $prov['nombre']], array_merge($prov, [
                'direccion' => 'Av. Principal #'.fake()->buildingNumber().', La Paz',
            ]));
        }

        Almacen::firstOrCreate(
            ['nombre' => 'Almacén Central'],
            ['ubicacion' => 'Planta baja', 'descripcion' => 'Principal']
        );
        Almacen::firstOrCreate(
            ['nombre' => 'Farmacia'],
            ['ubicacion' => 'Recepción', 'descripcion' => 'Venta directa']
        );

        $servicios = [
            ['nombre' => 'Consulta médica', 'descripcion' => 'Evaluación clínica general', 'precio' => 80.00],
            ['nombre' => 'Vacunación', 'descripcion' => 'Aplicación de vacunas', 'precio' => 50.00],
            ['nombre' => 'Cirugía menor', 'descripcion' => 'Procedimientos quirúrgicos menores', 'precio' => 200.00],
            ['nombre' => 'Peluquería canina', 'descripcion' => 'Baño y corte de pelo', 'precio' => 60.00],
            ['nombre' => 'Desparasitación', 'descripcion' => 'Control de parásitos', 'precio' => 40.00],
        ];

        foreach ($servicios as $servicio) {
            Servicio::firstOrCreate(
                ['nombre' => $servicio['nombre']],
                array_merge($servicio, ['esta_activo' => true])
            );
        }
    }

    private function seedNotasCompraEInventario(): void
    {
        if (NotaCompra::count() >= 3) {
            return;
        }

        $usuario = $this->usuarioOperador();
        $almacen = Almacen::where('nombre', 'Almacén Central')->first() ?? Almacen::first();
        $proveedores = Proveedor::orderBy('id')->take(3)->get();
        $productos = Producto::orderBy('id')->take(8)->get();

        if ($proveedores->isEmpty() || $productos->isEmpty() || ! $almacen) {
            return;
        }

        $compras = [
            ['dias' => 45, 'items' => [0, 1, 2], 'cantidades' => [50, 30, 20], 'precios' => [12.50, 8.00, 35.00]],
            ['dias' => 30, 'items' => [3, 4, 5], 'cantidades' => [40, 25, 15], 'precios' => [42.00, 180.00, 22.00]],
            ['dias' => 15, 'items' => [6, 7, 8, 9], 'cantidades' => [60, 35, 40, 25], 'precios' => [18.00, 28.00, 9.50, 15.00]],
        ];

        DB::transaction(function () use ($compras, $proveedores, $productos, $almacen, $usuario) {
            foreach ($compras as $i => $compra) {
                $proveedor = $proveedores[$i % $proveedores->count()];
                $total = 0;
                $lineas = [];

                foreach ($compra['items'] as $j => $idx) {
                    $producto = $productos[$idx % $productos->count()];
                    $cantidad = $compra['cantidades'][$j];
                    $precio = $compra['precios'][$j];
                    $subtotal = round($cantidad * $precio, 2);
                    $total += $subtotal;
                    $lineas[] = compact('producto', 'cantidad', 'precio', 'subtotal');
                }

                $nota = NotaCompra::create([
                    'fecha_compra' => now()->subDays($compra['dias'])->toDateString(),
                    'monto_total' => $total,
                    'proveedor_id' => $proveedor->id,
                    'almacen_id' => $almacen->id,
                    'usuario_id' => $usuario->id,
                ]);

                foreach ($lineas as $linea) {
                    $detalle = DetalleNotaCompra::create([
                        'cantidad' => $linea['cantidad'],
                        'precio_compra' => $linea['precio'],
                        'subtotal' => $linea['subtotal'],
                        'nota_compra_id' => $nota->id,
                        'producto_id' => $linea['producto']->id,
                    ]);

                    Inventario::create([
                        'stock' => $linea['cantidad'],
                        'precio_compra' => $linea['precio'],
                        'precio' => round($linea['precio'] * 1.3 * 1.13, 2),
                        'fecha_vencimiento' => now()->addMonths(12)->toDateString(),
                        'producto_id' => $linea['producto']->id,
                        'almacen_id' => $almacen->id,
                        'detalle_nota_compra_id' => $detalle->id,
                    ]);
                }
            }
        });
    }

    private function seedConsultasYTratamientos(): void
    {
        if (ConsultaMedica::count() >= 8) {
            return;
        }

        $vet = $this->usuarioVeterinario();
        $mascotas = Mascota::with('propietario')->orderBy('id')->take(6)->get();
        $servicioConsulta = Servicio::where('nombre', 'Consulta médica')->first();
        $servicioVacuna = Servicio::where('nombre', 'Vacunación')->first();
        $servicioDesparasitacion = Servicio::where('nombre', 'Desparasitación')->first();
        $productos = Producto::orderBy('id')->take(5)->get();

        if ($mascotas->isEmpty() || ! $vet) {
            return;
        }

        $consultas = [
            ['dias' => 20, 'estado' => EstadoConsultaEnum::COMPLETADA->value, 'motivo' => 'Control de rutina', 'diagnostico' => 'Paciente sano', 'servicio' => $servicioConsulta, 'costo' => 80],
            ['dias' => 18, 'estado' => EstadoConsultaEnum::COMPLETADA->value, 'motivo' => 'Vacunación anual', 'diagnostico' => 'Sin contraindicaciones', 'servicio' => $servicioVacuna, 'costo' => 50],
            ['dias' => 14, 'estado' => EstadoConsultaEnum::COMPLETADA->value, 'motivo' => 'Vómitos ocasionales', 'diagnostico' => 'Gastritis leve', 'servicio' => $servicioConsulta, 'costo' => 80],
            ['dias' => 10, 'estado' => EstadoConsultaEnum::COMPLETADA->value, 'motivo' => 'Desparasitación', 'diagnostico' => 'Parásitos intestinales', 'servicio' => $servicioDesparasitacion, 'costo' => 40],
            ['dias' => 7, 'estado' => EstadoConsultaEnum::RESERVADA->value, 'motivo' => 'Control post tratamiento', 'diagnostico' => null, 'servicio' => $servicioConsulta, 'costo' => 80],
            ['dias' => 5, 'estado' => EstadoConsultaEnum::EN_ATENCION->value, 'motivo' => 'Herida en pata', 'diagnostico' => 'Evaluación en curso', 'servicio' => $servicioConsulta, 'costo' => 80],
            ['dias' => 3, 'estado' => EstadoConsultaEnum::COMPLETADA->value, 'motivo' => 'Dermatitis', 'diagnostico' => 'Alergia alimentaria', 'servicio' => $servicioConsulta, 'costo' => 80],
            ['dias' => 1, 'estado' => EstadoConsultaEnum::RESERVADA->value, 'motivo' => 'Revisión vacunas', 'diagnostico' => null, 'servicio' => $servicioVacuna, 'costo' => 50],
        ];

        DB::transaction(function () use ($consultas, $mascotas, $vet, $productos) {
            foreach ($consultas as $i => $data) {
                $mascota = $mascotas[$i % $mascotas->count()];
                $fecha = now()->subDays($data['dias']);

                $consulta = ConsultaMedica::create([
                    'fecha' => $fecha->toDateString(),
                    'hora' => $fecha->format('H:i:s'),
                    'motivo' => $data['motivo'],
                    'diagnostico' => $data['diagnostico'],
                    'costo_consulta' => $data['costo'],
                    'estado' => $data['estado'],
                    'mascota_id' => $mascota->id,
                    'usuario_id' => $vet->id,
                    'servicio_id' => $data['servicio']?->id,
                ]);

                if ($data['estado'] === EstadoConsultaEnum::COMPLETADA->value && $productos->isNotEmpty()) {
                    $producto = $productos[$i % $productos->count()];
                    Tratamiento::create([
                        'consulta_medica_id' => $consulta->id,
                        'producto_id' => $producto->id,
                        'cantidad' => fake()->numberBetween(1, 3),
                        'instrucciones_dosis' => $producto->dosificacion ?? 'Según indicación veterinaria',
                        'notas' => 'Tratamiento prescrito en consulta',
                    ]);
                }
            }
        });
    }

    private function seedVacunasEHistorial(): void
    {
        $vacunas = [
            ['nombre' => 'Antirrábica', 'duracion_dias' => 365, 'notas' => 'Obligatoria anual'],
            ['nombre' => 'Séxtuple canina', 'duracion_dias' => 365, 'notas' => 'Refuerzo anual'],
            ['nombre' => 'Triple felina', 'duracion_dias' => 365, 'notas' => 'Para gatos'],
            ['nombre' => 'Parvovirus', 'duracion_dias' => 180, 'notas' => 'Cachorros'],
        ];

        foreach ($vacunas as $v) {
            Vacuna::firstOrCreate(['nombre' => $v['nombre']], $v);
        }

        if (HistorialVacunacion::count() >= 4) {
            return;
        }

        $vet = $this->usuarioVeterinario();
        $mascotas = Mascota::orderBy('id')->take(4)->get();
        $vacunasDb = Vacuna::orderBy('id')->get();

        if ($mascotas->isEmpty() || $vacunasDb->isEmpty() || ! $vet) {
            return;
        }

        foreach ($mascotas as $i => $mascota) {
            $vacuna = $vacunasDb[$i % $vacunasDb->count()];
            $fecha = now()->subMonths($i + 1);

            HistorialVacunacion::firstOrCreate(
                [
                    'mascota_id' => $mascota->id,
                    'vacuna_id' => $vacuna->id,
                    'fecha_aplicacion' => $fecha->toDateString(),
                ],
                [
                    'fecha_proxima' => $fecha->copy()->addDays($vacuna->duracion_dias ?? 365)->toDateString(),
                    'aplicado_por' => $vet->id,
                    'notas' => 'Aplicación registrada en demo',
                ]
            );
        }
    }

    private function seedNotasVenta(): void
    {
        if (NotaVenta::count() >= 3) {
            return;
        }

        $usuario = $this->usuarioOperador();
        $almacen = Almacen::where('nombre', 'Farmacia')->first()
            ?? Almacen::where('nombre', 'Almacén Central')->first()
            ?? Almacen::first();
        $clientes = Cliente::orderBy('id')->take(4)->get();
        $productos = Producto::orderBy('id')->take(6)->get();
        $inventarioService = app(InventarioService::class);

        if ($clientes->isEmpty() || $productos->isEmpty() || ! $almacen) {
            return;
        }

        $ventas = [
            ['dias' => 12, 'cliente' => 0, 'items' => [0, 1], 'cantidades' => [2, 1]],
            ['dias' => 8, 'cliente' => 1, 'items' => [2, 5], 'cantidades' => [1, 3]],
            ['dias' => 4, 'cliente' => 2, 'items' => [3, 4, 6], 'cantidades' => [1, 2, 1]],
        ];

        DB::transaction(function () use ($ventas, $clientes, $productos, $almacen, $usuario, $inventarioService) {
            foreach ($ventas as $venta) {
                $cliente = $clientes[$venta['cliente'] % $clientes->count()];
                $total = 0;
                $lineas = [];

                foreach ($venta['items'] as $j => $idx) {
                    $producto = $productos[$idx % $productos->count()];
                    $cantidad = $venta['cantidades'][$j];
                    $precioVenta = round(
                        (float) Inventario::where('almacen_id', $almacen->id)
                            ->where('producto_id', $producto->id)
                            ->orderBy('creado_en')
                            ->value('precio') ?: 25.00,
                        2
                    );
                    $subtotal = round($cantidad * $precioVenta, 2);
                    $total += $subtotal;
                    $lineas[] = compact('producto', 'cantidad', 'precioVenta', 'subtotal');
                }

                $nota = NotaVenta::create([
                    'fecha_venta' => now()->subDays($venta['dias']),
                    'monto_total' => $total,
                    'estado' => 'completada',
                    'cliente_id' => $cliente->id,
                    'usuario_id' => $usuario->id,
                    'almacen_id' => $almacen->id,
                ]);

                foreach ($lineas as $linea) {
                    $detalle = DetalleNotaVenta::create([
                        'cantidad' => $linea['cantidad'],
                        'precio_venta' => $linea['precioVenta'],
                        'subtotal' => $linea['subtotal'],
                        'nota_venta_id' => $nota->id,
                        'producto_id' => $linea['producto']->id,
                    ]);

                    try {
                        $inventarioService->consumeStock(
                            $almacen->id,
                            $linea['producto']->id,
                            $linea['cantidad'],
                            $detalle->id
                        );
                    } catch (\Exception) {
                        // Si no hay stock suficiente, el detalle queda registrado igual.
                    }
                }
            }
        });
    }

    private function seedPagos(): void
    {
        if (Pago::count() >= 5) {
            return;
        }

        $usuario = $this->usuarioOperador();
        $consultas = ConsultaMedica::with('mascota')
            ->where('estado', EstadoConsultaEnum::COMPLETADA->value)
            ->orderByDesc('fecha')
            ->take(5)
            ->get();
        $notasVenta = NotaVenta::orderByDesc('fecha_venta')->take(2)->get();

        foreach ($consultas as $consulta) {
            Pago::firstOrCreate(
                ['consulta_id' => $consulta->id],
                [
                    'fecha_pago' => $consulta->fecha,
                    'monto' => $consulta->costo_consulta ?? 80,
                    'metodo_pago' => fake()->randomElement(['efectivo', 'tarjeta', 'qr']),
                    'tipo_pago' => 'contado',
                    'servicio_id' => $consulta->servicio_id,
                    'cliente_id' => $consulta->mascota?->cliente_id,
                    'mascota_id' => $consulta->mascota_id,
                    'usuario_id' => $usuario->id,
                ]
            );
        }

        foreach ($notasVenta as $nota) {
            Pago::firstOrCreate(
                ['nota_venta_id' => $nota->id],
                [
                    'fecha_pago' => $nota->fecha_venta,
                    'monto' => $nota->monto_total,
                    'metodo_pago' => 'efectivo',
                    'tipo_pago' => 'contado',
                    'cliente_id' => $nota->cliente_id,
                    'usuario_id' => $usuario->id,
                ]
            );
        }
    }

    private function usuarioVeterinario(): ?Usuario
    {
        return Usuario::whereHas('rol', fn ($q) => $q->where('nombre', RolEnum::VETERINARIO->value))->first()
            ?? Usuario::whereHas('rol', fn ($q) => $q->where('nombre', RolEnum::ADMINISTRADOR->value))->first()
            ?? Usuario::first();
    }

    private function usuarioOperador(): Usuario
    {
        return Usuario::whereHas('rol', fn ($q) => $q->where('nombre', RolEnum::ADMINISTRADOR->value))->first()
            ?? Usuario::whereHas('rol', fn ($q) => $q->where('nombre', RolEnum::RECEPCIONISTA->value))->first()
            ?? Usuario::first();
    }
}

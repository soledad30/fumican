<?php

namespace App\Http\Controllers;

use App\Enums\RolEnum;
use App\Models\Servicios\Cliente;
use App\Models\Servicios\ConsultaMedica;
use App\Models\Servicios\Mascota;
use App\Models\Ventas\NotaCompra;
use App\Models\Ventas\NotaVenta;
use App\Models\Ventas\DetalleNotaVenta;
use App\Models\Ventas\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user?->rol?->nombre === RolEnum::CLIENTE->value) {
            return redirect()->route('portal.index');
        }

        // --- GRÁFICOS DE SERVICIOS ---

        // 1. Consultas por Mes
        $monthExpressionConsultations = $this->monthExpression('creado_en');

        $consultationsByMonth = ConsultaMedica::select(
            DB::raw('count(id) as count'),
            DB::raw("{$monthExpressionConsultations} as month")
        )
            ->where('creado_en', '>=', now()->subYear())
            ->groupBy(DB::raw($monthExpressionConsultations))
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy('month');

        $consultationMonths = [];
        $consultationData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthKey = $month->format('Y-m');
            $consultationMonths[] = $month->isoFormat('MMM YY');
            $consultationData[] = $consultationsByMonth->get($monthKey)->count ?? 0;
        }

        // 2. Mascotas por Especie (Esta consulta ya era compatible)
        $petsBySpecies = Mascota::join('razas', 'mascotas.raza_id', '=', 'razas.id')
            ->join('especies', 'razas.especie_id', '=', 'especies.id')
            ->select('especies.nombre', DB::raw('count(mascotas.id) as count'))
            ->groupBy('especies.nombre')
            ->pluck('count', 'nombre');

        $monthExpressionCustomers = $this->monthExpression('creado_en');

        $newCustomersByMonth = Cliente::select(
            DB::raw('count(id) as count'),
            DB::raw("{$monthExpressionCustomers} as month")
        )
            ->where('creado_en', '>=', now()->subMonths(6))
            ->groupBy(DB::raw($monthExpressionCustomers))
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy('month');

        $customerMonths = [];
        $customerData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthKey = $month->format('Y-m');
            $customerMonths[] = $month->isoFormat('MMM YY');
            $customerData[] = $newCustomersByMonth->get($monthKey)->count ?? 0;
        }

        // --- GRÁFICOS DE VENTAS Y COMPRAS ---

        // 4. Ventas y Compras (últimos 6 meses) - (Lógica unificada)
        $dateFilter = now()->subMonths(6)->format('Y-m-d');

        $salesMonthExpression = $this->monthExpression('fecha_venta');
        $salesTotalExpression = $this->sumDecimalExpression('monto_total');

        $salesByMonth = NotaVenta::select(
            DB::raw("{$salesTotalExpression} as total"),
            DB::raw("{$salesMonthExpression} as month")
        )
            ->where('fecha_venta', '>=', $dateFilter)
            ->groupBy(DB::raw($salesMonthExpression))
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy('month');

        $purchasesMonthExpression = $this->monthExpression('fecha_compra');
        $purchasesTotalExpression = $this->sumDecimalExpression('monto_total');

        $purchasesByMonth = NotaCompra::select(
            DB::raw("{$purchasesTotalExpression} as total"),
            DB::raw("{$purchasesMonthExpression} as month")
        )
            ->where('fecha_compra', '>=', $dateFilter)
            ->groupBy(DB::raw($purchasesMonthExpression))
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy('month');


        $salesMonths = [];
        $salesData = [];
        $purchasesData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthKey = $month->format('Y-m');
            $salesMonths[] = $month->isoFormat('MMM YY');
            $salesData[] = floatval($salesByMonth->get($monthKey)->total ?? 0); // Usar floatval por el cast a DECIMAL
            $purchasesData[] = floatval($purchasesByMonth->get($monthKey)->total ?? 0); // Usar floatval
        }

        // 5. Top 5 Medicamentos más vendidos (cantidad) - (Ya era compatible)
        $topMedicaments = DetalleNotaVenta::select('producto_id', DB::raw('SUM(cantidad) as total_quantity'))
            ->with('medicament:id,nombre')
            ->groupBy('producto_id')
            ->orderBy('total_quantity', 'desc')
            ->limit(5)
            ->get();

        $inventoryValueByWarehouse = Inventario::select('almacen_id', DB::raw('SUM(stock * precio) as total_value'))
            ->with('warehouse:id,nombre')
            ->groupBy('almacen_id')
            ->get();


        $stats = [
            'consultations' => [
                'labels' => $consultationMonths,
                'data' => $consultationData,
            ],
            'species' => [
                'labels' => $petsBySpecies->keys(),
                'data' => $petsBySpecies->values(),
            ],
            'newCustomers' => [
                'labels' => $customerMonths,
                'data' => $customerData,
            ],
            'salesVsPurchases' => [
                'labels' => $salesMonths,
                'sales' => $salesData,
                'purchases' => $purchasesData,
            ],
            'topMedicaments' => [
                'labels' => $topMedicaments->pluck('medicament.nombre'),
                'data' => $topMedicaments->pluck('total_quantity'),
            ],
            'inventoryValue' => [
                'labels' => $inventoryValueByWarehouse->pluck('warehouse.nombre'),
                'data' => $inventoryValueByWarehouse->pluck('total_value'),
            ]
        ];

        $visitCount = 0;

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'visitCount' => $visitCount
        ]);
    }

    private function monthExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "TO_CHAR({$column}::timestamp, 'YYYY-MM')",
            'mysql', 'mariadb' => "DATE_FORMAT({$column}, '%Y-%m')",
            default => "strftime('%Y-%m', {$column})",
        };
    }

    private function sumDecimalExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "SUM(CAST({$column} AS REAL))",
            'pgsql' => "SUM(CAST({$column} AS DECIMAL(10,2)))",
            default => "SUM({$column})",
        };
    }
}

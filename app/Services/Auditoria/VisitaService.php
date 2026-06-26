<?php

namespace App\Services\Auditoria;

use App\Models\Auditoria\Visita;

class VisitaService
{
    public function registrarVisita(string $ruta): int
    {
        $ruta = $this->normalizarRuta($ruta);
        $visita = Visita::firstOrCreate(['ruta' => $ruta], ['contador' => 0]);
        $visita->increment('contador');
        $visita->update(['ultima_visita' => now()]);

        return $visita->contador;
    }

    public function getContador(string $ruta): int
    {
        $ruta = $this->normalizarRuta($ruta);
        $visita = Visita::where('ruta', $ruta)->first();

        return $visita?->contador ?? 0;
    }

    public function getTotalSitio(): int
    {
        return (int) Visita::sum('contador');
    }

    public function getTopPaginas(int $limit = 10)
    {
        return Visita::orderByDesc('contador')->limit($limit)->get();
    }

    private function normalizarRuta(string $ruta): string
    {
        $ruta = parse_url($ruta, PHP_URL_PATH) ?? '/';

        return $ruta === '' ? '/' : $ruta;
    }
}

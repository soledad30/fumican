<?php

namespace App\Http\Controllers;

use App\Repositories\Servicios\ServicioRepository;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class WelcomeController extends Controller
{
    public function __construct(protected ServicioRepository $servicioRepository) {}

    public function index(): InertiaResponse
    {
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'servicios' => Schema::hasTable('servicios')
                ? $this->servicioRepository->getAllActivos()->map(fn ($s) => [
                    'id' => $s->id,
                    'nombre' => $s->nombre,
                    'descripcion' => $s->descripcion,
                    'precio' => $s->precio,
                ])
                : collect(),
        ]);
    }
}

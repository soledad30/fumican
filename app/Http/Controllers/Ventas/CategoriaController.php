<?php

namespace App\Http\Controllers\Ventas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ventas\StoreCategoriaRequest;
use App\Services\Ventas\CategoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoriaController extends Controller
{
    public function __construct(protected CategoriaService $CategoriaService) {}

    public function index()
    {
        return redirect()->route('productos.index');
    }

    public function search(Request $request)
    {
        $categories = $this->CategoriaService->search($request->input('search_term'));

        return Inertia::render('Ventas/Categorias/Index', compact('categories'));
    }

    public function store(StoreCategoriaRequest $request): JsonResponse
    {
        $category = $this->CategoriaService->createCategory($request->validated());

        return response()->json([
            'message' => 'Categoría creada correctamente.',
            'category' => $category,
        ], 201);
    }

    public function update(StoreCategoriaRequest $request, int $id): JsonResponse
    {
        $this->CategoriaService->updateCategory($id, $request->validated());

        return response()->json([
            'message' => 'Categoría actualizada correctamente.',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->CategoriaService->deleteCategory($id);

        return response()->json([
            'message' => 'Categoría eliminada correctamente.',
        ]);
    }
}

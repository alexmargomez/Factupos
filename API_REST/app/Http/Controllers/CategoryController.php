<?php

namespace App\Http\Controllers;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index() //Listar categorias
    {
        $categories = Category::all();
        return response()->json($categories, 200);
    }

    public function create(Request $request) //Crear categoria
    {
        $request->validate([
            'name' => 'required|string',
        ]);
        
        $category = Category::create($request->all());
        return response()->json($category, 201);
    }

    public function show($id) //Seleccionar una categoria
    {
        $category = Category::findOrFail($id);
        return response()->json($category, 200);
    }

    public function update(Request $request, $id) //Actualizar categoria
    {
        $request->validate([
            'name' => 'required|string',
        ]);
        $category = Category::findOrFail($id);
        $category->update($request->all());

        return response()->json($category, 201);
    }

    public function destroy($id) //Eliminar categoria
    {
        $category = Category::findOrFail($id);
        $category->delete();
    
        return response()->json(['message' => 'Categoria eliminada exitosamente.'], 200);
    }
}

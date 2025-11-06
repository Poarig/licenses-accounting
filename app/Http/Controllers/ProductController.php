<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::whereNull('deleted_at')->get();
        return view('products.index', compact('products'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:products,name',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            Product::create([
                'name' => $request->name,
                'description' => $request->description
            ]);

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Продукт успешно добавлен'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при добавлении продукта: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateField(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'field' => 'required|in:name,description',
            'value' => 'required|string|max:255',
        ]);

        if ($request->field === 'name') {
            $validator->addRules(['value' => 'required|string|max:255|unique:products,name,' . $product->id]);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $field = $request->field;
            $product->$field = $request->value;
            $product->save();

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Данные продукта обновлены'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ошибка обновления: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Product $product)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $product->delete();

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Продукт удален'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении продукта: ' . $e->getMessage()
            ], 500);
        }
    }

    public function restore($id)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $product = Product::withTrashed()->findOrFail($id);
            $product->restore();

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Продукт восстановлен'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при восстановлении продукта: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDeleted()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Доступ запрещен');
        }
    
        $products = Product::onlyTrashed()->get();
        return view('products.index', compact('products'))->with('showDeleted', true);
    }
}
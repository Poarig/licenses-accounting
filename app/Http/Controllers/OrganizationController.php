<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrganizationController extends Controller
{
    public function index()
    {
        $organizations = Organization::all();
        return view('organizations.index', compact('organizations'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:organizations,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            Organization::create([
                'name' => $request->name
            ]);

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Организация успешно добавлена'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при добавлении организации: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateField(Request $request, Organization $organization)
    {
        $validator = Validator::make($request->all(), [
            'field' => 'required|in:name',
            'value' => 'required|string|max:255|unique:organizations,name,' . $organization->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $field = $request->field;
            $organization->$field = $request->value;
            $organization->save();

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Данные обновлены'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ошибка обновления: ' . $e->getMessage()
            ], 500);
        }
    }
}
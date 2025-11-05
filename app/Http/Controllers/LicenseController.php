<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Pincode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LicenseController extends Controller
{
    public function index()
    {
        $licenses = License::with(['organization', 'product'])->get();
        $products = Product::all();
        $organizations = Organization::all();
        return view('licenses.index', compact('licenses', 'products', 'organizations'));
    }

    public function organizationLicenses(Organization $organization)
    {
        $licenses = License::with(['organization', 'product'])
            ->where('organization_id', $organization->id)
            ->get();
        $products = Product::all();
        return view('licenses.organization', compact('licenses', 'organization', 'products'));
    }

    public function store(Request $request)
    {
        \Log::info('License store method called', $request->all());

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'max_count' => 'nullable|integer|min:1',
            'number' => 'required|string|max:255|unique:licenses,number',
            'organization_id' => 'required|exists:organizations,id',
            'single_pincodes' => 'nullable|string',
            'multi_pincodes' => 'nullable|string',
        ], [
            'product_id.required' => 'Выберите продукт',
            'max_count.integer' => 'Максимальное количество должно быть числом',
            'max_count.min' => 'Максимальное количество должно быть не менее 1',
            'number.required' => 'Укажите номер лицензии',
            'number.unique' => 'Лицензия с таким номером уже существует',
            'organization_id.required' => 'Выберите организацию',
        ]);

        if ($validator->fails()) {
            \Log::error('License validation failed', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $license = License::create([
                'product_id' => $request->product_id,
                'max_count' => $request->max_count,
                'number' => $request->number,
                'organization_id' => $request->organization_id,
            ]);

            // Обрабатываем однопользовательские пинкоды
            if ($request->filled('single_pincodes')) {
                $singlePincodes = array_filter(
                    explode("\n", $request->single_pincodes),
                    function($pincode) { return trim($pincode) !== ''; }
                );
                
                foreach ($singlePincodes as $pincodeValue) {
                    Pincode::create([
                        'license_id' => $license->id,
                        'value' => trim($pincodeValue),
                        'type' => 'single',
                        'status' => 'nonactivated'
                    ]);
                }
            }

            // Обрабатываем многопользовательские пинкоды
            if ($request->filled('multi_pincodes')) {
                $multiPincodes = array_filter(
                    explode("\n", $request->multi_pincodes),
                    function($pincode) { return trim($pincode) !== ''; }
                );
                
                foreach ($multiPincodes as $pincodeValue) {
                    Pincode::create([
                        'license_id' => $license->id,
                        'value' => trim($pincodeValue),
                        'type' => 'multi',
                        'status' => 'nonactivated'
                    ]);
                }
            }

            DB::commit();

            \Log::info('License created successfully', ['id' => $license->id]);

            return response()->json([
                'success' => true, 
                'message' => 'Лицензия успешно добавлена'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('License creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при добавлении лицензии: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateField(Request $request, License $license)
    {
        $validator = Validator::make($request->all(), [
            'field' => 'required|in:product_id,max_count,number',
            'value' => 'required',
        ]);

        // валидация для разных полей
        if ($request->field === 'number') {
            $validator->addRules(['value' => 'required|string|max:255|unique:licenses,number,' . $license->id]);
        } elseif ($request->field === 'max_count') {
            $validator->addRules(['value' => 'nullable|integer|min:1']);
        } elseif ($request->field === 'product_id') {
            $validator->addRules(['value' => 'required|exists:products,id']);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Обработка null для max_count
        $value = $request->value;
        if ($request->field === 'max_count' && ($value === '' || $value === null)) {
            $value = null;
        }

        try {
            DB::beginTransaction();

            $field = $request->field;
            $license->$field = $value;
            $license->save();

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Данные лицензии обновлены'
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
<?php

namespace App\Http\Controllers;

use App\Models\Pincode;
use App\Models\License;
use App\Models\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PincodeController extends Controller
{
    public function index(License $license)
    {
        $pincodes = Pincode::with(['actions' => function($query) {
            $query->where('action_type', 'активирован')
                  ->whereNotNull('device_information')
                  ->latest();
        }])->where('license_id', $license->id)->get();

        return view('pincodes.index', compact('pincodes', 'license'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'license_id' => 'required|exists:licenses,id',
            'single_pincodes' => 'nullable|string',
            'multi_pincodes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $licenseId = $request->license_id;

            // Обрабатываем однопользовательские пинкоды
            if ($request->filled('single_pincodes')) {
                $singlePincodes = array_filter(
                    explode("\n", $request->single_pincodes),
                    function($pincode) { return trim($pincode) !== ''; }
                );
                
                foreach ($singlePincodes as $pincodeValue) {
                    $pincode = Pincode::create([
                        'license_id' => $licenseId,
                        'value' => trim($pincodeValue),
                        'type' => 'single',
                        'status' => 'nonactivated'
                    ]);


                    Action::create([
                        'pincode_id' => $pincode->id,
                        'user_id' => auth()->id(),
                        'action_type' => 'добавлен',
                        'comment' => 'Пинкод создан автоматически при добавлении лицензии'
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
                    $pincode = Pincode::create([
                        'license_id' => $licenseId,
                        'value' => trim($pincodeValue),
                        'type' => 'multi',
                        'status' => 'nonactivated'
                    ]);

                    Action::create([
                        'pincode_id' => $pincode->id,
                        'user_id' => auth()->id(),
                        'action_type' => 'добавлен',
                        'comment' => 'Пинкод добавлен в систему.'
                    ]);
                }
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Пинкоды добавлены']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при добавлении пинкодов: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, Pincode $pincode)
    {
        $request->validate([
            'status' => 'required|in:nonactivated,active,used',
        ]);

        try {
            DB::beginTransaction();

            $pincode->status = $request->status;
            $pincode->save();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Статус пинкода обновлен']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Ошибка обновления'], 500);
        }
    }

    public function changeStatusWithComment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pincode_id' => 'required|exists:pincodes,id',
            'status' => 'required|in:nonactivated,active,used',
            'comment' => 'nullable|string|max:1000',
            'device_information' => 'nullable|string|max:1000',
        ]);

        // Исправляем логику проверки устройства
        $validator->after(function ($validator) use ($request) {
            if ($request->status === 'active' && empty($request->device_information)) {
                $validator->errors()->add(
                    'device_information', 
                    'Информация об устройстве обязательна при активации пинкода'
                );
            }

            // Для деактивации устройство не обязательно
            if ($request->status === 'used' && !empty($request->device_information)) {
                // Можно очистить или оставить - зависит от бизнес-логики
                // Оставляем как есть, так как это может быть полезно для истории
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $pincode = Pincode::find($request->pincode_id);
            $oldStatus = $pincode->status;

            // Обновляем статус пинкода
            $pincode->status = $request->status;
            $pincode->save();

            // Исправляем логику определения типа действия
            $actionType = '';
            switch ($request->status) {
                case 'active':
                    $actionType = 'активирован';
                    break;
                case 'used':
                    $actionType = 'дезактивирован';
                    break;
                case 'nonactivated':
                    // Для сброса статуса используем более подходящее действие
                    $actionType = 'сброшен';
                    break;
            }

            // Логирование действия
            $actionData = [
                'pincode_id' => $pincode->id,
                'user_id' => auth()->id(),
                'action_type' => $actionType,
                'comment' => $request->comment ?? 'Изменение статуса',
            ];

            if (!empty($request->device_information)) {
                $actionData['device_information'] = $request->device_information;
            }

            Action::create($actionData);

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Статус пинкода изменен',
                'new_status' => $pincode->status
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ошибка изменения статуса: ' . $e->getMessage()
            ], 500);
        }
    }
}
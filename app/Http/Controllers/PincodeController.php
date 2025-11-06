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
        $pincodes = Pincode::whereHas('license', function($query) {
                $query->whereNull('deleted_at');
            })
            ->with(['actions' => function($query) {
                $query->where('action_type', 'активирован')
                      ->whereNotNull('device_information')
                      ->latest();
            }])
            ->where('license_id', $license->id)
            ->get();

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
            'file' => 'nullable|file|max:10240',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->status === 'active' && empty($request->device_information)) {
                $validator->errors()->add(
                    'device_information', 
                    'Информация об устройстве обязательна при активации пинкода'
                );
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
            $license = $pincode->license;

            // Проверки только при активации
            if ($request->status === 'active') {
                // Получаем активные пинкоды лицензии
                $activePincodes = $license->pincodes()
                    ->where('status', 'active')
                    ->get();

                $activeSinglePincodes = $activePincodes->where('type', 'single');
                $activeMultiPincodes = $activePincodes->where('type', 'multi');

                // Проверка 1: Если активирован многопользовательский пинкод - никакие другие нельзя активировать
                if ($activeMultiPincodes->count() > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Невозможно активировать пинкод. В лицензии уже активирован многопользовательский пинкод.'
                    ], 422);
                }

                // Проверка 2: Если активируем многопользовательский пинкод
                if ($pincode->isMultiUser()) {
                    // Нельзя активировать, если есть активные однопользовательские пинкоды
                    if ($activeSinglePincodes->count() > 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Невозможно активировать многопользовательский пинкод. В лицензии уже есть активные однопользовательские пинкоды.'
                        ], 422);
                    }

                    // Нельзя активировать, если есть другие активные многопользовательские пинкоды
                    if ($activeMultiPincodes->count() > 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Невозможно активировать многопользовательский пинкод. В лицензии уже есть активный многопользовательский пинкод.'
                        ], 422);
                    }
                }

                // Проверка 3: Если активируем однопользовательский пинкод
                if ($pincode->isSingleUser()) {
                    // Нельзя активировать, если есть активные многопользовательские пинкоды
                    if ($activeMultiPincodes->count() > 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Невозможно активировать однопользовательский пинкод. В лицензии уже активирован многопользовательский пинкод.'
                        ], 422);
                    }

                    // Проверка максимального количества однопользовательских активаций
                    if ($license->max_count !== null) {
                        $currentActiveSingleCount = $activeSinglePincodes->count();

                        // Если текущий пинкод уже активен, не учитываем его в подсчете
                        if ($pincode->status === 'active') {
                            $currentActiveSingleCount--;
                        }

                        if ($currentActiveSingleCount >= $license->max_count) {
                            return response()->json([
                                'success' => false,
                                'message' => "Невозможно активировать пинкод. Достигнуто максимальное количество активаций ({$license->max_count}) для этой лицензии."
                            ], 422);
                        }
                    }
                }
            }

            \Log::info('Changing pincode status', [
                'pincode_id' => $pincode->id,
                'old_status' => $pincode->status,
                'new_status' => $request->status
            ]);

            // Обновляем статус пинкода
            $pincode->status = $request->status;
            $pincode->save();

            // Определяем тип действия
            $actionType = '';
            switch ($request->status) {
                case 'active':
                    $actionType = 'активирован';
                    break;
                case 'used':
                    $actionType = 'дезактивирован';
                    break;
                case 'nonactivated':
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

            // Обрабатываем загрузку файла
            if ($request->hasFile('file')) {
                $file = $request->file('file');

                // Читаем содержимое файла и кодируем в base64
                $fileContent = file_get_contents($file->getRealPath());

                $actionData['file_data'] = base64_encode($fileContent);
                $actionData['file_name'] = $file->getClientOriginalName();
            }

            Action::create($actionData);

            DB::commit();

            \Log::info('Status changed successfully', [
                'pincode_id' => $pincode->id, 
                'status' => $pincode->status
            ]);

            return response()->json([
                'success' => true, 
                'message' => 'Статус пинкода изменен',
                'new_status' => $pincode->status
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error changing status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка изменения статуса: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadFile($actionId)
    {
        try {
            $action = Action::findOrFail($actionId);

            if (!$action->file_data) {
                abort(404, 'Файл не найден');
            }

            // Определяем MIME-тип по расширению файла
            $extension = strtolower(pathinfo($action->file_name, PATHINFO_EXTENSION));
            $mimeTypes = [
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'txt' => 'text/plain; charset=utf-8',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'zip' => 'application/zip',
                'rar' => 'application/vnd.rar',
                'liq' => 'application/octet-stream'
            ];

            $contentType = $mimeTypes[$extension] ?? 'application/octet-stream';

            $headers = [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'attachment; filename="' . $action->file_name . '"',
            ];

            // Используем streamDownload для корректной обработки бинарных данных
            return response()->streamDownload(function () use ($action) {
                $fileData = $action->file_data;
                if (is_resource($fileData)) {
                    fpassthru($fileData);
                } else {
                    echo $fileData;
                }
            }, $action->file_name, $headers);

        } catch (\Exception $e) {
            \Log::error('Error downloading file: ' . $e->getMessage());
            abort(500, 'Ошибка при загрузке файла: ' . $e->getMessage());
        }
    }

    public function destroy(Pincode $pincode)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $pincode->delete();

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Пинкод удален'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении пинкода: ' . $e->getMessage()
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

            $pincode = Pincode::withTrashed()->findOrFail($id);
            $pincode->restore();

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Пинкод восстановлен'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при восстановлении пинкода: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDeleted(License $license)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Доступ запрещен');
        }
    
        $pincodes = Pincode::onlyTrashed()
            ->whereHas('license', function($query) {
                $query->whereNull('deleted_at');
            })
            ->with(['actions' => function($query) {
                $query->where('action_type', 'активирован')
                      ->whereNotNull('device_information')
                      ->latest();
            }])
            ->where('license_id', $license->id)
            ->get();
        
        return view('pincodes.index', compact('pincodes', 'license'))->with('showDeleted', true);
    }
}
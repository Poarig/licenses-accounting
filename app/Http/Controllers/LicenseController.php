<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Pincode;
use App\Models\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LicenseController extends Controller
{
    public function index()
    {
        $licenses = License::whereHas('organization', function($query) {
                $query->whereNull('deleted_at');
            })
            ->whereHas('product', function($query) {
                $query->whereNull('deleted_at');
            })
            ->with(['organization', 'product'])
            ->get();

        $products = Product::whereNull('deleted_at')->get();
        $organizations = Organization::whereNull('deleted_at')->get();
        return view('licenses.index', compact('licenses', 'products', 'organizations'));
    }


    public function organizationLicenses(Organization $organization)
    {
        $licenses = License::where('organization_id', $organization->id)
            ->whereHas('product', function($query) {
                $query->whereNull('deleted_at');
            })
            ->with(['organization', 'product'])
            ->get();

        $products = Product::whereNull('deleted_at')->get();
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
            'license_file' => 'required|file|max:10240|mimes:zip,rar,7z,tar,gz', // разрешаем архивы
        ], [
            'product_id.required' => 'Выберите продукт',
            'max_count.integer' => 'Максимальное количество должно быть числом',
            'max_count.min' => 'Максимальное количество должно быть не менее 1',
            'number.required' => 'Укажите номер лицензии',
            'number.unique' => 'Лицензия с таким номером уже существует',
            'organization_id.required' => 'Выберите организации',
            'license_file.max' => 'Размер файла не должен превышать 10MB',
            'license_file.mimes' => 'Допустимые форматы архивов: zip, rar, 7z, tar, gz',
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

            $licenseData = [
                'product_id' => $request->product_id,
                'max_count' => $request->max_count,
                'number' => $request->number,
                'organization_id' => $request->organization_id,
            ];

            // Обрабатываем загрузку файла
            if ($request->hasFile('license_file')) {
                $file = $request->file('license_file');
                
                // Сохраняем оригинальное имя файла
                $licenseData['archive_name'] = $file->getClientOriginalName();
                
                // Временно сохраняем файл и обрабатываем через модель
                $tempLicense = new License($licenseData);
                $tempLicense->saveFileData($file->getRealPath());
                $licenseData['archive_data'] = $tempLicense->archive_data;
            }

            $license = License::create($licenseData);

            // Обрабатываем однопользовательские пинкоды
            if ($request->filled('single_pincodes')) {
                $singlePincodes = array_filter(
                    explode("\n", $request->single_pincodes),
                    function($pincode) { return trim($pincode) !== ''; }
                );
                
                foreach ($singlePincodes as $pincodeValue) {
                    $pincode = Pincode::create([
                        'license_id' => $license->id,
                        'value' => trim($pincodeValue),
                        'type' => 'single',
                        'status' => 'nonactivated'
                    ]);

                    // Логирование действия
                    Action::create([
                        'pincode_id' => $pincode->id,
                        'user_id' => auth()->id(),
                        'action_type' => 'добавлен',
                        'date' => now(),
                        'comment' => 'Пинкод добавлен в систему.'
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
                        'license_id' => $license->id,
                        'value' => trim($pincodeValue),
                        'type' => 'multi',
                        'status' => 'nonactivated'
                    ]);

                    // Логирование действия
                    Action::create([
                        'pincode_id' => $pincode->id,
                        'user_id' => auth()->id(),
                        'action_type' => 'добавлен',
                        'date' => now(),
                        'comment' => 'Пинкод добавлен в систему.'
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

    // Метод для скачивания архива
    public function downloadFile(License $license)
    {
        try {
            if (!$license->hasFile()) {
                abort(404, 'Файл не найден');
            }

            // Получаем бинарные данные через метод модели
            $fileData = $license->getFileBinaryData();
            
            if (!$fileData) {
                throw new \Exception('Не удалось получить данные файла');
            }

            // Определяем MIME-тип по расширению файла
            $extension = strtolower($license->getFileExtension());
            $mimeTypes = [
                'zip' => 'application/zip',
                'rar' => 'application/vnd.rar',
                '7z' => 'application/x-7z-compressed',
                'tar' => 'application/x-tar',
                'gz' => 'application/gzip',
            ];

            $contentType = $mimeTypes[$extension] ?? 'application/octet-stream';

            $headers = [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'attachment; filename="' . $license->archive_name . '"',
                'Content-Length' => strlen($fileData),
            ];

            \Log::info('Downloading license file', [
                'license_id' => $license->id,
                'file_name' => $license->archive_name,
                'file_size' => strlen($fileData),
                'content_type' => $contentType
            ]);

            return response($fileData, 200, $headers);

        } catch (\Exception $e) {
            \Log::error('Error downloading license file: ' . $e->getMessage());
            abort(500, 'Ошибка при загрузке файла: ' . $e->getMessage());
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

     public function destroy(License $license)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $license->delete();

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Лицензия удалена'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении лицензии: ' . $e->getMessage()
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

            $license = License::withTrashed()->findOrFail($id);
            $license->restore();

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Лицензия восстановлена'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при восстановлении лицензии: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDeleted()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Доступ запрещен');
        }
    
        $licenses = License::onlyTrashed()
            ->whereHas('organization', function($query) {
                $query->whereNull('deleted_at');
            })
            ->whereHas('product', function($query) {
                $query->whereNull('deleted_at');
            })
            ->with(['organization', 'product'])
            ->get();
        
        $products = Product::whereNull('deleted_at')->get();
        $organizations = Organization::whereNull('deleted_at')->get();
        return view('licenses.index', compact('licenses', 'products', 'organizations'))->with('showDeleted', true);
    }

    public function getOrganizationDeleted(Organization $organization)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Доступ запрещен');
        }

        $licenses = License::onlyTrashed()
            ->where('organization_id', $organization->id)
            ->whereHas('product', function($query) {
                $query->whereNull('deleted_at');
            })
            ->with(['organization', 'product'])
            ->get();

        $products = Product::whereNull('deleted_at')->get();
        return view('licenses.organization', compact('licenses', 'organization', 'products'))->with('showDeleted', true);
    }
}
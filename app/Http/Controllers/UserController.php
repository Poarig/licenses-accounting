<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withTrashed()->orderBy('created_at', 'desc')->get();
        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'patronymic' => 'nullable|string|max:255',
            'login' => 'required|string|max:255|unique:users,login',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,user',
        ], [
            'login.unique' => 'Пользователь с таким логином уже существует.',
            'password.min' => 'Пароль должен содержать минимум 8 символов.',
            'password.confirmed' => 'Пароли не совпадают.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            User::create([
                'name' => $request->name,
                'surname' => $request->surname,
                'patronymic' => $request->patronymic,
                'login' => $request->login,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Пользователь успешно создан'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании пользователя: ' . $e->getMessage() // Исправлено с $request на $e
            ], 500);
        }
    }

    public function update(Request $request, User $user)
    {
        //$this->checkAdmin();

        \Log::info('Updating user data:', $request->all());

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'patronymic' => 'nullable|string|max:255',
            'login' => 'required|string|max:255|unique:users,login,' . $user->id,
            'password' => 'nullable|min:8|confirmed',
            'role' => 'required|in:admin,user',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation errors:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            \Log::info('Updating user:', [
                'id' => $user->id,
                'old_role' => $user->role,
                'new_role' => $request->role
            ]);

            $user->name = $request->name;
            $user->surname = $request->surname;
            $user->patronymic = $request->patronymic;
            $user->login = $request->login;
            $user->role = $request->role;

            // Обновляем пароль только если он был указан
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            DB::commit();

            \Log::info('User updated successfully:', ['user_id' => $user->id]);

            return response()->json([
                'success' => true, 
                'message' => 'Пользователь успешно обновлен'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении пользователя: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(User $user, Request $request)
    {
        // Не позволяем удалить самого себя
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалить свою учетную запись'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Пользователь удален'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании пользователя: ' . $e->getMessage() // Исправлено с $request на $e
            ], 500);
        }
    }

    public function restore($id, Request $request)
    {
        try {
            DB::beginTransaction();

            $user = User::withTrashed()->findOrFail($id);
            $user->restore();

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Пользователь восстановлен'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании пользователя: ' . $e->getMessage() // Исправлено с $request на $e
            ], 500);
        }
    }
}
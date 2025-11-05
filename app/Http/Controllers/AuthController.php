<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        // Если пользователь уже авторизован, перенаправляем на организации
        if (Auth::check()) {
            return redirect('/organizations');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        // Проверяем, что пользователь не удален
        $user = User::where('login', $request->login)->first();
        
        if ($user && $user->deleted_at) {
            return back()->withErrors([
                'login' => 'Учетная запись удалена.',
            ]);
        }

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/organizations');
        }

        return back()->withErrors([
            'login' => 'Неверные учетные данные.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
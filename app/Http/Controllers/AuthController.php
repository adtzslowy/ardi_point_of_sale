<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $userId = $request->user_id;
        $field = filter_var($userId, FILTER_VALIDATE_EMAIL) ? 'email' : 'user_id';

        if (
            !Auth::attempt(
                [
                    $field => $userId,
                    'password' => $request->password,
                ],
                $request->boolean('remember'),
            )
        ) {
            return back()
                ->withErrors(['user_id' => 'User ID atau password salah.'])
                ->onlyInput('user_id');
        }

        $request->session()->regenerate();

        Log::info('Session after login', [
            'session_id' => session()->getId(),
            'driver' => config('session.driver'),
            'user_id' => Auth::id(),
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Selamat datang, ' . Auth::user()->name . '!');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('login')->with('success', 'Berhasil logout');
    }
}

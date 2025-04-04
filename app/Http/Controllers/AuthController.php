<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use function Laravel\Prompts\password;

class AuthController extends Controller
{
    public function login(): View
    {
        return view('auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        // validação do formulário
        $credentials = $request->validate(
            [
                'username' => 'required|min:3|max:30',
                'password' => 'required|min:8|max:32|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
            ],
            [
                'username.required' => 'O usuário é obrigatório',
                'username.min' => 'O usuário deve ter no mínimo :min caracteres',
                'username.max' => 'O usuário deve ter no máximo :max caracteres',

                'password.required' => 'A senha é obrigatório',
                'password.min' => 'A senha deve ter no mínimo :min caracteres',
                'password.max' => 'A senha deve ter no máximo :max caracteres',
                'password.regex' => 'A senha deve conter pelo menos uma letra maiúscula, uma letra minúscula e um número'
            ]
        );

        // // login tradicional do laravel 
        // if(Auth::attempt($credentials)){
        //     $request->session()->regenerate();
        //     redirect()->route('home');
        // } // só usar se tem email e password

        // verificar se o user existe
        $user = User::where('username', $credentials['username'])
            ->where('active', true)
            ->where(function ($query) {
                $query->whereNull('blocked_until')
                    ->orWhere('blocked_until', '<=', now());
            })
            ->whereNotNull('email_verified_at')
            ->whereNull('deleted_at')
            ->first();

        // verifica se o user existe
        if (!$user) {
            return back()->withInput()->with([
                'invalid_login' => 'Login inválido.'
            ]);
        }

        // verificar se a password é valida
        if (!password_verify($credentials['password'], $user->password)) {
            return back()->withInput()->with([
                'invalid_login' => 'Login inválido.'
            ]);
        }

        // atualizar o ultimo login (last_login_at)
        $user->last_login_at = now();
        $user->blocked_until = null;
        $user->save();

        // login propriamente dito!
        $request->session()->regenerate();
        Auth::login($user);

        // redirecionamento 
        return redirect()->intended(route('home'));
    }

    public function logout(): RedirectResponse
    {
        // logout
        Auth::logout();
        return redirect()->route('login');
    }

    public function register(): View
    {
        return view('auth.register');
    }

    public function store_user(Request $request): void
    {
        // form validation
        $request->validate(
            [
                'username' => 'required|min:3|max:30|unique:users,username',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8|max:32|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
            ],
            [
                'username.required' => 'O usuário é obrigatório',
                'username.min' => 'O usuário deve ter no mínimo :min caracteres',
                'username.max' => 'O usuário deve ter no máximo :max caracteres',
                'username.unique' => 'O usuário já existe',

                'email.required' => 'O email é obrigatório',
                'email.unique' => 'O email já existe',

                'password.required' => 'A senha é obrigatório',
                'password.min' => 'A senha deve ter no mínimo :min caracteres',
                'password.max' => 'A senha deve ter no máximo :max caracteres',
                'password.regex' => 'A senha deve conter pelo menos uma letra maiúscula, uma letra minúscula e um número'
            ]
        );
    }
}

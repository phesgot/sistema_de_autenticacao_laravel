<?php

namespace App\Http\Controllers;

use App\Mail\NewUserConfirmation;
use App\Mail\ResetPassword;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

    public function store_user(Request $request): RedirectResponse|View
    {
        // form validation
        $request->validate(
            [
                'username' => 'required|min:3|max:30|unique:users,username',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8|max:32|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                'password_confirmation' => 'required|same:password'
            ],
            [
                'username.required' => 'O usuário é obrigatório',
                'username.min' => 'O usuário deve ter no mínimo :min caracteres',
                'username.max' => 'O usuário deve ter no máximo :max caracteres',
                'username.unique' => 'Este nome não está disponivel',

                'email.required' => 'O email é obrigatório',
                'email.email' => 'O email deve ser um endereço de email válido',
                'email.unique' => 'Este email não está disponivel',

                'password.required' => 'A senha é obrigatório',
                'password.min' => 'A senha deve ter no mínimo :min caracteres',
                'password.max' => 'A senha deve ter no máximo :max caracteres',
                'password.regex' => 'A senha deve conter pelo menos uma letra maiúscula, uma letra minúscula e um número',

                'password_confirmation.required' => 'A confirmação da senha é obrigatório',
                'password_confirmation.same' => 'A confirmação da senha deve ser igual a senha',

            ]
        );

        // vamos criar um novo usuário definindo um token de verificação de email
        $user = new User();
        $user->username = $request->username;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->token = Str::random(64);

        // gerar o link
        $confirmation_link = route('new_user_conformation', ['token' => $user->token]);

        // enviar o email
        $result = Mail::to($user->email)->send(new NewUserConfirmation($user->username, $confirmation_link));

        // verificar se o email foi enviado com sucesso
        if (!$result) {
            return back()->withInput()->with([
                'server_error' => 'Ocorreu um erro ao enviar o email de confirmação'
            ]);
        }

        // criar o usuário na base de dados
        $user->save();

        // apresentar uma view de sucesso
        return view('auth.email_sent', ['email' => $user->email]);


        dd($user);
    }

    public function new_user_conformation($token)
    {
        // verifica se o token é válido
        $user = User::where('token', $token)->first();
        if (!$user) {
            return redirect()->route('login');
        }

        // confirmar o registro do usuário
        $user->email_verified_at = Carbon::now();
        $user->token = null;
        $user->active = true;
        $user->save();

        // autenticação automática (login) do usuário confirmado
        Auth::login($user);

        // apresenta uma menssagem de sucesso
        return view('auth.new_user_confirmation');
    }

    public function profile(): View
    {
        return view('auth.profile');
    }

    public function change_password(Request $request)
    {
        // form validation
        $request->validate(
            [
                'current_password' => 'required|min:8|max:32|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                'new_password' => 'required|min:8|max:32|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/|different:current_password',
                'new_password_confirmation' => 'required|same:new_password'
            ],
            [
                'current_password.required' => 'A senha atual é obrigatório',
                'current_password.min' => 'A senha deve ter no mínimo :min caracteres',
                'current_password.max' => 'A senha deve ter no máximo :max caracteres',
                'current_password.regex' => 'A senha deve conter pelo menos uma letra maiúscula, uma letra minúscula e um número',

                'new_password.required' => 'A nova senha é obrigatório',
                'new_password.min' => 'A senha deve ter no mínimo :min caracteres',
                'new_password.max' => 'A senha deve ter no máximo :max caracteres',
                'new_password.regex' => 'A senha deve conter pelo menos uma letra maiúscula, uma letra minúscula e um número',
                'new_password.different' => 'A nova senha deve ser diferente da senha atual',

                'new_password_confirmation.required' => 'A confirmação da nova senha é obrigatório',
                'new_password_confirmation.same' => 'A confirmação da nova senha deve ser igual a nova senha',

            ]
        );

        // verificar se a password atual (current_password) está correta
        if (!password_verify($request->current_password, Auth::user()->password)) {
            return back()->with([
                'server_error' => 'A senha atual não está correta'
            ]);
        }

        // atualizar a senha na base de dados
        $user = Auth::user();
        $user->password = bcrypt($request->new_password);
        $user->save();

        // atualizar a password na sessão
        Auth::user()->password = $request->new_password;

        // apresenta uma menssagem de sucesso
        return redirect()->route('profile')->with([
            'success' => 'A senha foi atualizada com sucesso'
        ]);
    }

    public function forgot_password(): View
    {
        return view('auth.forgot_password');
    }

    public function send_reset_password_link(Request $request)
    {
        // form validation
        $request->validate(
            [
                'email' => 'required|email',
            ],
            [
                'email.required' => 'O email é obrigatório.',
                'email.email' => 'O email deve ser um endereço de email válido.',
            ]
        );

        $generic_message = "Verifique a sua caixa de correio para prosseguir com a recuperação de senha.";

        // verificar se o email existe
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->with([
                'server_message' => $generic_message
            ]);
        }

        // criar o link com token para enviar no email
        $user->token = Str::random(64);

        $token_link = route('reset_password', ['token' => $user->token]);

        // envio de email com link para recuperar a senha
        $result = Mail::to($user->email)->send(new ResetPassword($user->username, $token_link));

        // verifica se o email foi enviado
        if (!$result) {
            return back()->with([
                'server_message' => $generic_message
            ]);
        }

        // guarda o token na base de dados
        $user->save();

        return back()->with([
            'server_message' => $generic_message
        ]);
    }

    public function reset_password($token): View | RedirectResponse
    {
        // verificar se o token é valido
        $user = User::where('token', $token)->first();
        if (!$user) {
            return redirect()->route('login');
        }

        return view('auth.reset_password', ['token' => $token]);
    }

    public function reset_password_update(Request $request): RedirectResponse
    {
        // forma validation
        $request->validate(
            [
                'token' => 'required',
                'new_password' => 'required|min:8|max:32|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/|different:current_password',
                'new_password_confirmation' => 'required|same:new_password'

            ],
            [
                // não coloco menssagem para o token porque não é suposto o utilizador alterar o token
                // nem ter acesso a essa informação.
                'new_password.required' => 'A nova senha é obrigatório',
                'new_password.min' => 'A senha deve ter no mínimo :min caracteres',
                'new_password.max' => 'A senha deve ter no máximo :max caracteres',
                'new_password.regex' => 'A senha deve conter pelo menos uma letra maiúscula, uma letra minúscula e um número',

                'new_password_confirmation.required' => 'A confirmação da nova senha é obrigatório',
                'new_password_confirmation.same' => 'A confirmação da nova senha deve ser igual a nova senha',
            ]
        );

        // verifica se o token é valido
        $user = User::where('token', $request->token)->first();
        if (!$user) {
            return redirect()->route('login');
        }

        // atualizar a senha do user na base de dados
        $user->password = bcrypt($request->nee_password);
        $user->token = null;
        $user->save();

        return redirect()->route('login')->with([
            'success' => true
        ]);
    }
}

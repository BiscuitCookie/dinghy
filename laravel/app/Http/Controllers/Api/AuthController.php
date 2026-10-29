<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        abort_unless(config('app.registration_enabled', true), 403, 'Регистрация закрыта');

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));

        return response()->json([
            'token' => $user->createToken('spa')->plainTextToken,
            'user'  => new UserResource($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Неверные учётные данные'],
            ]);
        }

        $user = $request->user();
        $user->tokens()->where('name', 'spa')->delete();

        return response()->json([
            'token' => $user->createToken('spa')->plainTextToken,
            'user'  => new UserResource($user),
        ]);
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        return (new UserResource($request->user()))->response();
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($user) {
            $token = Password::broker()->createToken($user);
            $url = rtrim(config('app.url'), '/').'/app/reset-password/'.$token
                .'?email='.urlencode($user->email);

            Mail::to($user)->queue(new ResetPasswordMail($url, $user->name));
        }

        // Одинаковый ответ независимо от существования email — защита от перебора аккаунтов
        return response()->json([
            'message' => 'Если аккаунт с таким email существует, письмо со ссылкой отправлено',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'token'    => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            fn (User $user, string $password) => $user->update(['password' => Hash::make($password)]),
        );

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages([
                'token' => ['Неверный или просроченный токен сброса'],
            ]);
        }

        return response()->json(['message' => 'Пароль обновлён']);
    }
}

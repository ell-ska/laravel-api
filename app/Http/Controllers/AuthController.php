<?php

namespace App\Http\Controllers;

use App\Exceptions\AuthException;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9]+$/', 'min:3', 'unique:'.User::class],
            'password' => ['required', Rules\Password::defaults()],
        ]);

        User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'user registered',
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $request->username)->firstOrFail();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw new AuthException('invalid credentials', 400);
        }

        $token = $user->createToken('access-token');

        return response()->json([
            'message' => 'user authenticated',
            'access-token' => $token->plainTextToken,
        ], 200);
    }
}

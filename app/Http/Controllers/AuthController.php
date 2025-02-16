<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
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
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'validation failed',
                'errors' => $e->errors(),
            ], 400);
        } catch (Exception $e) {
            dump($e);

            return response()->json([
                'message' => 'failed to register user',
            ], 500);
        }
    }
}

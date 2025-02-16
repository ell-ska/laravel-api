<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'prompt' => ['required', 'string'],
        ]);

        $response = Http::post(env('LLM_URL'), [
            'model' => 'llama3.2:3b',
            'prompt' => $request->prompt,
            'stream' => false,
        ])->json();

        return response()->json($response['response']);
    }
}

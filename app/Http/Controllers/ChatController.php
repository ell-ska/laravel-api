<?php

namespace App\Http\Controllers;

use App\Exceptions\ChatException;
use App\Models\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'prompt' => ['required', 'string'],
        ]);

        $data = Http::post(env('LLM_URL').'/generate', [
            'model' => 'llama3.2:3b',
            'prompt' => $request->prompt,
            'stream' => false,
        ])->json();

        return response()->json($data['response']);
    }

    public function conversation(Request $request)
    {
        $request->validate([
            'prompt' => ['required', 'string'],
            'conversation_id' => ['nullable', 'uuid'],
        ]);

        $user = $request->user();
        $session = $request->conversation_id;

        if (! $session) {
            $session = Str::uuid();

            $data = Http::post(env('LLM_URL').'/generate', [
                'model' => 'llama3.2:3b',
                'prompt' => $request->prompt,
                'stream' => false,
            ])->json();

            History::create([
                'user_id' => $user->id,
                'conversation_id' => $session,
                'user_prompt' => $request->prompt,
                'llm_response' => $data['response'],
            ]);

            return response()->json([
                'response' => $data['response'],
                'message' => 'new session started',
                'conversation_id' => $session,
            ]);
        }

        $history = History::where([
            'user_id' => $user->id,
            'conversation_id' => $session,
        ])
            ->latest()
            ->get()
            ->map(fn ($chat) => [
                ['role' => 'user', 'content' => $chat->user_prompt],
                ['role' => 'assistant', 'content' => $chat->llm_response],
            ])
            ->flatten(1);

        if ($history->isEmpty()) {
            throw new ChatException('invalid conversation id', 400);
        }

        $messages = array_merge($history->toArray(), [
            ['role' => 'user', 'content' => $request->prompt],
        ]);

        $data = Http::post(env('LLM_URL').'/chat', [
            'model' => 'llama3.2:3b',
            'messages' => $messages,
            'stream' => false,
        ])->json();

        $llmResponse = $data['message']['content'];

        if (! $llmResponse) {
            throw new ChatException('no response recived', 500);
        }

        History::create([
            'user_id' => $user->id,
            'conversation_id' => $session,
            'user_prompt' => $request->prompt,
            'llm_response' => $llmResponse,
        ]);

        return response()->json([
            'response' => $llmResponse,
        ]);
    }
}

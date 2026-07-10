<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class AiChatController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        return Inertia::render('AiChat/Index', [
            'wallets' => $user->wallets()->where('is_active', true)->get(['id', 'name', 'currency']),
            'categories' => Category::where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))->get(['id', 'name', 'color', 'icon']),
        ]);
    }

    public function parse(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $parsed = $this->parseWithAi($request->message);

        return response()->json($parsed);
    }

    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|in:expense,income,transfer',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:255',
            'date' => 'required|date',
        ]);

        $user = Auth::user();
        $currency = strtoupper($validated['currency'] ?? $user->default_currency ?? 'PKR');
        $defaultCurrency = strtoupper($user->default_currency ?? 'PKR');

        $validated['currency'] = $currency;
        if ($currency !== $defaultCurrency) {
            $validated['converted_currency'] = $defaultCurrency;
        }

        $validated['ai_parsed'] = true;
        $transaction = $user->transactions()->create($validated);

        $delta = $validated['type'] === 'expense'
            ? -(float) ($validated['converted_amount'] ?? $validated['amount'])
            : (float) ($validated['converted_amount'] ?? $validated['amount']);
        $transaction->wallet()->increment('balance', $delta);

        return response()->json([
            'success' => true,
            'transaction' => $transaction->load(['category:id,name,color', 'wallet:id,name']),
        ]);
    }

    private function parseWithAi(string $message): array
    {
        $apiKey = config('services.openai.key');

        if (! $apiKey) {
            return $this->fallbackParse($message);
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Extract expense data from text. Return JSON: {"amount":number,"type":"expense|income","description":"string","category":"string","date":"YYYY-MM-DD"}. Use today\'s date if unspecified. Income keywords: salary,received,got,earned.',
                        ],
                        ['role' => 'user', 'content' => $message],
                    ],
                    'max_tokens' => 150,
                ]);

            $data = $response->json('choices.0.message.content');

            return json_decode($data, true) ?? $this->fallbackParse($message);
        } catch (\Exception $e) {
            Log::warning('AI parse failed', ['error' => $e->getMessage()]);

            return $this->fallbackParse($message);
        }
    }

    private function fallbackParse(string $message): array
    {
        preg_match('/(\d+(?:\.\d{1,2})?)/', $message, $matches);
        $amount = isset($matches[1]) ? (float) $matches[1] : 0;

        $type = preg_match('/salary|received|income|got|earned/i', $message) ? 'income' : 'expense';

        return [
            'amount' => $amount,
            'type' => $type,
            'description' => trim(preg_replace('/\d+(?:\.\d{1,2})?/', '', $message)),
            'category' => 'Other',
            'date' => now()->toDateString(),
        ];
    }
}

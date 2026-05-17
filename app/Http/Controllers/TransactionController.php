<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Entity;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Services\CurrencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $view = $request->input('view', 'list');
        if (! in_array($view, ['list', 'categories', 'merchants', 'timeline'])) {
            $view = 'list';
        }

        $applyFilters = function ($query) use ($request) {
            return $query
                ->when($request->type, fn ($q) => $q->where('type', $request->type))
                ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
                ->when($request->wallet_id, fn ($q) => $q->where('wallet_id', $request->wallet_id))
                ->when($request->merchant_id, fn ($q) => $q->where('merchant_id', $request->merchant_id))
                ->when($request->entity_id, fn ($q) => $q->where('entity_id', $request->entity_id))
                ->when($request->search, fn ($q) => $q->where('description', 'like', "%{$request->search}%"))
                ->when($request->date_from, fn ($q) => $q->whereDate('date', '>=', $request->date_from))
                ->when($request->date_to, fn ($q) => $q->whereDate('date', '<=', $request->date_to));
        };

        $sharedProps = [
            'view' => $view,
            'wallets' => $user->wallets()->get(['id', 'name', 'currency']),
            'categories' => Category::where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))->get(['id', 'name', 'color', 'icon']),
            'merchants' => Merchant::where('user_id', $user->id)->get(['id', 'name']),
            'trips' => $user->trips()->where('status', 'active')->get(['id', 'name', 'destination']),
            'entities' => $user->entities()->orderBy('name')->get(['id', 'name', 'type', 'color', 'emoji']),
            'filters' => $request->only(['type', 'category_id', 'wallet_id', 'merchant_id', 'entity_id', 'search', 'date_from', 'date_to', 'view']),
        ];

        if ($view === 'categories') {
            $totals = $applyFilters($user->transactions())
                ->whereNotNull('category_id')
                ->selectRaw("category_id, COUNT(*) as transaction_count, SUM(CASE WHEN type='expense' THEN COALESCE(converted_amount, amount) ELSE 0 END) as expense_amount, SUM(CASE WHEN type='income' THEN COALESCE(converted_amount, amount) ELSE 0 END) as income_amount")
                ->groupBy('category_id')
                ->get()
                ->keyBy('category_id');

            $categoryGroups = Category::where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))
                ->get(['id', 'name', 'color', 'icon', 'type'])
                ->map(fn ($cat) => array_merge($cat->toArray(), [
                    'transaction_count' => (int) ($totals[$cat->id]?->transaction_count ?? 0),
                    'expense_amount' => (float) ($totals[$cat->id]?->expense_amount ?? 0),
                    'income_amount' => (float) ($totals[$cat->id]?->income_amount ?? 0),
                ]))
                ->filter(fn ($cat) => $cat['transaction_count'] > 0)
                ->sortByDesc('transaction_count')
                ->values();

            return Inertia::render('Transactions/Index', array_merge($sharedProps, [
                'categoryGroups' => $categoryGroups,
            ]));
        }

        if ($view === 'merchants') {
            $totals = $applyFilters($user->transactions())
                ->whereNotNull('merchant_id')
                ->selectRaw("merchant_id, COUNT(*) as transaction_count, SUM(CASE WHEN type='expense' THEN COALESCE(converted_amount, amount) ELSE 0 END) as expense_amount, SUM(CASE WHEN type='income' THEN COALESCE(converted_amount, amount) ELSE 0 END) as income_amount")
                ->groupBy('merchant_id')
                ->get()
                ->keyBy('merchant_id');

            $merchantGroups = Merchant::where('user_id', $user->id)
                ->with('category:id,name,color')
                ->get(['id', 'name', 'logo', 'category_id'])
                ->map(fn ($m) => array_merge($m->toArray(), [
                    'transaction_count' => (int) ($totals[$m->id]?->transaction_count ?? 0),
                    'expense_amount' => (float) ($totals[$m->id]?->expense_amount ?? 0),
                    'income_amount' => (float) ($totals[$m->id]?->income_amount ?? 0),
                ]))
                ->filter(fn ($m) => $m['transaction_count'] > 0)
                ->sortByDesc('transaction_count')
                ->values();

            return Inertia::render('Transactions/Index', array_merge($sharedProps, [
                'merchantGroups' => $merchantGroups,
            ]));
        }

        if ($view === 'timeline') {
            return Inertia::render('Transactions/Index', array_merge($sharedProps, [
                'timelineItems' => Inertia::scroll(fn () => $applyFilters($user->transactions())
                    ->with(['category:id,name,color,icon', 'wallet:id,name,currency', 'merchant:id,name', 'entity:id,name,color,emoji'])
                    ->orderByDesc('date')
                    ->orderByDesc('created_at')
                    ->paginate(20, ['id', 'type', 'amount', 'currency', 'converted_amount', 'description', 'date', 'created_at', 'category_id', 'wallet_id', 'merchant_id', 'location', 'entity_id'])
                ),
            ]));
        }

        $transactions = $applyFilters($user->transactions())
            ->with(['category:id,name,color,icon', 'wallet:id,name,currency', 'merchant:id,name', 'entity:id,name,color,emoji'])
            ->orderByDesc('date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Transactions/Index', array_merge($sharedProps, [
            'transactions' => $transactions,
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'category_id' => 'nullable|exists:categories,id',
            'trip_id' => 'nullable|exists:trips,id',
            'entity_id' => 'nullable|exists:entities,id',
            'type' => 'required|in:expense,income,transfer',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'date' => 'required|date',
            'location' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $txCurrency = strtoupper($validated['currency'] ?? $user->default_currency ?? 'PKR');
        $defaultCurrency = strtoupper($user->default_currency ?? 'PKR');

        $validated['currency'] = $txCurrency;

        if ($txCurrency !== $defaultCurrency) {
            $validated['converted_amount'] = CurrencyService::convert((float) $validated['amount'], $txCurrency, $defaultCurrency);
            $validated['converted_currency'] = $defaultCurrency;
            $validated['exchange_rate'] = CurrencyService::rate($txCurrency, $defaultCurrency);
        } else {
            $validated['converted_amount'] = $validated['amount'];
            $validated['converted_currency'] = $defaultCurrency;
            $validated['exchange_rate'] = 1.0;
        }

        $transaction = $user->transactions()->create($validated);

        $this->updateWalletBalance($transaction);

        return back()->with('success', 'Transaction added.');
    }

    public function export(Request $request): HttpResponse
    {
        $user = Auth::user();

        $transactions = $user->transactions()
            ->with(['category:id,name', 'wallet:id,name', 'merchant:id,name'])
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->wallet_id, fn ($q) => $q->where('wallet_id', $request->wallet_id))
            ->when($request->search, fn ($q) => $q->where('description', 'like', "%{$request->search}%"))
            ->when($request->date_from, fn ($q) => $q->whereDate('date', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('date', '<=', $request->date_to))
            ->orderByDesc('date')
            ->get();

        $lines = [];
        $lines[] = implode(',', ['Date', 'Type', 'Amount', 'Currency', 'Converted Amount', 'Description', 'Category', 'Wallet', 'Merchant', 'Location', 'Notes']);

        foreach ($transactions as $tx) {
            $lines[] = implode(',', [
                $tx->date?->toDateString() ?? '',
                $tx->type,
                $tx->amount,
                $tx->currency ?? '',
                $tx->converted_amount ?? $tx->amount,
                '"' . str_replace('"', '""', $tx->description ?? '') . '"',
                '"' . str_replace('"', '""', $tx->category?->name ?? '') . '"',
                '"' . str_replace('"', '""', $tx->wallet?->name ?? '') . '"',
                '"' . str_replace('"', '""', $tx->merchant?->name ?? '') . '"',
                '"' . str_replace('"', '""', $tx->location ?? '') . '"',
                '"' . str_replace('"', '""', $tx->notes ?? '') . '"',
            ]);
        }

        $csv = implode("\n", $lines);
        $filename = 'transactions-' . now()->format('Y-m-d') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        Gate::authorize('delete', $transaction);
        $transaction->delete();

        return back()->with('success', 'Transaction deleted.');
    }

    private function updateWalletBalance(Transaction $transaction): void
    {
        $delta = $transaction->type === 'expense' ? -$transaction->amount : $transaction->amount;
        $transaction->wallet()->increment('balance', $delta);
    }
}

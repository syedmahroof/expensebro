<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Entity;
use App\Models\Merchant;
use App\Models\Transaction;
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
                ->when($request->currency, fn ($q) => $q->where('currency', $request->currency))
                ->when($request->search, fn ($q) => $q->where('description', 'like', "%{$request->search}%"))
                ->when($request->date_from, fn ($q) => $q->whereDate('date', '>=', $request->date_from))
                ->when($request->date_to, fn ($q) => $q->whereDate('date', '<=', $request->date_to));
        };

        $sharedProps = [
            'wallets' => $user->wallets()->where('is_active', true)->get(['id', 'name', 'currency']),
            'categories' => Category::where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))->get(['id', 'name', 'color', 'icon']),
            'merchants' => Merchant::where('user_id', $user->id)->get(['id', 'name']),
            'trips' => $user->trips()->get(['id', 'name', 'destination']),
            'entities' => Entity::where('user_id', $user->id)->get(['id', 'name']),
            'transactionCurrencies' => Transaction::distinct()->pluck('currency')->filter()->values(),
            'filters' => $request->only(['type', 'category_id', 'wallet_id', 'merchant_id', 'entity_id', 'currency', 'search', 'date_from', 'date_to', 'view']),
        ];

        if ($view === 'categories') {
            $totals = $applyFilters($user->transactions())
                ->whereNotNull('category_id')
                ->selectRaw("category_id, currency, COUNT(*) as transaction_count, SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) as expense_amount, SUM(CASE WHEN type='income' THEN amount ELSE 0 END) as income_amount")
                ->groupBy('category_id', 'currency')
                ->get()
                ->groupBy('category_id');

            $categoryGroups = Category::where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))
                ->get(['id', 'name', 'color', 'icon', 'type'])
                ->map(function ($cat) use ($totals) {
                    $stats = $totals->get($cat->id, collect());

                    return array_merge($cat->toArray(), [
                        'transaction_count' => (int) $stats->sum('transaction_count'),
                        'expense_amount' => $stats->pluck('expense_amount', 'currency')->toArray(),
                        'income_amount' => $stats->pluck('income_amount', 'currency')->toArray(),
                    ]);
                })
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
                ->selectRaw("merchant_id, currency, COUNT(*) as transaction_count, SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) as expense_amount, SUM(CASE WHEN type='income' THEN amount ELSE 0 END) as income_amount")
                ->groupBy('merchant_id', 'currency')
                ->get()
                ->groupBy('merchant_id');

            $merchantGroups = Merchant::where('user_id', $user->id)
                ->with('category:id,name,color')
                ->get(['id', 'name', 'logo', 'category_id'])
                ->map(function ($m) use ($totals) {
                    $stats = $totals->get($m->id, collect());

                    return array_merge($m->toArray(), [
                        'transaction_count' => (int) $stats->sum('transaction_count'),
                        'expense_amount' => $stats->pluck('expense_amount', 'currency')->toArray(),
                        'income_amount' => $stats->pluck('income_amount', 'currency')->toArray(),
                    ]);
                })
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
            ->when($request->currency, fn ($q) => $q->where('currency', $request->currency))
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
                '"'.str_replace('"', '""', $tx->description ?? '').'"',
                '"'.str_replace('"', '""', $tx->category?->name ?? '').'"',
                '"'.str_replace('"', '""', $tx->wallet?->name ?? '').'"',
                '"'.str_replace('"', '""', $tx->merchant?->name ?? '').'"',
                '"'.str_replace('"', '""', $tx->location ?? '').'"',
                '"'.str_replace('"', '""', $tx->notes ?? '').'"',
            ]);
        }

        $csv = implode("\n", $lines);
        $filename = 'transactions-'.now()->format('Y-m-d').'.csv';

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

<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupExpense;
use App\Models\GroupExpenseSplit;
use App\Services\CurrencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function index(): Response
    {
        $groups = Auth::user()->groups()
            ->withCount('members')
            ->with(['expenses' => fn ($q) => $q->select('id', 'group_id', 'converted_amount', 'amount')])
            ->latest()
            ->get()
            ->map(fn (Group $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'currency' => $group->currency,
                'members_count' => $group->members_count,
                'total_expenses' => (float) $group->expenses->sum(fn ($e) => $e->converted_amount ?? $e->amount),
                'created_at' => $group->created_at,
            ]);

        return Inertia::render('Groups/Index', [
            'groups' => $groups,
            'currencies' => CurrencyService::supported(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'currency' => ['nullable', 'string', 'max:10'],
            'members' => ['required', 'array', 'min:1'],
            'members.*.name' => ['required', 'string', 'max:255'],
            'members.*.phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user = Auth::user();
        $currency = strtoupper($validated['currency'] ?? $user->default_currency ?? 'PKR');

        $group = $user->groups()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'currency' => $currency,
        ]);

        foreach ($validated['members'] as $member) {
            $group->members()->create([
                'name' => $member['name'],
                'phone' => $member['phone'] ?? null,
            ]);
        }

        return redirect()->route('groups.show', $group);
    }

    public function show(Group $group): Response
    {
        Gate::authorize('view', $group);

        $group->load(['members', 'expenses.paidByMember', 'expenses.splits.member']);

        $expenses = $group->expenses->map(fn (GroupExpense $expense) => [
            'id' => $expense->id,
            'description' => $expense->description,
            'amount' => $expense->amount,
            'currency' => $expense->currency,
            'converted_amount' => $expense->converted_amount,
            'date' => $expense->date->toDateString(),
            'notes' => $expense->notes,
            'paid_by_member_id' => $expense->paid_by_member_id,
            'paid_by_name' => $expense->paidByMember?->name,
            'splits' => $expense->splits->map(fn (GroupExpenseSplit $split) => [
                'id' => $split->id,
                'group_member_id' => $split->group_member_id,
                'member_name' => $split->member?->name,
                'amount' => $split->amount,
                'settled_at' => $split->settled_at,
            ]),
        ]);

        return Inertia::render('Groups/Show', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'currency' => $group->currency,
            ],
            'members' => $group->members->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'phone' => $m->phone]),
            'expenses' => $expenses,
            'balances' => $group->balances(),
            'currencies' => CurrencyService::supported(),
            'defaultCurrency' => Auth::user()->default_currency ?? 'PKR',
        ]);
    }

    public function destroy(Group $group): RedirectResponse
    {
        Gate::authorize('delete', $group);

        $group->delete();

        return redirect()->route('groups.index');
    }

    public function storeExpense(Request $request, Group $group): RedirectResponse
    {
        Gate::authorize('update', $group);

        $validated = $request->validate([
            'paid_by_member_id' => ['required', 'integer', 'exists:group_members,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:10'],
            'description' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'splits' => ['required', 'array', 'min:1'],
            'splits.*.group_member_id' => ['required', 'integer', 'exists:group_members,id'],
            'splits.*.amount' => ['required', 'numeric', 'min:0'],
        ]);

        $user = Auth::user();
        $currency = strtoupper($validated['currency'] ?? $group->currency);
        $defaultCurrency = strtoupper($user->default_currency ?? 'PKR');

        $convertedAmount = $currency !== $defaultCurrency
            ? CurrencyService::convert((float) $validated['amount'], $currency, $defaultCurrency)
            : $validated['amount'];

        $expense = $group->expenses()->create([
            'paid_by_member_id' => $validated['paid_by_member_id'],
            'amount' => $validated['amount'],
            'currency' => $currency,
            'converted_amount' => $convertedAmount,
            'description' => $validated['description'],
            'date' => $validated['date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['splits'] as $split) {
            $expense->splits()->create([
                'group_member_id' => $split['group_member_id'],
                'amount' => $split['amount'],
            ]);
        }

        return back();
    }

    public function destroyExpense(Group $group, GroupExpense $expense): RedirectResponse
    {
        Gate::authorize('update', $group);

        $expense->delete();

        return back();
    }

    public function settleSplit(Group $group, GroupExpenseSplit $split): RedirectResponse
    {
        Gate::authorize('update', $group);

        $split->update(['settled_at' => $split->settled_at ? null : now()]);

        return back();
    }
}

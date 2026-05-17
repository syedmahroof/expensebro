<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EntityController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        $entities = $user->entities()
            ->withCount('transactions')
            ->withSum(['transactions as total_spent' => fn ($q) => $q->where('type', 'expense')], 'converted_amount')
            ->orderBy('name')
            ->get();

        return Inertia::render('Entities/Index', [
            'entities' => $entities,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:vehicle,office,team,house,children,property,other',
            'color' => 'required|string|max:7',
            'emoji' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:500',
        ]);

        Auth::user()->entities()->create($validated);

        return back()->with('success', 'Entity created.');
    }

    public function update(Request $request, Entity $entity): RedirectResponse
    {
        Gate::authorize('update', $entity);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:vehicle,office,team,house,children,property,other',
            'color' => 'required|string|max:7',
            'emoji' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:500',
        ]);

        $entity->update($validated);

        return back()->with('success', 'Entity updated.');
    }

    public function destroy(Entity $entity): RedirectResponse
    {
        Gate::authorize('delete', $entity);
        $entity->delete();

        return back()->with('success', 'Entity deleted.');
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EntityResource;
use App\Models\Entity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class EntityController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $entities = $request->user()->entities()
            ->withCount('transactions')
            ->withSum(['transactions as total_spent' => fn ($q) => $q->where('type', 'expense')], 'converted_amount')
            ->orderBy('name')
            ->get();

        return EntityResource::collection($entities);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateEntity($request);

        $entity = $request->user()->entities()->create($validated);

        return (new EntityResource($entity))->response()->setStatusCode(201);
    }

    public function update(Request $request, Entity $entity): EntityResource
    {
        Gate::authorize('update', $entity);

        $validated = $this->validateEntity($request);
        $entity->update($validated);

        return new EntityResource($entity->refresh());
    }

    public function destroy(Entity $entity): JsonResponse
    {
        Gate::authorize('delete', $entity);
        $entity->delete();

        return response()->json(['message' => 'Entity deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEntity(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:vehicle,office,team,house,children,property,other'],
            'color' => ['required', 'string', 'max:7'],
            'emoji' => ['nullable', 'string', 'max:10'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
    }
}

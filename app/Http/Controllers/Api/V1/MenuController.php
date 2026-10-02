<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreMenuRequest;
use App\Http\Requests\Api\V1\UpdateMenuRequest;
use App\Http\Resources\MenuResource;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Menu::class);

        $query = Menu::query()->orderBy('name');

        if (request()->filled('q')) {
            $search = '%'.request()->string('q')->trim()->toString().'%';
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', $search)->orWhere('code', 'like', $search);
            });
        }

        if (request()->has('active')) {
            $query->where('active', request()->boolean('active'));
        }

        $perPage = min(max(request()->integer('per_page', 20), 1), 100);

        return MenuResource::collection($query->paginate($perPage));
    }

    public function store(StoreMenuRequest $request): JsonResponse
    {
        Gate::authorize('create', Menu::class);

        $menu = Menu::query()->create($request->validated());

        return (new MenuResource($menu->refresh()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Menu $menu): MenuResource
    {
        Gate::authorize('view', $menu);

        return new MenuResource($menu);
    }

    public function update(UpdateMenuRequest $request, Menu $menu): MenuResource
    {
        Gate::authorize('update', $menu);

        $menu->update($request->validated());

        return new MenuResource($menu->refresh());
    }
}

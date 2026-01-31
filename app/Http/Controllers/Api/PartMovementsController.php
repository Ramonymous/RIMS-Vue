<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PartMovementResource;
use App\Models\PartMovements;
use Illuminate\Http\Request;

class PartMovementsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        // Build cache key from request parameters
        $cacheKey = 'part_movements_'
            .($request->start_date ?? 'no_start').'_'
            .($request->end_date ?? 'no_end').'_'
            .($request->type ?? 'all').'_'
            .($request->part_number ?? 'all');

        // Cache for 5 minutes (matches your script frequency)
        $movements = cache()->remember($cacheKey, 300, function () use ($request) {
            $query = PartMovements::with('part:id,part_number');

            // Optional filters
            if ($request->has('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('part_number')) {
                $query->whereHas('part', function ($q) use ($request) {
                    $q->where('part_number', 'like', '%'.$request->part_number.'%');
                });
            }

            // Order by created_at descending (latest first)
            $query->orderBy('created_at', 'desc');

            return $query->get();
        });

        return response()->json([
            'success' => true,
            'count' => $movements->count(),
            'data' => PartMovementResource::collection($movements),
        ])
            ->header('Cache-Control', 'public, max-age=300'); // Tell Cloudflare to cache for 5 min
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

<?php

namespace App\Http\Controllers;

use App\Exports\StockReportExport;
use App\Models\PartMovements;
use App\Models\Parts;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockController extends Controller
{
    public function __construct(
        private AuthorizationService $authService
    ) {}

    public function index(Request $request): Response
    {
        $partsQuery = Parts::query()
            ->select([
                'id',
                'part_number',
                'part_name',
                'stock',
                'is_active',
                'created_at',
            ]);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $partsQuery->where(function ($query) use ($search) {
                $query->where('part_number', 'LIKE', "%{$search}%")
                    ->orWhere('part_name', 'LIKE', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $partsQuery->where('is_active', $request->status === 'active');
        }

        $parts = $partsQuery
            ->orderBy('part_number')
            ->paginate(50)
            ->withQueryString();

        $movementsQuery = PartMovements::query()
            ->with([
                'part:id,part_number,part_name',
                'reference',
            ])
            ->select([
                'id',
                'part_id',
                'stock_before',
                'type',
                'qty',
                'stock_after',
                'reference_type',
                'reference_id',
                'created_at',
            ]);

        // Movement search filter
        if ($request->filled('movement_search')) {
            $search = $request->movement_search;
            $movementsQuery->whereHas('part', function ($query) use ($search) {
                $query->where('part_number', 'LIKE', "%{$search}%")
                    ->orWhere('part_name', 'LIKE', "%{$search}%");
            });
        }

        // Movement type filter
        if ($request->filled('movement_type') && $request->movement_type !== 'all') {
            $movementsQuery->where('type', $request->movement_type);
        }

        // Movement date range filter
        if ($request->filled('movement_start_date')) {
            $movementsQuery->whereDate('created_at', '>=', $request->movement_start_date);
        }

        if ($request->filled('movement_end_date')) {
            $movementsQuery->whereDate('created_at', '<=', $request->movement_end_date);
        }

        $movements = $movementsQuery
            ->latest()
            ->paginate(50, ['*'], 'movements_page')
            ->withQueryString();

        return Inertia::render('stock/Index', [
            'parts' => $parts,
            'movements' => $movements,
            'filters' => $request->only(['search', 'status', 'movement_search', 'movement_type', 'movement_start_date', 'movement_end_date']),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authService->authorize(auth()->user(), ['admin', 'manager', 'receiving', 'outgoing', 'part_gr', 'part_gi']);

        $filters = $request->only(['status', 'search', 'stock_level']);

        $filename = 'stock-report-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new StockReportExport($filters), $filename);
    }
}

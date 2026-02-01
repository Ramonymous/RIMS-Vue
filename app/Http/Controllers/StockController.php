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

    public function index(): Response
    {
        $parts = Parts::query()
            ->select([
                'id',
                'part_number',
                'part_name',
                'stock',
                'is_active',
                'created_at',
            ])
            ->orderBy('part_number')
            ->paginate(50)
            ->withQueryString();

        $movements = PartMovements::query()
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
            ])
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('stock/Index', [
            'parts' => $parts,
            'movements' => $movements,
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

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequestRequest;
use App\Http\Requests\UpdateRequestRequest;
use App\Models\OutgoingItems;
use App\Models\Outgoings;
use App\Models\PartMovements;
use App\Models\Parts;
use App\Models\RequestLists;
use App\Models\Requests;
use App\Services\AuthorizationService;
use App\Services\RequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RequestsController extends Controller
{
    private const ITEMS_PER_PAGE = 20;

    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        private RequestService $requestService,
        private AuthorizationService $authService
    ) {}

    /**
     * Get next available request number
     */
    private function getNextRequestNumber(): int
    {
        $lastRequest = Requests::query()
            ->select('request_number')
            ->orderByDesc('id')
            ->first();

        return $lastRequest ? ((int) $lastRequest->request_number) + 1 : 1;
    }

    public function index(): Response
    {
        // Fetch RequestLists items with optimized eager loading
        $requestItems = RequestLists::query()
            ->select(['id', 'request_id', 'part_id', 'qty', 'is_urgent', 'is_supplied', 'created_at'])
            ->with([
                'request:id,request_number,requested_by,requested_at,destination',
                'request.requestedBy:id,name',
                'part:id,part_number,part_name,stock',
            ])
            ->whereHas('request', fn ($q) => $q->where('status', 'completed'))
            ->where('is_supplied', false)
            ->orderByDesc('is_urgent')
            ->orderByDesc('created_at')
            ->paginate(self::ITEMS_PER_PAGE)
            ->withQueryString();

        return Inertia::render('requests/Index', [
            'requestItems' => $requestItems,
        ]);
    }

    public function create(): Response
    {
        // Use cache for parts list (rarely changes during active session)
        $parts = cache()->remember(
            'active_parts',
            self::CACHE_TTL,
            fn () => Parts::active()
                ->select(['id', 'part_number', 'part_name', 'stock'])
                ->orderBy('part_number')
                ->get()
        );

        // Optimized query for pending requests with proper grouping
        $pendingRequests = RequestLists::query()
            ->select(['request_lists.part_id', 'requests.destination', 'parts.part_number'])
            ->join('requests', 'request_lists.request_id', '=', 'requests.id')
            ->join('parts', 'request_lists.part_id', '=', 'parts.id')
            ->where('request_lists.is_supplied', false)
            ->where('requests.status', 'completed')
            ->get()
            ->groupBy('destination')
            ->map(fn ($items) => $items->pluck('part_number')->unique()->values()->toArray());

        return Inertia::render('requests/Create', [
            'parts' => $parts,
            'nextRequestNumber' => $this->getNextRequestNumber(),
            'pendingRequests' => $pendingRequests,
        ]);
    }

    public function store(StoreRequestRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['requested_by'] = auth()->id();

            // Auto-generate request number if not provided
            if (empty($data['request_number'])) {
                $data['request_number'] = $this->getNextRequestNumber();
            }

            $requestRecord = $this->requestService->create($data);

            // Clear parts cache on new request (stock might have changed)
            cache()->forget('active_parts');

            $message = $data['status'] === 'completed'
                ? 'Request confirmed successfully.'
                : 'Request saved as draft successfully.';

            // Return JSON for AJAX requests
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => ['request_number' => $requestRecord->request_number],
                ]);
            }

            // Redirect back to create page for continuous workflow
            return redirect()->route('requests.create')->with('success', $message);
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create request: '.$e->getMessage(),
                ], 422);
            }

            return redirect()->back()
                ->with('error', 'Failed to create request: '.$e->getMessage())
                ->withInput();
        }
    }

    // public function edit(Requests $request): Response
    // {
    //     if ($request->status === 'cancelled') {
    //         abort(403, 'Cannot edit cancelled request.');
    //     }

    //     $request->load(['items.part:id,part_number,part_name,stock']);

    //     // Reuse cached parts list
    //     $parts = cache()->remember(
    //         'active_parts',
    //         self::CACHE_TTL,
    //         fn () => Parts::active()
    //             ->select(['id', 'part_number', 'part_name', 'stock'])
    //             ->orderBy('part_number')
    //             ->get()
    //     );

    //     return Inertia::render('requests/Edit', [
    //         'request' => $request,
    //         'parts' => $parts,
    //     ]);
    // }

    public function update(UpdateRequestRequest $updateRequest, Requests $request): RedirectResponse
    {
        if ($request->status === 'cancelled') {
            abort(403, 'Cannot edit cancelled request.');
        }

        try {
            $data = $updateRequest->validated();
            $this->requestService->update($request, $data);

            // Clear cache as stock might have changed
            cache()->forget('active_parts');

            return redirect()->route('requests.index')->with('success', 'Request updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update request: '.$e->getMessage())
                ->withInput();
        }
    }

    public function cancel(Requests $request): RedirectResponse
    {
        try {
            $this->requestService->cancel($request);

            return redirect('/requests')->with('success', 'Request cancelled successfully.');
        } catch (\Exception $e) {
            return redirect('/requests')->with('error', 'Failed to cancel request: '.$e->getMessage());
        }
    }

    public function supply(Request $request, RequestLists $requestItem): RedirectResponse
    {
        $request->validate([
            'scanned_part_number' => 'required|string',
            'qty' => 'required|integer|min:1',
        ]);

        try {
            DB::transaction(function () use ($request, $requestItem) {
                // Load the part relationship
                $requestItem->load('part', 'request');

                // Validate scanned part number matches
                $scannedPartNumber = strtoupper(trim($request->input('scanned_part_number')));
                $expectedPartNumber = strtoupper(trim($requestItem->part->part_number));

                if ($scannedPartNumber !== $expectedPartNumber) {
                    throw new \Exception("Part number mismatch. Expected: {$expectedPartNumber}, Got: {$scannedPartNumber}");
                }

                // Get supplied quantity (default to requested qty if not provided)
                $suppliedQty = $request->input('qty', $requestItem->qty);

                // Validate quantity
                if ($suppliedQty > $requestItem->qty) {
                    throw new \Exception("Supply quantity ({$suppliedQty}) cannot exceed requested quantity ({$requestItem->qty})");
                }

                // Check if sufficient stock
                if ($requestItem->part->stock < $suppliedQty) {
                    throw new \Exception("Insufficient stock for {$requestItem->part->part_number}. Available: {$requestItem->part->stock}, Required: {$suppliedQty}");
                }

                // Generate outgoing document number
                $now = now();
                $prefix = 'OUT-'.$now->format('dmy');

                $lastDoc = Outgoings::query()
                    ->where('doc_number', 'LIKE', $prefix.'%')
                    ->orderByDesc('id')
                    ->first();

                $nextNumber = 1;
                if ($lastDoc) {
                    $parts = explode('-', $lastDoc->doc_number);
                    $lastNumber = (int) end($parts);
                    $nextNumber = $lastNumber + 1;
                }

                $docNumber = sprintf('%s-%03d', $prefix, $nextNumber);

                // Create outgoing record
                $outgoing = Outgoings::create([
                    'doc_number' => $docNumber,
                    'issued_by' => auth()->id(),
                    'issued_at' => now(),
                    'status' => 'completed', // Auto-complete since it's from supply
                    'notes' => "Auto-generated from request #{$requestItem->request->request_number} - {$requestItem->request->destination}",
                ]);

                // Create outgoing item
                OutgoingItems::create([
                    'outgoing_id' => $outgoing->id,
                    'part_id' => $requestItem->part_id,
                    'qty' => $suppliedQty,
                ]);

                // Update part stock
                $stockBefore = $requestItem->part->stock;
                $stockAfter = $stockBefore - $suppliedQty;

                $requestItem->part->update([
                    'stock' => $stockAfter,
                ]);

                // Create part movement record
                PartMovements::create([
                    'part_id' => $requestItem->part_id,
                    'stock_before' => $stockBefore,
                    'type' => 'out',
                    'qty' => $suppliedQty,
                    'stock_after' => $stockAfter,
                    'reference_type' => Outgoings::class,
                    'reference_id' => $outgoing->id,
                ]);

                // Mark request item as supplied
                $requestItem->update(['is_supplied' => true]);
            });

            return redirect()->route('requests.index')->with('success', 'Item supplied successfully. Outgoing created.');
        } catch (\Exception $e) {
            return redirect()->route('requests.index')
                ->with('error', 'Failed to supply item: '.$e->getMessage());
        }
    }

    public function pickCommand(Request $request, RequestLists $requestItem): RedirectResponse
    {
        $requestItem->load([
            'part:id,part_number,part_name,address',
            'request:id,request_number,destination,requested_at',
        ]);

        cache()->put(
            'pick_command',
            $requestItem->part->part_number,
            now()->addSeconds(self::CACHE_TTL)
        );

        return redirect()->back()->with('success', 'Pick command sent.');
    }
}

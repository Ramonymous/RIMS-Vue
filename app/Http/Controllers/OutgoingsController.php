<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOutgoingRequest;
use App\Http\Requests\UpdateOutgoingRequest;
use App\Models\Outgoings;
use App\Models\Parts;
use App\Services\AuthorizationService;
use App\Services\OutgoingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OutgoingsController extends Controller
{
    public function __construct(
        private OutgoingService $outgoingService,
        private AuthorizationService $authService
    ) {}

    /**
     * Generate the next document number based on current date and existing records.
     */
    private function getNextDocNumber(): string
    {
        $now = now();
        $prefix = 'OUT-'.$now->format('dmy');

        $lastDoc = Outgoings::query()
            ->where('doc_number', 'LIKE', $prefix.'%')
            ->orderByDesc('id')
            ->first();

        if ($lastDoc) {
            // Extract the sequential number from doc_number (e.g., "OUT-310126-005" -> 5)
            $parts = explode('-', $lastDoc->doc_number);
            $lastNumber = (int) end($parts);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%03d', $prefix, $nextNumber);
    }

    public function index(Request $request): Response
    {
        $query = Outgoings::query()
            ->with(['issuedBy:id,name', 'items.part:id,part_number,part_name,stock'])
            ->select([
                'id',
                'doc_number',
                'issued_by',
                'issued_at',
                'status',
                'is_gi',
                'created_at',
            ]);

        // Server-side search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('doc_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('issuedBy', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        $outgoings = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('outgoings/Index', [
            'outgoings' => $outgoings,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        $this->authService->authorize(
            auth()->user(),
            ['admin', 'outgoing', 'manager', 'part_gi'],
            'You do not have permission to create outgoings.'
        );

        $parts = Parts::active()
            ->select(['id', 'part_number', 'part_name', 'stock'])
            ->orderBy('part_number')
            ->get();

        return Inertia::render('outgoings/Create', [
            'parts' => $parts,
            'nextDocNumber' => $this->getNextDocNumber(),
        ]);
    }

    public function store(StoreOutgoingRequest $request): RedirectResponse
    {
        $this->authService->authorize(
            auth()->user(),
            ['admin', 'outgoing', 'manager', 'part_gi'],
            'You do not have permission to create outgoings.'
        );

        try {
            $data = $request->validated();
            $data['issued_by'] = auth()->id();

            $outgoing = $this->outgoingService->create($data);

            $message = $data['status'] === 'completed'
                ? 'Outgoing confirmed successfully.'
                : 'Outgoing saved as draft successfully.';

            return redirect('/outgoings')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create outgoing: '.$e->getMessage())
                ->withInput();
        }
    }

    public function edit(Outgoings $outgoing): Response
    {
        $this->authService->authorize(
            auth()->user(),
            ['admin', 'outgoing', 'manager', 'part_gi'],
            'You do not have permission to edit outgoings.'
        );

        if (! $outgoing->isEditable()) {
            abort(403, 'Cannot edit this outgoing.');
        }

        $outgoing->load(['items.part:id,part_number,part_name,stock']);

        $parts = Parts::active()
            ->select(['id', 'part_number', 'part_name', 'stock'])
            ->orderBy('part_number')
            ->get();

        return Inertia::render('outgoings/Edit', [
            'outgoing' => $outgoing,
            'parts' => $parts,
        ]);
    }

    public function update(UpdateOutgoingRequest $request, Outgoings $outgoing): RedirectResponse
    {
        $this->authService->authorize(
            auth()->user(),
            ['admin', 'outgoing', 'manager', 'part_gi'],
            'You do not have permission to edit outgoings.'
        );

        try {
            $this->outgoingService->update($outgoing, $request->validated());

            return redirect('/outgoings')
                ->with('success', 'Outgoing updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update outgoing: '.$e->getMessage())
                ->withInput();
        }
    }

    public function cancel(Outgoings $outgoing): RedirectResponse
    {
        try {
            $this->outgoingService->cancel($outgoing);

            return redirect('/outgoings')
                ->with('success', 'Outgoing cancelled successfully.');
        } catch (\Exception $e) {
            return redirect('/outgoings')
                ->with('error', 'Failed to cancel outgoing: '.$e->getMessage());
        }
    }

    public function updateGiStatus(Outgoings $outgoing): RedirectResponse
    {
        try {
            $this->outgoingService->toggleGiStatus($outgoing);

            return redirect('/outgoings')
                ->with('success', 'GI status updated successfully.');
        } catch (\Exception $e) {
            return redirect('/outgoings')
                ->with('error', 'Failed to update GI status: '.$e->getMessage());
        }
    }
}

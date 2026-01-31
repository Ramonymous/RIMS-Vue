<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReceivingRequest;
use App\Http\Requests\UpdateReceivingRequest;
use App\Models\Parts;
use App\Models\Receivings;
use App\Services\AuthorizationService;
use App\Services\ReceivingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReceivingsController extends Controller
{
    public function __construct(
        private ReceivingService $receivingService,
        private AuthorizationService $authService
    ) {}

    public function index(): Response
    {
        $receivings = Receivings::query()
            ->with(['receivedBy:id,name', 'items.part:id,part_number,part_name,stock'])
            ->select([
                'id',
                'doc_number',
                'received_by',
                'received_at',
                'status',
                'is_gr',
                'created_at',
            ])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('receivings/Index', [
            'receivings' => $receivings,
        ]);
    }

    public function create(): Response
    {
        $this->authService->authorize(
            auth()->user(),
            ['admin', 'receiving', 'manager', 'part_gr'],
            'You do not have permission to create receivings.'
        );

        $parts = Parts::active()
            ->select(['id', 'part_number', 'part_name', 'stock'])
            ->orderBy('part_number')
            ->get();

        return Inertia::render('receivings/Create', [
            'parts' => $parts,
        ]);
    }

    public function store(StoreReceivingRequest $request): RedirectResponse
    {
        $this->authService->authorize(
            auth()->user(),
            ['admin', 'receiving', 'manager', 'part_gr'],
            'You do not have permission to create receivings.'
        );

        try {
            $data = $request->validated();
            $data['received_by'] = auth()->id();

            $receiving = $this->receivingService->create($data);

            $message = $data['status'] === 'completed'
                ? 'Receiving confirmed successfully.'
                : 'Receiving saved as draft successfully.';

            return redirect('/receivings')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create receiving: '.$e->getMessage())
                ->withInput();
        }
    }

    public function edit(Receivings $receiving): Response
    {
        $this->authService->authorize(
            auth()->user(),
            ['admin', 'receiving', 'manager', 'part_gr'],
            'You do not have permission to edit receivings.'
        );

        if (! $receiving->isEditable()) {
            abort(403, 'Cannot edit this receiving.');
        }

        $receiving->load(['items.part:id,part_number,part_name,stock']);

        $parts = Parts::active()
            ->select(['id', 'part_number', 'part_name', 'stock'])
            ->orderBy('part_number')
            ->get();

        return Inertia::render('receivings/Edit', [
            'receiving' => $receiving,
            'parts' => $parts,
        ]);
    }

    public function update(UpdateReceivingRequest $request, Receivings $receiving): RedirectResponse
    {
        $this->authService->authorize(
            auth()->user(),
            ['admin', 'receiving', 'manager', 'part_gr'],
            'You do not have permission to edit receivings.'
        );

        try {
            $this->receivingService->update($receiving, $request->validated());

            return redirect('/receivings')
                ->with('success', 'Receiving updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update receiving: '.$e->getMessage())
                ->withInput();
        }
    }

    public function cancel(Receivings $receiving): RedirectResponse
    {
        try {
            $this->receivingService->cancel($receiving);

            return redirect('/receivings')
                ->with('success', 'Receiving cancelled successfully.');
        } catch (\Exception $e) {
            return redirect('/receivings')
                ->with('error', 'Failed to cancel receiving: '.$e->getMessage());
        }
    }

    public function updateGrStatus(Receivings $receiving): RedirectResponse
    {
        try {
            $this->receivingService->toggleGrStatus($receiving);

            return redirect('/receivings')
                ->with('success', 'GR status updated successfully.');
        } catch (\Exception $e) {
            return redirect('/receivings')
                ->with('error', 'Failed to update GR status: '.$e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Exports\PartsTemplateExport;
use App\Http\Requests\StorePartRequest;
use App\Http\Requests\UpdatePartRequest;
use App\Imports\PartsImport;
use App\Models\Parts;
use App\Services\AuthorizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PartsController extends Controller
{
    public function __construct(
        private AuthorizationService $authService
    ) {}

    public function index(): Response
    {
        $this->authService->authorize(auth()->user(), ['admin', 'manager']);

        $parts = Parts::query()
            ->select([
                'id',
                'part_number',
                'part_name',
                'customer_code',
                'supplier_code',
                'model',
                'variant',
                'standard_packing',
                'stock',
                'address',
                'is_active',
                'created_at',
            ])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('parts/Index', [
            'parts' => $parts,
        ]);
    }

    public function store(StorePartRequest $request): RedirectResponse
    {
        $this->authService->authorize(auth()->user(), ['admin', 'manager']);

        Parts::create($request->validated());

        return redirect()->route('parts.index')
            ->with('success', 'Part created successfully.');
    }

    public function update(UpdatePartRequest $request, Parts $part): RedirectResponse
    {
        $this->authService->authorize(auth()->user(), ['admin', 'manager']);

        $part->update($request->validated());

        return redirect()->route('parts.index')
            ->with('success', 'Part updated successfully.');
    }

    public function destroy(Parts $part): RedirectResponse
    {
        $this->authService->authorize(auth()->user(), ['admin', 'manager']);

        if ($part->hasTransactions()) {
            return redirect()->route('parts.index')
                ->with('error', 'Cannot delete part with existing transactions.');
        }

        $part->delete();

        return redirect()->route('parts.index')
            ->with('success', 'Part deleted successfully.');
    }

    public function exportTemplate(): BinaryFileResponse
    {
        $this->authService->authorize(auth()->user(), ['admin', 'manager']);

        return Excel::download(new PartsTemplateExport, 'parts_template.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authService->authorize(auth()->user(), ['admin', 'manager']);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        try {
            $import = new PartsImport;
            Excel::import($import, $request->file('file'));

            $failures = $import->failures();

            if ($failures->isNotEmpty()) {
                $errorMessages = $failures->map(function ($failure) {
                    return "Row {$failure->row()}: ".implode(', ', $failure->errors());
                })->take(5)->implode(' | ');

                $totalFailures = $failures->count();
                $message = $totalFailures > 5
                    ? "Import completed with {$totalFailures} errors. First 5: {$errorMessages}"
                    : "Import completed with errors: {$errorMessages}";

                return redirect()->route('parts.index')->with('warning', $message);
            }

            return redirect()->route('parts.index')
                ->with('success', 'Parts imported successfully.');
        } catch (\Exception $e) {
            return redirect()->route('parts.index')
                ->with('error', 'Import failed: '.$e->getMessage());
        }
    }
}

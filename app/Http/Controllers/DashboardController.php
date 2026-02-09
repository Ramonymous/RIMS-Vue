<?php

namespace App\Http\Controllers;

use App\Models\Outgoings;
use App\Models\PartMovements;
use App\Models\Parts;
use App\Models\Receivings;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $permissions = $user->permissions ?? [];

        // Key Performance Indicators - Single aggregate query
        $stats = Parts::selectRaw('
            COUNT(*) as total_parts,
            COUNT(CASE WHEN is_active = true THEN 1 END) as active_parts,
            COALESCE(SUM(stock), 0) as total_stock,
            COUNT(CASE WHEN stock < 10 AND stock > 0 THEN 1 END) as low_stock_count,
            COUNT(CASE WHEN stock = 0 THEN 1 END) as out_of_stock_count
        ')->first();

        // Receivings & Outgoings Stats - Combined queries
        $receivingStats = Receivings::selectRaw('
            COUNT(*) as total
        ')->first();
        
        $outgoingStats = Outgoings::selectRaw('
            COUNT(*) as total
        ')->first();

        $now = now();
        $currentStart = $now->copy()->subDays(6)->startOfDay();
        $previousStart = $now->copy()->subDays(13)->startOfDay();
        $previousEnd = $now->copy()->subDays(7)->endOfDay();

        $currentIncoming = PartMovements::query()
            ->where('type', 'in')
            ->whereBetween('created_at', [$currentStart, $now])
            ->sum('qty');

        $previousIncoming = PartMovements::query()
            ->where('type', 'in')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('qty');

        $currentOutgoing = PartMovements::query()
            ->where('type', 'out')
            ->whereBetween('created_at', [$currentStart, $now])
            ->sum('qty');

        $previousOutgoing = PartMovements::query()
            ->where('type', 'out')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('qty');

        $incomingTrend = $previousIncoming > 0
            ? (($currentIncoming - $previousIncoming) / $previousIncoming) * 100
            : ($currentIncoming > 0 ? 100 : 0);

        $outgoingTrend = $previousOutgoing > 0
            ? (($currentOutgoing - $previousOutgoing) / $previousOutgoing) * 100
            : ($currentOutgoing > 0 ? 100 : 0);

        // Daily Trends (last 30 days for chart)
        $dailyData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            $receivingsIn = PartMovements::where('type', 'in')
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->sum('qty');

            $outgoingsOut = PartMovements::where('type', 'out')
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->sum('qty');

            $dailyData[] = [
                'date' => $date->format('Y-m-d'),
                'incoming' => $receivingsIn,
                'outgoing' => $outgoingsOut,
            ];
        }

        // Recent Activities (last 10 movements)
        $recentActivities = PartMovements::with(['part:id,part_number,part_name'])
            ->select(['id', 'part_id', 'type', 'qty', 'stock_before', 'stock_after', 'created_at'])
            ->latest()
            ->limit(10)
            ->get();

        // Top 5 Parts by Stock
        $topPartsByStock = Parts::active()
            ->select(['id', 'part_number', 'part_name', 'stock'])
            ->orderBy('stock', 'desc')
            ->limit(5)
            ->get();

        // Stock Distribution
        $stockDistribution = [
            ['status' => 'Available', 'count' => Parts::inStock()->count()],
            ['status' => 'Low Stock', 'count' => $stats->low_stock_count],
            ['status' => 'Out of Stock', 'count' => $stats->out_of_stock_count],
        ];

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalParts' => $stats->total_parts,
                'activeParts' => $stats->active_parts,
                'totalStock' => $stats->total_stock,
                'lowStockCount' => $stats->low_stock_count,
                'outOfStockCount' => $stats->out_of_stock_count,
                'totalReceivings' => $receivingStats->total,
                'pendingReceivings' => $receivingStats->pending,
                'totalOutgoings' => $outgoingStats->total,
                'pendingOutgoings' => $outgoingStats->pending,
            ],
            'monthlyData' => $dailyData,
            'recentActivities' => $recentActivities,
            'topPartsByStock' => $topPartsByStock,
            'stockDistribution' => $stockDistribution,
            'trend' => [
                'incomingTotal' => $currentIncoming,
                'outgoingTotal' => $currentOutgoing,
                'incomingPct' => round($incomingTrend, 1),
                'outgoingPct' => round($outgoingTrend, 1),
            ],
            'userPermissions' => $permissions,
        ]);
    }
}

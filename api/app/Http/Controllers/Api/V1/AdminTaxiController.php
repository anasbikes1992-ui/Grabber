<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TaxiCashCommissionInvoice;
use App\Models\TaxiDriverScore;
use App\Models\TaxiQuest;
use App\Models\TaxiSurgeZone;
use App\Models\TaxiTrip;
use App\Services\CashCommissionInvoiceService;
use App\Services\TaxiSurgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTaxiController extends Controller
{
    public function __construct(
        private readonly TaxiSurgeService $surgeService,
        private readonly CashCommissionInvoiceService $commissionService,
    ) {}

    /**
     * GET /v1/admin/taxi/rides
     * All non-terminal rides with driver and customer info.
     */
    public function liveRides(): JsonResponse
    {
        $rides = TaxiTrip::with(['driver.profile', 'customer.profile', 'taxiCategory'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $rides]);
    }

    /**
     * GET /v1/admin/taxi/driver-scores
     * Paginated driver performance scores with optional tier filter.
     */
    public function driverScores(Request $request): JsonResponse
    {
        $query = TaxiDriverScore::with('driver.profile')
            ->latest('period_start');

        if ($request->has('tier')) {
            $query->where('tier', $request->input('tier'));
        }

        if ($request->has('driver_id')) {
            $query->where('driver_id', $request->input('driver_id'));
        }

        return response()->json(['success' => true, 'data' => $query->paginate(20)]);
    }

    /**
     * GET /v1/admin/taxi/surge-zones
     * All surge zones with current multiplier.
     */
    public function surgeZones(): JsonResponse
    {
        $zones = TaxiSurgeZone::get()->map(function ($zone) {
            return [
                ...$zone->toArray(),
                'current_multiplier' => $this->surgeService->computeMultiplier($zone),
            ];
        });

        return response()->json(['success' => true, 'data' => $zones]);
    }

    /**
     * POST /v1/admin/taxi/surge-zones/{id}/override
     * Set manual surge override on a zone.
     */
    public function setSurgeOverride(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'multiplier' => ['required', 'numeric', 'between:1,5'],
            'minutes'    => ['required', 'integer', 'between:5,240'],
        ]);

        $zone = TaxiSurgeZone::findOrFail($id);

        $this->surgeService->setManualOverride($zone, $data['multiplier'], $data['minutes']);

        return response()->json([
            'success' => true,
            'message' => "Surge override set to {$data['multiplier']}x for {$data['minutes']} minutes.",
        ]);
    }

    /**
     * DELETE /v1/admin/taxi/surge-zones/{id}/override
     * Clear manual surge override.
     */
    public function clearSurgeOverride(string $id): JsonResponse
    {
        $zone = TaxiSurgeZone::findOrFail($id);

        $this->surgeService->clearManualOverride($zone);

        return response()->json(['success' => true, 'message' => 'Surge override cleared.']);
    }

    /**
     * GET /v1/admin/taxi/quests
     * List all driver quests.
     */
    public function quests(): JsonResponse
    {
        $quests = TaxiQuest::latest()->get();
        return response()->json(['success' => true, 'data' => $quests]);
    }

    /**
     * POST /v1/admin/taxi/quests
     * Create a new driver quest.
     */
    public function createQuest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'type'         => ['required', 'in:daily,weekly'],
            'metric'       => ['required', 'in:completed_rides,km_driven,online_minutes,rating'],
            'target_value' => ['required', 'numeric', 'min:1'],
            'reward_type'  => ['required', 'in:cash,points'],
            'reward_amount' => ['required', 'numeric', 'min:0'],
            'starts_at'    => ['nullable', 'date'],
            'ends_at'      => ['nullable', 'date', 'after:starts_at'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $quest = TaxiQuest::create($data);

        return response()->json(['success' => true, 'quest' => $quest], 201);
    }

    /**
     * GET /v1/admin/taxi/corporate-accounts
     * List all corporate accounts with usage stats.
     */
    public function corporateAccounts(): JsonResponse
    {
        $accounts = \App\Models\TaxiCorporateAccount::withCount('employees')
            ->with('employees')
            ->latest()
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $accounts]);
    }

    /**
     * GET /v1/admin/taxi/cash-commission-invoices
     * Paginated cash commission invoices with status filter.
     */
    public function cashCommissionInvoices(Request $request): JsonResponse
    {
        $query = TaxiCashCommissionInvoice::with('driver.profile')
            ->latest('period_start');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('driver_id')) {
            $query->where('driver_id', $request->input('driver_id'));
        }

        return response()->json(['success' => true, 'data' => $query->paginate(20)]);
    }

    /**
     * POST /v1/admin/taxi/cash-commission-invoices/{id}/mark-paid
     * Admin marks an invoice as paid.
     */
    public function markInvoicePaid(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'payment_ref' => ['required', 'string', 'max:255'],
        ]);

        $invoice = $this->commissionService->markPaid($id, $data['payment_ref']);

        return response()->json(['success' => true, 'invoice' => $invoice]);
    }

    /**
     * GET /v1/admin/taxi/stats
     * High-level dashboard stats.
     */
    public function stats(): JsonResponse
    {
        $today = now()->startOfDay();

        return response()->json([
            'success' => true,
            'data'    => [
                'rides_today'             => TaxiTrip::where('created_at', '>=', $today)->count(),
                'completed_today'         => TaxiTrip::where('status', 'completed')->where('completed_at', '>=', $today)->count(),
                'active_rides'            => TaxiTrip::whereNotIn('status', ['completed', 'cancelled', 'searching'])->count(),
                'searching'               => TaxiTrip::where('status', 'searching')->count(),
                'revenue_today'           => TaxiTrip::where('status', 'completed')->where('completed_at', '>=', $today)->sum('final_fare'),
                'unpaid_commission_total' => TaxiCashCommissionInvoice::where('status', 'unpaid')->sum('commission_amount'),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PearlPointsBalance;
use App\Models\PlatformConfig;
use App\Models\StaysListing;
use App\Models\VehicleListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    /**
     * POST /v1/bookings
     * Create a new booking (pending payment).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'booking_type'  => ['required', Rule::in(['stay', 'vehicle', 'taxi', 'event', 'experience'])],
            'bookable_id'   => ['required', 'uuid'],
            'starts_at'     => ['required', 'date', 'after:now'],
            'ends_at'       => ['required', 'date', 'after:starts_at'],
            'payment_method' => ['required', Rule::in(['card', 'bank_transfer', 'cash_provider', 'cash_agent'])],
            'pearl_points_to_use' => ['sometimes', 'integer', 'min:0'],
            'customer_notes' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $bookingType = $data['booking_type'];
        $bookableId  = $data['bookable_id'];

        [$listing, $providerId, $subtotal] = $this->resolveListingAndPrice($bookingType, $bookableId, $data);

        // Pearl Points discount
        $ptsToUse   = (int) ($data['pearl_points_to_use'] ?? 0);
        $ptsDiscount = 0.0;
        if ($ptsToUse >= 100) {
            $maxDiscount = $subtotal * 0.30;
            $ptsToUse    = (int) min($ptsToUse, $maxDiscount);
            $ptsBalance  = PearlPointsBalance::where('user_id', $request->user()->id)->first();
            $ptsToUse    = min($ptsToUse, $ptsBalance?->balance ?? 0);
            $ptsDiscount = (float) $ptsToUse;
        }

        // Tax calculation
        $vatRate       = (float) PlatformConfig::get('tax', 'vat_rate', 18.0);
        $commissionRate = $this->getCommissionRate($bookingType);

        $commissionAmount = round($subtotal * ($commissionRate / 100), 2);
        $vatAmount        = round(($subtotal - $ptsDiscount) * ($vatRate / 100), 2);
        $totalAmount      = round($subtotal - $ptsDiscount + $vatAmount, 2);

        $booking = DB::transaction(function () use (
            $request, $data, $listing, $providerId, $subtotal,
            $commissionAmount, $vatAmount, $totalAmount, $ptsToUse, $ptsDiscount
        ) {
            if ($ptsToUse > 0) {
                $ptsBalance = PearlPointsBalance::where('user_id', $request->user()->id)->lockForUpdate()->first();
                if (!$ptsBalance || !$ptsBalance->spend($ptsToUse)) {
                    abort(422, 'Insufficient Pearl Points balance.');
                }
            }

            $bookingType   = $data['booking_type'];
            $bookableClass = $this->getBookableClass($bookingType);

            return Booking::create([
                'customer_id'     => $request->user()->id,
                'provider_id'     => $providerId,
                'booking_type'    => $bookingType,
                'bookable_type'   => $bookableClass,
                'bookable_id'     => $data['bookable_id'],
                'starts_at'       => $data['starts_at'],
                'ends_at'         => $data['ends_at'],
                'status'          => 'pending',
                'payment_status'  => 'unpaid',
                'subtotal'        => $subtotal,
                'commission_amount' => $commissionAmount,
                'vat_amount'      => $vatAmount,
                'total_amount'    => $totalAmount,
                'currency'        => 'LKR',
                'customer_notes'  => $data['customer_notes'] ?? null,
            ]);
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'booking_id'      => $booking->id,
                'status'          => $booking->status,
                'subtotal'        => $booking->subtotal,
                'pearl_discount'  => $ptsDiscount,
                'vat_amount'      => $booking->vat_amount,
                'total_amount'    => $booking->total_amount,
                'payment_method'  => $data['payment_method'],
                'booking_ref'     => 'GRAB-' . strtoupper(substr($booking->id, 0, 8)),
            ],
        ], 201);
    }

    /**
     * GET /v1/bookings
     * List current user's bookings (as customer or provider).
     */
    public function index(Request $request): JsonResponse
    {
        $view = $request->query('view', 'customer'); // customer|provider
        $userId = $request->user()->id;

        $query = Booking::with(['customer.profile', 'provider.profile', 'payment'])
            ->orderByDesc('created_at');

        if ($view === 'provider') {
            $query->forProvider($userId);
        } else {
            $query->forCustomer($userId);
        }

        return response()->json(['success' => true, 'data' => $query->paginate(15)]);
    }

    /**
     * GET /v1/bookings/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $booking = Booking::with(['customer.profile', 'provider.profile', 'payment'])->findOrFail($id);

        if (!$booking->isOwnedBy($request->user())) {
            abort(403, 'Forbidden.');
        }

        return response()->json(['success' => true, 'data' => $booking]);
    }

    /**
     * PATCH /v1/bookings/{id}/cancel
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        if (!$booking->isOwnedBy($request->user())) {
            abort(403, 'Forbidden.');
        }

        if (!in_array($booking->status, ['pending', 'confirmed', 'awaiting_payment'])) {
            abort(422, 'This booking cannot be cancelled.');
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:300'],
        ]);

        $booking->update([
            'status'              => 'cancelled',
            'cancellation_reason' => $data['reason'],
            'cancelled_at'        => now(),
            'cancelled_by'        => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Booking cancelled.']);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveListingAndPrice(string $bookingType, string $bookableId, array $data): array
    {
        return match ($bookingType) {
            'stay' => $this->resolveStayPrice($bookableId, $data),
            'vehicle' => $this->resolveVehiclePrice($bookableId, $data),
            default => throw new \InvalidArgumentException("Unsupported booking type: {$bookingType}"),
        };
    }

    private function resolveStayPrice(string $listingId, array $data): array
    {
        $listing = StaysListing::active()->findOrFail($listingId);
        $nights  = (int) ceil(
            (strtotime($data['ends_at']) - strtotime($data['starts_at'])) / 86400
        );
        $subtotal = round($listing->base_price_per_night * max($nights, 1), 2);
        return [$listing, $listing->host_id, $subtotal];
    }

    private function resolveVehiclePrice(string $listingId, array $data): array
    {
        $listing = VehicleListing::active()->findOrFail($listingId);
        $days    = (int) ceil(
            (strtotime($data['ends_at']) - strtotime($data['starts_at'])) / 86400
        );
        $subtotal = round($listing->price_per_day * max($days, 1), 2);
        return [$listing, $listing->owner_id, $subtotal];
    }

    private function getCommissionRate(string $bookingType): float
    {
        return match ($bookingType) {
            'stay'       => (float) PlatformConfig::get('commission', 'stays_rate', 12.0),
            'vehicle'    => (float) PlatformConfig::get('commission', 'vehicles_rate', 10.0),
            'taxi'       => (float) PlatformConfig::get('commission', 'taxi_rate', 15.0),
            'event'      => (float) PlatformConfig::get('commission', 'events_rate', 8.0),
            'experience' => (float) PlatformConfig::get('commission', 'experiences_rate', 10.0),
            default      => 10.0,
        };
    }

    private function getBookableClass(string $bookingType): string
    {
        return match ($bookingType) {
            'stay'    => StaysListing::class,
            'vehicle' => VehicleListing::class,
            default   => StaysListing::class,
        };
    }
}

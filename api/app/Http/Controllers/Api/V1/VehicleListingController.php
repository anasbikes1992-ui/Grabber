<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\VehicleListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleListingController extends Controller
{
    /**
     * GET /v1/vehicles
     */
    public function index(Request $request): JsonResponse
    {
        $query = VehicleListing::active()
            ->with('owner:id,email,phone')
            ->orderByDesc('rating_avg');

        if ($type = $request->query('vehicle_type')) {
            $query->where('vehicle_type', $type);
        }
        if ($city = $request->query('city')) {
            $query->where('pickup_city', 'like', "%{$city}%");
        }
        if ($maxPrice = $request->query('max_price')) {
            $query->where('price_per_day', '<=', (float) $maxPrice);
        }
        if ($seats = $request->query('seats')) {
            $query->where('seats', '>=', (int) $seats);
        }
        if ($driver = $request->query('driver_available')) {
            $query->where('driver_available', filter_var($driver, FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json(['success' => true, 'data' => $query->paginate(20)]);
    }

    /**
     * POST /v1/vehicles
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'make'           => ['required', 'string', 'max:60'],
            'model'          => ['required', 'string', 'max:60'],
            'year'           => ['required', 'integer', 'between:1990,2027'],
            'color'          => ['sometimes', 'string', 'max:30'],
            'license_plate'  => ['required', 'string', 'max:20', Rule::unique('vehicle_listings', 'license_plate')],
            'vehicle_type'   => ['required', Rule::in([
                'economy','comfort','suv','luxury','van','tuk_tuk','motorbike','bicycle','ev','campervan',
            ])],
            'transmission'   => ['sometimes', Rule::in(['manual', 'automatic'])],
            'seats'          => ['required', 'integer', 'min:1', 'max:50'],
            'price_per_day'  => ['required', 'numeric', 'min:0'],
            'pickup_city'    => ['required', 'string'],
            'pickup_lat'     => ['sometimes', 'numeric'],
            'pickup_lng'     => ['sometimes', 'numeric'],
            'features'       => ['sometimes', 'array'],
            'driver_available' => ['sometimes', 'boolean'],
            'driver_extra_per_day' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $listing = $request->user()->vehicleListings()->create([
            ...$data,
            'currency' => 'LKR',
            'status'   => 'draft',
        ]);

        return response()->json(['success' => true, 'data' => $listing], 201);
    }

    /**
     * GET /v1/vehicles/{id}
     */
    public function show(string $id): JsonResponse
    {
        $listing = VehicleListing::with(['owner:id,email,phone', 'owner.profile:user_id,full_name,avatar_url'])
            ->findOrFail($id);
        return response()->json(['success' => true, 'data' => $listing]);
    }

    /**
     * PATCH /v1/vehicles/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $listing = VehicleListing::findOrFail($id);

        if ($listing->owner_id !== $request->user()->id) {
            abort(403, 'Forbidden.');
        }

        $data = $request->validate([
            'price_per_day'  => ['sometimes', 'numeric', 'min:0'],
            'status'         => ['sometimes', Rule::in(['draft', 'active', 'inactive'])],
            'features'       => ['sometimes', 'array'],
            'driver_available' => ['sometimes', 'boolean'],
        ]);

        $listing->update($data);
        return response()->json(['success' => true, 'data' => $listing->fresh()]);
    }

    /**
     * DELETE /v1/vehicles/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $listing = VehicleListing::findOrFail($id);
        if ($listing->owner_id !== $request->user()->id) {
            abort(403, 'Forbidden.');
        }
        $listing->delete();
        return response()->json(['success' => true, 'message' => 'Vehicle removed.']);
    }

    /**
     * GET /v1/vehicles/mine
     */
    public function mine(Request $request): JsonResponse
    {
        $listings = $request->user()->vehicleListings()
            ->withTrashed()
            ->orderByDesc('created_at')
            ->paginate(20);
        return response()->json(['success' => true, 'data' => $listings]);
    }
}

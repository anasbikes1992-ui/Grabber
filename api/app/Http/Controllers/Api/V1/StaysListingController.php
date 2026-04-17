<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StaysListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StaysListingController extends Controller
{
    /**
     * GET /v1/stays
     * Public listing search.
     */
    public function index(Request $request): JsonResponse
    {
        $query = StaysListing::active()
            ->with('host:id,email,phone')
            ->orderByDesc('rating_avg');

        if ($city = $request->query('city')) {
            $query->inCity((string) $city);
        }
        if ($type = $request->query('property_type')) {
            $query->where('property_type', $type);
        }
        if ($minPrice = $request->query('min_price')) {
            $query->where('base_price_per_night', '>=', (float) $minPrice);
        }
        if ($maxPrice = $request->query('max_price')) {
            $query->where('base_price_per_night', '<=', (float) $maxPrice);
        }
        if ($guests = $request->query('guests')) {
            $query->where('max_guests', '>=', (int) $guests);
        }

        $listings = $query->paginate(20);

        return response()->json(['success' => true, 'data' => $listings]);
    }

    /**
     * POST /v1/stays
     * Provider creates a new listing.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'               => ['required', 'string', 'max:200'],
            'description'         => ['nullable', 'string'],
            'property_type'       => ['required', Rule::in([
                'hotel', 'villa', 'guesthouse', 'boutique', 'eco_lodge',
                'apartment', 'treehouse', 'camping',
            ])],
            'address'             => ['required', 'string'],
            'lat'                 => ['required', 'numeric', 'between:-90,90'],
            'lng'                 => ['required', 'numeric', 'between:-180,180'],
            'city'                => ['required', 'string'],
            'base_price_per_night' => ['required', 'numeric', 'min:0'],
            'max_guests'          => ['required', 'integer', 'min:1'],
            'bedrooms'            => ['sometimes', 'integer', 'min:0'],
            'bathrooms'           => ['sometimes', 'integer', 'min:0'],
            'amenities'           => ['sometimes', 'array'],
            'instant_book'        => ['sometimes', 'boolean'],
            'min_nights'          => ['sometimes', 'integer', 'min:1'],
            'max_nights'          => ['sometimes', 'integer', 'min:1'],
        ]);

        $listing = $request->user()->staysListings()->create([
            ...$data,
            'currency' => 'LKR',
            'status'   => 'draft',
        ]);

        return response()->json(['success' => true, 'data' => $listing], 201);
    }

    /**
     * GET /v1/stays/{id}
     */
    public function show(string $id): JsonResponse
    {
        $listing = StaysListing::with(['host:id,email,phone', 'host.profile:user_id,full_name,avatar_url'])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $listing]);
    }

    /**
     * PATCH /v1/stays/{id}
     * Host updates their listing.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $listing = StaysListing::findOrFail($id);

        if ($listing->host_id !== $request->user()->id) {
            abort(403, 'Forbidden.');
        }

        $data = $request->validate([
            'title'               => ['sometimes', 'string', 'max:200'],
            'description'         => ['sometimes', 'nullable', 'string'],
            'base_price_per_night' => ['sometimes', 'numeric', 'min:0'],
            'max_guests'          => ['sometimes', 'integer', 'min:1'],
            'amenities'           => ['sometimes', 'array'],
            'status'              => ['sometimes', Rule::in(['draft', 'active', 'inactive'])],
            'instant_book'        => ['sometimes', 'boolean'],
            'min_nights'          => ['sometimes', 'integer', 'min:1'],
            'max_nights'          => ['sometimes', 'integer', 'min:1'],
        ]);

        $listing->update($data);

        return response()->json(['success' => true, 'data' => $listing->fresh()]);
    }

    /**
     * DELETE /v1/stays/{id}
     * Soft-delete (host only).
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $listing = StaysListing::findOrFail($id);

        if ($listing->host_id !== $request->user()->id) {
            abort(403, 'Forbidden.');
        }

        $listing->delete();

        return response()->json(['success' => true, 'message' => 'Listing removed.']);
    }

    /**
     * GET /v1/stays/mine
     * Host's own listings.
     */
    public function mine(Request $request): JsonResponse
    {
        $listings = $request->user()->staysListings()
            ->withTrashed()
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $listings]);
    }
}

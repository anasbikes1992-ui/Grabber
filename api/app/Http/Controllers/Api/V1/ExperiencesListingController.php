<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExperiencesListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExperiencesListingController extends Controller
{
    /**
     * GET /v1/experiences
     */
    public function index(Request $request): JsonResponse
    {
        $query = ExperiencesListing::where('status', 'active');

        if ($request->filled('city')) {
            $query->where('city', 'ilike', '%' . $request->city . '%');
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('max_price')) {
            $query->where('price_per_person', '<=', (float) $request->max_price);
        }
        if ($request->filled('min_group')) {
            $query->where('max_group', '>=', (int) $request->min_group);
        }

        $listings = $query->orderByDesc('rating_avg')->paginate(20);

        return response()->json(['success' => true, 'data' => $listings]);
    }

    /**
     * GET /v1/experiences/{id}
     */
    public function show(string $id): JsonResponse
    {
        $listing = ExperiencesListing::with('provider.profile')
            ->where('status', 'active')
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $listing]);
    }

    /**
     * POST /v1/experiences  (provider creates)
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'                 => ['required', 'string', 'max:200'],
            'description'           => ['nullable', 'string'],
            'category'              => ['required', 'string', 'max:50'],
            'city'                  => ['required', 'string', 'max:80'],
            'lat'                   => ['nullable', 'numeric', 'between:-90,90'],
            'lng'                   => ['nullable', 'numeric', 'between:-180,180'],
            'price_per_person'      => ['required', 'numeric', 'min:1'],
            'price_per_group'       => ['nullable', 'numeric', 'min:0'],
            'child_price'           => ['nullable', 'numeric', 'min:0'],
            'min_group'             => ['sometimes', 'integer', 'min:1'],
            'max_group'             => ['sometimes', 'integer', 'min:1', 'max:500'],
            'duration_hours'        => ['nullable', 'integer', 'min:1'],
            'weather_dependent'     => ['sometimes', 'boolean'],
            'wheelchair_accessible' => ['sometimes', 'boolean'],
            'min_age'               => ['nullable', 'integer', 'min:0', 'max:100'],
            'availability_months'   => ['nullable', 'array'],
            'images'                => ['nullable', 'array'],
        ]);

        $listing = ExperiencesListing::create([
            ...$data,
            'provider_id' => $request->user()->id,
            'status'      => 'pending_approval',
        ]);

        return response()->json(['success' => true, 'data' => $listing], 201);
    }

    /**
     * PUT /v1/experiences/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $listing = ExperiencesListing::findOrFail($id);
        abort_unless($listing->provider_id === $request->user()->id, 403, 'Forbidden.');

        $data = $request->validate([
            'title'                 => ['sometimes', 'string', 'max:200'],
            'description'           => ['nullable', 'string'],
            'price_per_person'      => ['sometimes', 'numeric', 'min:1'],
            'price_per_group'       => ['nullable', 'numeric', 'min:0'],
            'child_price'           => ['nullable', 'numeric', 'min:0'],
            'min_group'             => ['sometimes', 'integer', 'min:1'],
            'max_group'             => ['sometimes', 'integer', 'min:1'],
            'images'                => ['nullable', 'array'],
            'status'                => ['sometimes', 'in:active,inactive'],
        ]);

        $listing->update($data);

        return response()->json(['success' => true, 'data' => $listing]);
    }

    /**
     * DELETE /v1/experiences/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $listing = ExperiencesListing::findOrFail($id);
        abort_unless($listing->provider_id === $request->user()->id, 403, 'Forbidden.');
        $listing->delete();
        return response()->json(['success' => true]);
    }

    /**
     * GET /v1/experiences/mine
     */
    public function mine(Request $request): JsonResponse
    {
        $listings = ExperiencesListing::where('provider_id', $request->user()->id)
            ->latest()
            ->paginate(20);
        return response()->json(['success' => true, 'data' => $listings]);
    }
}

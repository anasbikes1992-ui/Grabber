<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TaxiCategory;
use App\Services\TaxiSurgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxiFareController extends Controller
{
    public function __construct(
        private readonly TaxiSurgeService $surgeService,
    ) {}

    /**
     * GET /v1/taxi/fare/estimate
     * Returns itemized fare breakdown for given pickup/dropoff and category.
     */
    public function estimate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'origin_lat'      => ['required', 'numeric', 'between:-90,90'],
            'origin_lng'      => ['required', 'numeric', 'between:-180,180'],
            'dest_lat'        => ['required', 'numeric', 'between:-90,90'],
            'dest_lng'        => ['required', 'numeric', 'between:-180,180'],
            'taxi_category_id' => ['required', 'integer', 'exists:taxi_categories,id'],
        ]);

        $distanceKm = $this->haversineKm(
            $data['origin_lat'], $data['origin_lng'],
            $data['dest_lat'],   $data['dest_lng']
        );

        $surge    = $this->surgeService->calculateSurge((float) $data['origin_lat'], (float) $data['origin_lng']);
        $category = TaxiCategory::findOrFail($data['taxi_category_id']);

        $baseFare      = (float) $category->base_fare;
        $distanceFare  = round($distanceKm * (float) $category->per_km_rate, 2);
        $surgeAddition = round(($baseFare + $distanceFare) * ($surge - 1.0), 2);
        $totalFare     = round($baseFare + $distanceFare + $surgeAddition, 2);
        $pearlPoints   = (int) floor($totalFare / 100); // 1 pt per LKR 100

        return response()->json([
            'success'         => true,
            'category'        => $category,
            'distance_km'     => round($distanceKm, 2),
            'surge_multiplier' => $surge,
            'breakdown'       => [
                'base_fare'     => $baseFare,
                'distance_fare' => $distanceFare,
                'surge_addition' => $surgeAddition,
                'total'         => $totalFare,
            ],
            'pearl_points_earn' => $pearlPoints,
        ]);
    }

    /**
     * GET /v1/taxi/fare/all-categories
     * Returns fare estimates for all categories for given pickup/dropoff.
     */
    public function allCategories(Request $request): JsonResponse
    {
        $data = $request->validate([
            'origin_lat' => ['required', 'numeric', 'between:-90,90'],
            'origin_lng' => ['required', 'numeric', 'between:-180,180'],
            'dest_lat'   => ['required', 'numeric', 'between:-90,90'],
            'dest_lng'   => ['required', 'numeric', 'between:-180,180'],
        ]);

        $distanceKm = $this->haversineKm(
            $data['origin_lat'], $data['origin_lng'],
            $data['dest_lat'],   $data['dest_lng']
        );

        $surge      = $this->surgeService->calculateSurge((float) $data['origin_lat'], (float) $data['origin_lng']);
        $categories = TaxiCategory::where('is_active', true)->get();

        $estimates = $categories->map(function ($category) use ($distanceKm, $surge) {
            $total = round(
                ((float) $category->base_fare + $distanceKm * (float) $category->per_km_rate) * $surge,
                2
            );

            return [
                'category'         => $category,
                'distance_km'      => round($distanceKm, 2),
                'surge_multiplier' => $surge,
                'total_fare'       => $total,
                'pearl_points_earn' => (int) floor($total / 100),
            ];
        });

        return response()->json(['success' => true, 'data' => $estimates]);
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthR = 6371;
        $dLat   = deg2rad($lat2 - $lat1);
        $dLng   = deg2rad($lng2 - $lng1);
        $a      = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earthR * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}

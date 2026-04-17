<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PearlPointsBalance;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * POST /v1/reviews
     * Submit a review for a completed booking.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'booking_id' => ['required', 'uuid'],
            'rating'     => ['required', 'integer', 'between:1,5'],
            'body'       => ['nullable', 'string', 'max:1000'],
        ]);

        $booking = Booking::findOrFail($data['booking_id']);

        if ($booking->status !== 'completed') {
            abort(422, 'You can only review completed bookings.');
        }

        $userId = $request->user()->id;
        $direction = $booking->customer_id === $userId ? 'customer_to_provider' : 'provider_to_customer';

        if ($direction === 'customer_to_provider' && $booking->customer_id !== $userId) {
            abort(403, 'Forbidden.');
        }
        if ($direction === 'provider_to_customer' && $booking->provider_id !== $userId) {
            abort(403, 'Forbidden.');
        }

        $alreadyReviewed = Review::where('booking_id', $booking->id)
            ->where('reviewer_id', $userId)
            ->exists();
        if ($alreadyReviewed) {
            abort(422, 'You have already reviewed this booking.');
        }

        $revieweeId    = $direction === 'customer_to_provider' ? $booking->provider_id : $booking->customer_id;
        $bookableClass = get_class($booking->bookable);

        $review = Review::create([
            'booking_id'    => $booking->id,
            'reviewer_id'   => $userId,
            'reviewee_id'   => $revieweeId,
            'reviewable_type' => $bookableClass,
            'reviewable_id'  => $booking->bookable_id,
            'rating'        => $data['rating'],
            'body'          => $data['body'] ?? null,
            'direction'     => $direction,
        ]);

        // Award Pearl Points for review
        $pts = PearlPointsBalance::where('user_id', $userId)->first();
        $pts?->earn(50);

        return response()->json(['success' => true, 'data' => $review], 201);
    }

    /**
     * GET /v1/reviews?reviewable_type=stays&reviewable_id={id}
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reviewable_type' => ['required', 'string'],
            'reviewable_id'   => ['required', 'uuid'],
        ]);

        $reviews = Review::with('reviewer.profile:user_id,full_name,avatar_url')
            ->where('reviewable_type', $data['reviewable_type'])
            ->where('reviewable_id', $data['reviewable_id'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json(['success' => true, 'data' => $reviews]);
    }
}

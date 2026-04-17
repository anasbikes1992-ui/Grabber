<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EventsListing;
use App\Models\EventTicketType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventsListingController extends Controller
{
    /**
     * GET /v1/events
     */
    public function index(Request $request): JsonResponse
    {
        $query = EventsListing::with('ticketTypes')
            ->whereIn('status', ['published'])
            ->where('starts_at', '>', now());

        if ($request->filled('city')) {
            $query->where('city', 'ilike', '%' . $request->city . '%');
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        $events = $query->orderBy('starts_at')->paginate(20);

        return response()->json(['success' => true, 'data' => $events]);
    }

    /**
     * GET /v1/events/{id}
     */
    public function show(string $id): JsonResponse
    {
        $event = EventsListing::with(['ticketTypes', 'organiser.profile'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $event]);
    }

    /**
     * POST /v1/events  (organiser creates)
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'               => ['required', 'string', 'max:200'],
            'description'         => ['nullable', 'string'],
            'category'            => ['nullable', 'string', 'max:50'],
            'venue_name'          => ['nullable', 'string', 'max:200'],
            'city'                => ['nullable', 'string', 'max:80'],
            'lat'                 => ['nullable', 'numeric', 'between:-90,90'],
            'lng'                 => ['nullable', 'numeric', 'between:-180,180'],
            'starts_at'           => ['required', 'date', 'after:now'],
            'ends_at'             => ['required', 'date', 'after:starts_at'],
            'event_type'          => ['required', 'in:in_person,virtual,hybrid'],
            'stream_url'          => ['nullable', 'url', 'max:255'],
            'qr_scanner_enabled'  => ['sometimes', 'boolean'],
            'images'              => ['nullable', 'array'],
            'is_recurring'        => ['sometimes', 'boolean'],
            'recurring_pattern'   => ['nullable', 'in:weekly,monthly'],
            // Ticket types array
            'ticket_types'        => ['nullable', 'array'],
            'ticket_types.*.name' => ['required_with:ticket_types', 'string', 'max:100'],
            'ticket_types.*.type' => ['required_with:ticket_types', 'in:general,reserved,vip,early_bird,group,free,donation'],
            'ticket_types.*.price'    => ['required_with:ticket_types', 'numeric', 'min:0'],
            'ticket_types.*.quantity' => ['nullable', 'integer', 'min:1'],
            'ticket_types.*.sale_starts_at' => ['nullable', 'date'],
            'ticket_types.*.sale_ends_at'   => ['nullable', 'date'],
        ]);

        $event = DB::transaction(function () use ($data, $request) {
            $event = EventsListing::create([
                ...collect($data)->except('ticket_types')->toArray(),
                'organiser_id' => $request->user()->id,
                'status'       => 'draft',
            ]);

            foreach ($data['ticket_types'] ?? [] as $tt) {
                $event->ticketTypes()->create($tt);
            }

            return $event;
        });

        return response()->json([
            'success' => true,
            'data'    => $event->load('ticketTypes'),
        ], 201);
    }

    /**
     * PUT /v1/events/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $event = EventsListing::findOrFail($id);
        abort_unless($event->organiser_id === $request->user()->id, 403, 'Forbidden.');

        $data = $request->validate([
            'title'              => ['sometimes', 'string', 'max:200'],
            'description'        => ['nullable', 'string'],
            'status'             => ['sometimes', 'in:draft,published,cancelled'],
            'starts_at'          => ['sometimes', 'date'],
            'ends_at'            => ['sometimes', 'date'],
            'images'             => ['nullable', 'array'],
            'stream_url'         => ['nullable', 'url', 'max:255'],
            'qr_scanner_enabled' => ['sometimes', 'boolean'],
        ]);

        $event->update($data);

        return response()->json(['success' => true, 'data' => $event->load('ticketTypes')]);
    }

    /**
     * DELETE /v1/events/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $event = EventsListing::findOrFail($id);
        abort_unless($event->organiser_id === $request->user()->id, 403, 'Forbidden.');
        $event->delete();
        return response()->json(['success' => true]);
    }

    /**
     * GET /v1/events/mine
     */
    public function mine(Request $request): JsonResponse
    {
        $events = EventsListing::with('ticketTypes')
            ->where('organiser_id', $request->user()->id)
            ->latest()
            ->paginate(20);
        return response()->json(['success' => true, 'data' => $events]);
    }

    /**
     * PATCH /v1/events/{id}/publish
     */
    public function publish(Request $request, string $id): JsonResponse
    {
        $event = EventsListing::findOrFail($id);
        abort_unless($event->organiser_id === $request->user()->id, 403, 'Forbidden.');
        abort_unless($event->status === 'draft', 422, 'Only draft events can be published.');
        $event->update(['status' => 'published']);
        return response()->json(['success' => true, 'message' => 'Event published.']);
    }
}

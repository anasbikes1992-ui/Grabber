<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EventTicket;
use App\Models\EventTicketType;
use App\Models\EventsListing;
use App\Models\TicketScan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventTicketController extends Controller
{
    /**
     * POST /v1/events/{id}/tickets/purchase
     * Creates one or more event tickets for authenticated customer.
     */
    public function purchase(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'ticket_type_id' => ['required', 'uuid', 'exists:event_ticket_types,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $event = EventsListing::findOrFail($id);
        abort_unless($event->status === 'published', 422, 'Event is not available for booking.');

        $quantity = (int) ($data['quantity'] ?? 1);

        $ticketType = EventTicketType::where('event_id', $event->id)
            ->where('id', $data['ticket_type_id'])
            ->where('is_active', true)
            ->firstOrFail();

        if ($ticketType->sale_starts_at && now()->lt($ticketType->sale_starts_at)) {
            abort(422, 'Ticket sales have not started yet.');
        }

        if ($ticketType->sale_ends_at && now()->gt($ticketType->sale_ends_at)) {
            abort(422, 'Ticket sales are closed.');
        }

        $tickets = DB::transaction(function () use ($request, $ticketType, $event, $quantity) {
            $lockedType = EventTicketType::where('id', $ticketType->id)->lockForUpdate()->firstOrFail();

            if ($lockedType->quantity !== null) {
                $remaining = $lockedType->quantity - $lockedType->sold;
                if ($remaining < $quantity) {
                    abort(422, 'Not enough tickets available.');
                }
            }

            $created = collect();
            for ($i = 0; $i < $quantity; $i++) {
                $created->push(EventTicket::create([
                    'event_id' => $event->id,
                    'ticket_type_id' => $lockedType->id,
                    'customer_id' => $request->user()->id,
                    'ticket_code' => strtoupper(Str::random(12)),
                    'status' => 'active',
                    'price_paid' => (float) $lockedType->price,
                    'purchased_at' => now(),
                ]));
            }

            $lockedType->increment('sold', $quantity);
            $event->increment('sold_tickets', $quantity);

            return $created;
        });

        return response()->json([
            'success' => true,
            'message' => 'Tickets purchased successfully.',
            'data' => $tickets->load(['event', 'ticketType']),
        ], 201);
    }

    /**
     * GET /v1/events/tickets/{ticketCode}
     * Fetch ticket by code for customer/organiser verification views.
     */
    public function show(Request $request, string $ticketCode): JsonResponse
    {
        $ticket = EventTicket::with(['event', 'ticketType', 'customer.profile'])
            ->where('ticket_code', strtoupper($ticketCode))
            ->firstOrFail();

        $user = $request->user();
        $allowed = $ticket->customer_id === $user->id || $ticket->event?->organiser_id === $user->id;
        abort_unless($allowed, 403, 'Forbidden.');

        return response()->json(['success' => true, 'data' => $ticket]);
    }

    /**
     * POST /v1/events/tickets/{ticketCode}/scan
     * Marks ticket as used (entry scan) and logs scan result.
     */
    public function scan(Request $request, string $ticketCode): JsonResponse
    {
        $data = $request->validate([
            'scan_type' => ['nullable', 'in:entry,exit,manual'],
        ]);

        $ticket = EventTicket::with('event')
            ->where('ticket_code', strtoupper($ticketCode))
            ->firstOrFail();

        $user = $request->user();
        abort_unless($ticket->event?->organiser_id === $user->id || in_array($user->role, ['admin', 'super_admin'], true), 403, 'Forbidden.');

        $scanType = $data['scan_type'] ?? 'entry';

        if ($ticket->status !== 'active') {
            TicketScan::create([
                'ticket_id' => $ticket->id,
                'scanned_by' => $user->id,
                'scan_type' => $scanType,
                'result' => 'already_used',
                'scanned_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ticket already used or invalid.',
                'data' => ['status' => $ticket->status],
            ], 422);
        }

        DB::transaction(function () use ($ticket, $user, $scanType) {
            $locked = EventTicket::where('id', $ticket->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'active') {
                TicketScan::create([
                    'ticket_id' => $locked->id,
                    'scanned_by' => $user->id,
                    'scan_type' => $scanType,
                    'result' => 'already_used',
                    'scanned_at' => now(),
                ]);

                abort(422, 'Ticket already used or invalid.');
            }

            $locked->update([
                'status' => 'used',
                'used_at' => now(),
            ]);

            TicketScan::create([
                'ticket_id' => $locked->id,
                'scanned_by' => $user->id,
                'scan_type' => $scanType,
                'result' => 'valid',
                'scanned_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Ticket scan successful.',
        ]);
    }
}

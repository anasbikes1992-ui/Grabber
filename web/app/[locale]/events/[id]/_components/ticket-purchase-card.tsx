'use client';

import { useState } from 'react';

const API_BASE = process.env.NEXT_PUBLIC_API_URL ?? 'https://api.grabber.lk';

type TicketType = {
  id: string;
  name: string;
  price: number;
};

export default function TicketPurchaseCard({
  eventId,
  ticketTypes,
}: {
  eventId: string;
  ticketTypes: TicketType[];
}) {
  const [selectedTicketType, setSelectedTicketType] = useState<string>(ticketTypes[0]?.id ?? '');
  const [purchasing, setPurchasing] = useState(false);
  const [purchaseMsg, setPurchaseMsg] = useState('');

  const purchaseTicket = async () => {
    if (!selectedTicketType) {
      setPurchaseMsg('Select a ticket type first.');
      return;
    }

    const token = typeof window !== 'undefined' ? localStorage.getItem('grabber_token') : null;
    if (!token) {
      setPurchaseMsg('Please log in to purchase tickets.');
      return;
    }

    setPurchasing(true);
    setPurchaseMsg('');

    try {
      const res = await fetch(`${API_BASE}/api/v1/events/${eventId}/tickets/purchase`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({ ticket_type_id: selectedTicketType, quantity: 1 }),
      });

      const json = await res.json();
      if (!res.ok || !json?.success) {
        throw new Error(json?.message ?? 'Ticket purchase failed');
      }

      setPurchaseMsg('Ticket purchased successfully. Check your app for QR ticket.');
    } catch (e) {
      setPurchaseMsg(e instanceof Error ? e.message : 'Ticket purchase failed');
    } finally {
      setPurchasing(false);
    }
  };

  return (
    <section className="rounded-3xl bg-white p-6 shadow-sm md:p-8">
      <h2 className="text-xl font-semibold text-slate-950">Ticket Options</h2>

      <div className="mt-4 grid gap-3 md:grid-cols-2">
        {ticketTypes.map((ticket) => (
          <label key={ticket.id} className="flex cursor-pointer items-center justify-between rounded-2xl border border-slate-200 px-4 py-3">
            <div>
              <p className="text-sm font-semibold text-slate-900">{ticket.name}</p>
              <p className="text-xs text-slate-500">LKR {Number(ticket.price).toLocaleString()}</p>
            </div>
            <input
              type="radio"
              name="ticket-type"
              checked={selectedTicketType === ticket.id}
              onChange={() => setSelectedTicketType(ticket.id)}
            />
          </label>
        ))}
      </div>

      <button
        onClick={purchaseTicket}
        disabled={purchasing}
        className="mt-5 rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
      >
        {purchasing ? 'Purchasing...' : 'Purchase Ticket'}
      </button>

      {purchaseMsg && <p className="mt-3 text-sm text-slate-700">{purchaseMsg}</p>}
    </section>
  );
}

import { notFound } from 'next/navigation';
import TicketPurchaseCard from './_components/ticket-purchase-card';

const API_BASE = process.env.NEXT_PUBLIC_API_URL ?? 'https://api.grabber.lk';

type EventDetails = {
  id: string;
  title: string;
  description?: string;
  city?: string;
  venue_name?: string;
  starts_at: string;
  ends_at: string;
  ticket_types?: Array<{
    id: string;
    name: string;
    price: number;
  }>;
};

async function loadEvent(eventId: string): Promise<EventDetails | null> {
  try {
    const res = await fetch(`${API_BASE}/api/v1/events/${eventId}`, { next: { revalidate: 60 } });
    const json = await res.json();
    if (!res.ok || !json?.data) return null;
    return json.data as EventDetails;
  } catch {
    return null;
  }
}

export default async function EventDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const event = await loadEvent(id);

  if (!event) {
    notFound();
  }

  const ticketTypes = (event.ticket_types ?? []).map((ticket) => ({
    id: ticket.id,
    name: ticket.name,
    price: Number(ticket.price ?? 0),
  }));

  return (
    <main className="min-h-screen bg-[#f5f8ff] px-4 py-8 md:px-8">
      <div className="mx-auto max-w-4xl space-y-6">
        <section className="rounded-3xl bg-white p-6 shadow-sm md:p-8">
          <p className="text-sm uppercase tracking-[0.25em] text-blue-700">{event.city ?? 'Sri Lanka'}</p>
          <h1 className="mt-3 text-4xl font-semibold text-slate-950">{event.title}</h1>
          <p className="mt-3 text-sm text-slate-600">
            {new Date(event.starts_at).toLocaleString()} - {new Date(event.ends_at).toLocaleString()}
          </p>
          <p className="mt-4 text-sm leading-7 text-slate-700">{event.description ?? 'This event is available for ticket booking on Grabber.'}</p>
        </section>

        {ticketTypes.length > 0 ? (
          <TicketPurchaseCard eventId={event.id} ticketTypes={ticketTypes} />
        ) : (
          <section className="rounded-3xl bg-white p-6 shadow-sm md:p-8">
            <p className="text-sm text-slate-600">No ticket types are available for this event yet.</p>
          </section>
        )}
      </div>
    </main>
  );
}

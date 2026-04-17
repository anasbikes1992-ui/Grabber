import Link from 'next/link';

const API_BASE = process.env.NEXT_PUBLIC_API_URL ?? 'https://api.grabber.lk';

type EventItem = {
  id: string;
  title: string;
  city?: string;
  category?: string;
  starts_at: string;
  short_description?: string;
  event_type?: string;
};

const fallbackEvents: EventItem[] = [
  {
    id: 'demo-jazz-night',
    title: 'Colombo Jazz Night',
    city: 'Colombo',
    category: 'Music',
    starts_at: new Date(Date.now() + 86400000).toISOString(),
    short_description: 'Live jazz sets and rooftop dining in the city center.',
    event_type: 'in_person',
  },
  {
    id: 'demo-kandy-food-fest',
    title: 'Kandy Food Festival',
    city: 'Kandy',
    category: 'Food',
    starts_at: new Date(Date.now() + 172800000).toISOString(),
    short_description: 'Street food stalls, chef demos, and regional specialties.',
    event_type: 'hybrid',
  },
];

async function loadEvents(): Promise<EventItem[]> {
  try {
    const res = await fetch(`${API_BASE}/api/v1/events`, { next: { revalidate: 120 } });
    if (!res.ok) throw new Error('Failed to load events');
    const json = await res.json();
    const data = Array.isArray(json?.data?.data)
      ? json.data.data
      : Array.isArray(json?.data)
        ? json.data
        : [];

    if (!Array.isArray(data) || data.length === 0) {
      return fallbackEvents;
    }

    return data as EventItem[];
  } catch {
    return fallbackEvents;
  }
}

export default async function EventsPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const events = await loadEvents();

  return (
    <main className="min-h-screen bg-[#f5f8ff] px-4 py-8 md:px-8">
      <div className="mx-auto max-w-6xl">
        <section className="rounded-3xl bg-white p-6 shadow-sm md:p-8">
          <p className="text-sm uppercase tracking-[0.25em] text-blue-700">Events</p>
          <h1 className="mt-3 text-4xl font-semibold text-slate-950">Discover concerts, festivals, and live experiences.</h1>
          <p className="mt-3 text-sm text-slate-600">Book tickets instantly and access QR entry in the Grabber app.</p>
        </section>

        <section className="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
          {events.map((event) => (
            <article key={event.id} className="rounded-3xl bg-white shadow-sm overflow-hidden">
              <div className="h-40 bg-[linear-gradient(135deg,#0f172a,#1d4ed8,#93c5fd)]" />
              <div className="p-5">
                <p className="text-xs uppercase tracking-widest text-blue-700">{event.category ?? 'Event'}</p>
                <h2 className="mt-2 text-xl font-semibold text-slate-950">{event.title}</h2>
                <p className="mt-2 text-sm text-slate-500">{event.city ?? 'Sri Lanka'} · {new Date(event.starts_at).toLocaleString()}</p>
                <p className="mt-3 text-sm leading-6 text-slate-600">{event.short_description ?? 'Live event hosted on Grabber.'}</p>
                <Link
                  href={`/${locale}/events/${event.id}`}
                  className="mt-5 inline-block rounded-full bg-slate-950 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
                >
                  View Event
                </Link>
              </div>
            </article>
          ))}
        </section>
      </div>
    </main>
  );
}

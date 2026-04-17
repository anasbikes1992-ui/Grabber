'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { useLocale } from 'next-intl';
import { MapPin, Search, Star } from 'lucide-react';

type StayCard = {
  id: string;
  title: string;
  city: string;
  base_price: number;
  short_description?: string;
};

const fallbackStays: StayCard[] = [
  {
    id: 'demo-1',
    title: 'Ella Ridge Cabanas',
    city: 'Ella',
    base_price: 28500,
    short_description: 'Canopy cabins with ridge-line sunrise views and local breakfast.',
  },
  {
    id: 'demo-2',
    title: 'Galle Fort Courtyard',
    city: 'Galle',
    base_price: 32000,
    short_description: 'Restored heritage rooms tucked inside the fort wall.',
  },
  {
    id: 'demo-3',
    title: 'Sigiriya Canopy Lodge',
    city: 'Sigiriya',
    base_price: 24750,
    short_description: 'Pool villas with direct access to the cultural triangle.',
  },
];

export default function StaysPage() {
  const locale = useLocale();
  const [search, setSearch] = useState('');
  const [stays, setStays] = useState<StayCard[]>(fallbackStays);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let active = true;

    async function load() {
      try {
        const res = await fetch('/api/v1/stays');
        const json = await res.json();
        if (!active) {
          return;
        }

        const items = Array.isArray(json.data?.data) ? json.data.data : Array.isArray(json.data) ? json.data : [];
        if (items.length > 0) {
          setStays(items);
        }
      } catch {
        // Keep fallback cards when the API is not reachable from the frontend dev server.
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    }

    load();

    return () => {
      active = false;
    };
  }, []);

  const filtered = stays.filter((stay) => {
    const term = search.trim().toLowerCase();
    if (!term) {
      return true;
    }

    return [stay.title, stay.city, stay.short_description ?? '']
      .join(' ')
      .toLowerCase()
      .includes(term);
  });

  return (
    <main className="min-h-screen bg-[#fbf7f2] px-4 py-8 md:px-8">
      <div className="mx-auto max-w-6xl">
        <section className="rounded-[2rem] bg-white p-6 shadow-sm md:p-8">
          <p className="text-sm uppercase tracking-[0.25em] text-teal-700">Stays</p>
          <div className="mt-4 grid gap-4 md:grid-cols-[1.1fr_0.9fr] md:items-end">
            <div>
              <h1 className="text-4xl font-semibold text-slate-950">Book stays built for local weekends and full-island itineraries.</h1>
              <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                Browse approved properties, compare city clusters, and move straight into booking confirmation after payment.
              </p>
            </div>
            <div className="rounded-[1.5rem] bg-[#0f766e] p-5 text-white">
              <p className="text-sm text-teal-100">Live inventory</p>
              <p className="mt-2 text-3xl font-semibold">{loading ? '...' : filtered.length.toString().padStart(2, '0')}</p>
              <p className="mt-2 text-sm text-teal-100">Curated listings visible to customers</p>
            </div>
          </div>

          <div className="mt-6 flex items-center gap-3 rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-3">
            <Search className="h-5 w-5 text-slate-400" />
            <input
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Search stays by city, property, or vibe"
              className="w-full bg-transparent text-sm text-slate-900 outline-none placeholder:text-slate-400"
            />
          </div>
        </section>

        <section className="mt-8 grid gap-5 md:grid-cols-3">
          {filtered.map((stay) => (
            <article key={stay.id} className="overflow-hidden rounded-[1.75rem] bg-white shadow-sm">
              <div className="h-44 bg-[linear-gradient(135deg,#082f49,#0f766e,#99f6e4)]" />
              <div className="p-5">
                <div className="flex items-start justify-between gap-4">
                  <div>
                    <h2 className="text-xl font-semibold text-slate-950">{stay.title}</h2>
                    <p className="mt-1 flex items-center gap-1 text-sm text-slate-500">
                      <MapPin className="h-4 w-4" />
                      {stay.city}
                    </p>
                  </div>
                  <span className="flex items-center gap-1 rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700">
                    <Star className="h-3.5 w-3.5 fill-current" /> 4.8
                  </span>
                </div>
                <p className="mt-4 text-sm leading-6 text-slate-600">{stay.short_description ?? 'Approved stay listing ready for booking.'}</p>
                <div className="mt-5 flex items-center justify-between">
                  <div>
                    <p className="text-xs uppercase tracking-[0.2em] text-slate-400">From</p>
                    <p className="text-xl font-semibold text-slate-950">LKR {Number(stay.base_price).toLocaleString()}</p>
                  </div>
                  <Link
                    href={`/${locale}/stays/${stay.id}`}
                    className="rounded-full bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                  >
                    Reserve
                  </Link>
                </div>
              </div>
            </article>
          ))}
        </section>
      </div>
    </main>
  );
}
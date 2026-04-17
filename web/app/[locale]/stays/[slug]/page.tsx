import type { Metadata } from 'next';
import { notFound } from 'next/navigation';
import Link from 'next/link';
import { MapPin, Star, Users, Calendar, CreditCard, Building2, Banknote, Sparkles } from 'lucide-react';

type Props = {
  params: Promise<{ locale: string; slug: string }>;
};

type Stay = {
  id: string;
  title: string;
  slug: string;
  city: string;
  base_price: number;
  short_description?: string;
  amenities?: string[];
  rating?: number;
  review_count?: number;
};

const demoStays: Record<string, Stay> = {
  'ella-ridge-cabanas': {
    id: 'demo-1',
    title: 'Ella Ridge Cabanas',
    slug: 'ella-ridge-cabanas',
    city: 'Ella',
    base_price: 28500,
    short_description: 'Canopy cabins perched on the ridge with front-row sunrise views and locally sourced breakfast every morning.',
    amenities: ['Free Wi-Fi', 'Breakfast included', 'Tea trail access', 'Hot water', 'Parking'],
    rating: 4.9,
    review_count: 84,
  },
  'galle-fort-courtyard': {
    id: 'demo-2',
    title: 'Galle Fort Courtyard',
    slug: 'galle-fort-courtyard',
    city: 'Galle',
    base_price: 32000,
    short_description: 'Restored Dutch colonial rooms inside the UNESCO-listed Galle Fort wall. Walk to the lighthouse in under two minutes.',
    amenities: ['Air conditioning', 'Heritage tour', 'In-room dining', 'Free Wi-Fi', 'Rooftop access'],
    rating: 4.8,
    review_count: 121,
  },
  'sigiriya-canopy-lodge': {
    id: 'demo-3',
    title: 'Sigiriya Canopy Lodge',
    slug: 'sigiriya-canopy-lodge',
    city: 'Sigiriya',
    base_price: 24750,
    short_description: 'Pool villas surrounded by forest close to Pidurangala Rock and Sigiriya. Wildlife sightings are common at dusk.',
    amenities: ['Private pool', 'Free Wi-Fi', 'Safari transfers', 'Breakfast', 'Air conditioning'],
    rating: 4.7,
    review_count: 63,
  },
};

async function fetchStay(slug: string): Promise<Stay | null> {
  try {
    const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL ?? 'https://api.grabber.lk'}/api/v1/stays/${slug}`, {
      next: { revalidate: 300 },
    });
    if (!res.ok) throw new Error('not found');
    const json = await res.json();
    return json.data ?? null;
  } catch {
    return demoStays[slug] ?? null;
  }
}

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const { slug } = await params;
  const stay = await fetchStay(slug);
  if (!stay) return { title: 'Stay not found — Grabber Hub LK' };

  return {
    title: `${stay.title} in ${stay.city} — Grabber Hub LK`,
    description: stay.short_description ?? `Book ${stay.title} in ${stay.city} on Grabber Hub LK.`,
  };
}

export default async function StayDetailPage({ params }: Props) {
  const { locale, slug } = await params;
  const stay = await fetchStay(slug);

  if (!stay) notFound();

  const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'LodgingBusiness',
    name: stay.title,
    address: { '@type': 'PostalAddress', addressLocality: stay.city, addressCountry: 'LK' },
    priceRange: `LKR ${stay.base_price.toLocaleString()} / night`,
    ...(stay.rating ? { aggregateRating: { '@type': 'AggregateRating', ratingValue: stay.rating, reviewCount: stay.review_count ?? 1 } } : {}),
  };

  return (
    <>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }} />

      <main className="min-h-screen bg-[#fbf7f2] px-4 py-8 md:px-8">
        <div className="mx-auto max-w-6xl">

          {/* Breadcrumb */}
          <nav className="mb-6 flex items-center gap-2 text-sm text-slate-500">
            <Link href={`/${locale}/stays`} className="hover:text-slate-900">Stays</Link>
            <span>/</span>
            <span className="text-slate-900">{stay.city}</span>
            <span>/</span>
            <span className="text-slate-900 font-medium">{stay.title}</span>
          </nav>

          <div className="grid gap-8 lg:grid-cols-[1fr_420px]">

            {/* Left column */}
            <div className="space-y-6">

              {/* Hero */}
              <div className="overflow-hidden rounded-[2rem] bg-[linear-gradient(135deg,#082f49,#0f766e,#99f6e4)] shadow-lg">
                <div className="h-72 md:h-96" />
              </div>

              {/* Property header */}
              <div className="rounded-[2rem] bg-white p-6 shadow-sm">
                <div className="flex flex-wrap items-start justify-between gap-4">
                  <div>
                    <p className="flex items-center gap-1.5 text-sm text-slate-500">
                      <MapPin className="h-4 w-4" />
                      {stay.city}, Sri Lanka
                    </p>
                    <h1 className="mt-2 text-3xl font-semibold text-slate-950">{stay.title}</h1>
                  </div>
                  {stay.rating && (
                    <div className="flex items-center gap-1.5 rounded-full bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700">
                      <Star className="h-4 w-4 fill-current" />
                      {stay.rating} · {stay.review_count} reviews
                    </div>
                  )}
                </div>
                <p className="mt-5 text-base leading-7 text-slate-600">{stay.short_description}</p>
              </div>

              {/* Amenities */}
              {stay.amenities && stay.amenities.length > 0 && (
                <div className="rounded-[2rem] bg-white p-6 shadow-sm">
                  <h2 className="text-xl font-semibold text-slate-950">What this place offers</h2>
                  <ul className="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    {stay.amenities.map((a) => (
                      <li key={a} className="flex items-center gap-2 text-sm text-slate-700">
                        <span className="h-2 w-2 rounded-full bg-teal-600" />
                        {a}
                      </li>
                    ))}
                  </ul>
                </div>
              )}

              {/* Location placeholder */}
              <div className="overflow-hidden rounded-[2rem] bg-white shadow-sm">
                <div className="h-52 bg-slate-100 flex items-center justify-center text-slate-400 text-sm">
                  <span>Map — {stay.city}</span>
                </div>
                <div className="p-5">
                  <p className="text-sm text-slate-600">Exact address shown after booking confirmation.</p>
                </div>
              </div>
            </div>

            {/* Right column — booking widget */}
            <div className="lg:sticky lg:top-8 h-fit">
              <BookingWidget stay={stay} locale={locale} />
            </div>
          </div>
        </div>
      </main>
    </>
  );
}

// ─── Booking widget (client interaction is handled by the mobile app;
//     web widget shows pricing and links to app or payment flow) ───────────────

type BookingWidgetProps = { stay: Stay; locale: string };

function BookingWidget({ stay, locale }: BookingWidgetProps) {
  const handlingFeeAmount = Math.round(stay.base_price * 0.03);

  return (
    <div className="space-y-4">
      {/* Price card */}
      <div className="rounded-[2rem] bg-white p-6 shadow-sm">
        <p className="text-3xl font-semibold text-slate-950">
          LKR {stay.base_price.toLocaleString()}
          <span className="text-base font-normal text-slate-500"> / night</span>
        </p>

        {/* Date placeholder inputs */}
        <div className="mt-5 grid grid-cols-2 gap-3">
          <div className="rounded-[1rem] border border-slate-200 px-4 py-3">
            <p className="text-xs font-medium uppercase tracking-widest text-slate-400">Check-in</p>
            <p className="mt-1 text-sm font-semibold text-slate-900">Select date</p>
          </div>
          <div className="rounded-[1rem] border border-slate-200 px-4 py-3">
            <p className="text-xs font-medium uppercase tracking-widest text-slate-400">Check-out</p>
            <p className="mt-1 text-sm font-semibold text-slate-900">Select date</p>
          </div>
        </div>

        <div className="mt-3 flex items-center gap-2 rounded-[1rem] border border-slate-200 px-4 py-3">
          <Users className="h-4 w-4 text-slate-400" />
          <span className="text-sm text-slate-600">2 guests</span>
        </div>

        {/* CTA */}
        <Link
          href={`/${locale}/dashboard`}
          className="mt-5 block rounded-full bg-slate-950 px-5 py-4 text-center text-sm font-semibold text-white transition hover:bg-slate-800"
        >
          Reserve now
        </Link>
        <p className="mt-3 text-center text-xs text-slate-400">You won&apos;t be charged yet</p>
      </div>

      {/* Payment methods */}
      <div className="rounded-[2rem] bg-white p-6 shadow-sm">
        <h3 className="font-semibold text-slate-900">Payment options</h3>
        <ul className="mt-4 space-y-3">
          <PaymentBadge
            icon={<CreditCard className="h-4 w-4" />}
            label="Card (WebxPay)"
            note={`+LKR ${handlingFeeAmount.toLocaleString()} handling fee`}
          />
          <PaymentBadge
            icon={<Building2 className="h-4 w-4" />}
            label="Bank Transfer"
            note="48 h to transfer · Grabber account"
          />
          <PaymentBadge
            icon={<Banknote className="h-4 w-4" />}
            label="Cash"
            note="Grabber office or authorised agent"
          />
        </ul>
      </div>

      {/* Pearl Points */}
      <div className="flex items-center gap-3 rounded-[1.5rem] bg-amber-50 px-5 py-4">
        <Sparkles className="h-5 w-5 text-amber-600 shrink-0" />
        <p className="text-sm text-amber-800">
          <strong>Pearl Points</strong> — redeem at checkout for up to 30% off. Balance shown in the Grabber app.
        </p>
      </div>
    </div>
  );
}

function PaymentBadge({ icon, label, note }: { icon: React.ReactNode; label: string; note: string }) {
  return (
    <li className="flex items-center gap-3 rounded-[1rem] bg-slate-50 px-4 py-3">
      <span className="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-900 text-white shrink-0">
        {icon}
      </span>
      <div>
        <p className="text-sm font-semibold text-slate-900">{label}</p>
        <p className="text-xs text-slate-500">{note}</p>
      </div>
    </li>
  );
}

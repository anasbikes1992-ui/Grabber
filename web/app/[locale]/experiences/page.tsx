import Link from 'next/link';

const API_BASE = process.env.NEXT_PUBLIC_API_URL ?? 'https://api.grabber.lk';

type ExperienceItem = {
  id: string;
  title: string;
  city?: string;
  category?: string;
  base_price?: number;
  short_description?: string;
};

const fallbackExperiences: ExperienceItem[] = [
  {
    id: 'demo-whale-watching',
    title: 'Mirissa Whale Watching Morning Tour',
    city: 'Mirissa',
    category: 'Wildlife',
    base_price: 17500,
    short_description: 'Guided morning cruise with onboard naturalist and breakfast.',
  },
  {
    id: 'demo-kandy-cooking',
    title: 'Kandy Heritage Cooking Workshop',
    city: 'Kandy',
    category: 'Cultural',
    base_price: 9500,
    short_description: 'Hands-on local cuisine session with market visit and recipe pack.',
  },
];

async function loadExperiences(): Promise<ExperienceItem[]> {
  try {
    const res = await fetch(`${API_BASE}/api/v1/experiences`, { next: { revalidate: 120 } });
    if (!res.ok) throw new Error('Failed to load experiences');
    const json = await res.json();

    const data = Array.isArray(json?.data?.data)
      ? json.data.data
      : Array.isArray(json?.data)
        ? json.data
        : [];

    if (!data.length) return fallbackExperiences;
    return data as ExperienceItem[];
  } catch {
    return fallbackExperiences;
  }
}

export default async function ExperiencesPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const experiences = await loadExperiences();

  return (
    <main className="min-h-screen bg-[#f7fdf8] px-4 py-8 md:px-8">
      <div className="mx-auto max-w-6xl">
        <section className="rounded-3xl bg-white p-6 shadow-sm md:p-8">
          <p className="text-sm uppercase tracking-[0.25em] text-emerald-700">Experiences</p>
          <h1 className="mt-3 text-4xl font-semibold text-slate-950">Book curated tours and adventures across Sri Lanka.</h1>
          <p className="mt-3 text-sm text-slate-600">Find approved providers, compare categories, and reserve your spot.</p>
        </section>

        <section className="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
          {experiences.map((item) => (
            <article key={item.id} className="rounded-3xl bg-white shadow-sm overflow-hidden">
              <div className="h-40 bg-[linear-gradient(135deg,#052e16,#15803d,#86efac)]" />
              <div className="p-5">
                <p className="text-xs uppercase tracking-widest text-emerald-700">{item.category ?? 'Experience'}</p>
                <h2 className="mt-2 text-xl font-semibold text-slate-950">{item.title}</h2>
                <p className="mt-2 text-sm text-slate-500">{item.city ?? 'Sri Lanka'}</p>
                <p className="mt-3 text-sm leading-6 text-slate-600">{item.short_description ?? 'Experience listing available on Grabber.'}</p>
                <div className="mt-5 flex items-center justify-between">
                  <p className="text-sm font-semibold text-slate-900">LKR {Number(item.base_price ?? 0).toLocaleString()}</p>
                  <Link
                    href={`/${locale}/experiences/${item.id}`}
                    className="rounded-full bg-slate-950 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
                  >
                    View
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

import { notFound } from 'next/navigation';

const API_BASE = process.env.NEXT_PUBLIC_API_URL ?? 'https://api.grabber.lk';

type ExperienceDetails = {
  id: string;
  title: string;
  description?: string;
  city?: string;
  category?: string;
  base_price?: number;
  duration_hours?: number;
  max_group_size?: number;
};

const fallbackExperience: ExperienceDetails = {
  id: 'demo',
  title: 'Sri Lanka Signature Experience',
  description: 'A curated local experience with certified guides and transport options.',
  city: 'Sri Lanka',
  category: 'Cultural',
  base_price: 10000,
  duration_hours: 4,
  max_group_size: 10,
};

async function loadExperience(id: string): Promise<ExperienceDetails | null> {
  try {
    const res = await fetch(`${API_BASE}/api/v1/experiences/${id}`, { next: { revalidate: 120 } });
    if (!res.ok) throw new Error('Failed to load experience');
    const json = await res.json();
    return (json?.data as ExperienceDetails) ?? null;
  } catch {
    return id.startsWith('demo') ? fallbackExperience : null;
  }
}

export default async function ExperienceDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const experience = await loadExperience(id);

  if (!experience) {
    notFound();
  }

  return (
    <main className="min-h-screen bg-[#f7fdf8] px-4 py-8 md:px-8">
      <div className="mx-auto max-w-4xl space-y-6">
        <section className="rounded-3xl bg-white p-6 shadow-sm md:p-8">
          <p className="text-sm uppercase tracking-[0.25em] text-emerald-700">{experience.category ?? 'Experience'}</p>
          <h1 className="mt-3 text-4xl font-semibold text-slate-950">{experience.title}</h1>
          <p className="mt-3 text-sm text-slate-600">{experience.city ?? 'Sri Lanka'}</p>
          <p className="mt-4 text-sm leading-7 text-slate-700">{experience.description ?? 'Experience details are available in the Grabber app.'}</p>
        </section>

        <section className="rounded-3xl bg-white p-6 shadow-sm md:p-8">
          <h2 className="text-xl font-semibold text-slate-950">Experience Snapshot</h2>
          <div className="mt-4 grid gap-3 sm:grid-cols-3">
            <div className="rounded-2xl border border-slate-200 p-4">
              <p className="text-xs uppercase tracking-widest text-slate-400">From</p>
              <p className="mt-2 text-lg font-semibold text-slate-900">LKR {Number(experience.base_price ?? 0).toLocaleString()}</p>
            </div>
            <div className="rounded-2xl border border-slate-200 p-4">
              <p className="text-xs uppercase tracking-widest text-slate-400">Duration</p>
              <p className="mt-2 text-lg font-semibold text-slate-900">{experience.duration_hours ?? '-'} hrs</p>
            </div>
            <div className="rounded-2xl border border-slate-200 p-4">
              <p className="text-xs uppercase tracking-widest text-slate-400">Group Size</p>
              <p className="mt-2 text-lg font-semibold text-slate-900">Up to {experience.max_group_size ?? '-'}</p>
            </div>
          </div>
        </section>
      </div>
    </main>
  );
}

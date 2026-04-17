import {useTranslations} from 'next-intl';

export default function LocaleHomePage() {
  const t = useTranslations('home');

  return (
    <main className="mx-auto flex min-h-screen w-full max-w-5xl flex-col justify-center gap-4 px-6">
      <h1 className="text-4xl font-bold tracking-tight">{t('headline')}</h1>
      <p className="text-lg text-zinc-700">{t('subhead')}</p>
    </main>
  );
}

'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useTranslations } from 'next-intl';

type TaxiCategory = {
  id: string | number;
  name: string;
  base_fare?: number;
  per_km_rate?: number;
};

type FareEstimate = {
  category: TaxiCategory;
  distance_km: number;
  surge_multiplier: number;
  total_fare: number;
  pearl_points_earn: number;
};

const API_BASE = process.env.NEXT_PUBLIC_API_URL ?? 'https://api.grabber.lk';

export default function TaxiPage() {
  useTranslations();
  const [selectedCategory, setSelectedCategory] = useState<string>('');
  const [pickup, setPickup] = useState('');
  const [dropoff, setDropoff] = useState('');
  const [estimates, setEstimates] = useState<FareEstimate[]>([]);
  const [loading, setLoading] = useState(false);
  const [surge, setSurge] = useState(1.0);
  const [error, setError] = useState('');

  const categories = estimates.map((item) => item.category);
  const effectiveSelectedCategory = selectedCategory || (categories.length > 0 ? String(categories[0].id) : '');

  const iconForCategory = (name: string) => {
    const n = name.toLowerCase();
    if (n.includes('van')) return '🚐';
    if (n.includes('suv') || n.includes('mini')) return '🚙';
    return '🚗';
  };

  const parseCoords = (value: string): { lat: number; lng: number } | null => {
    const parts = value.split(',').map((part) => part.trim());
    if (parts.length !== 2) return null;

    const lat = Number(parts[0]);
    const lng = Number(parts[1]);

    if (Number.isNaN(lat) || Number.isNaN(lng)) return null;
    if (lat < -90 || lat > 90 || lng < -180 || lng > 180) return null;

    return { lat, lng };
  };

  const getEstimate = async () => {
    setError('');

    if (!pickup || !dropoff || !effectiveSelectedCategory) {
      setError('Enter pickup, dropoff, and category.');
      return;
    }

    const origin = parseCoords(pickup);
    const destination = parseCoords(dropoff);

    if (!origin || !destination) {
      setError('Use coordinate format: lat,lng (example: 6.9271,79.8612).');
      return;
    }

    setLoading(true);
    try {
      const url = new URL(`${API_BASE}/api/v1/taxi/fare/all-categories`);
      url.searchParams.set('origin_lat', String(origin.lat));
      url.searchParams.set('origin_lng', String(origin.lng));
      url.searchParams.set('dest_lat', String(destination.lat));
      url.searchParams.set('dest_lng', String(destination.lng));

      const res = await fetch(url.toString(), {
        method: 'GET',
        headers: { Accept: 'application/json' },
        cache: 'no-store',
      });

      const json = await res.json();
      if (!res.ok || !json?.success || !Array.isArray(json?.data)) {
        throw new Error(json?.message ?? 'Failed to get fare estimates');
      }

      const nextEstimates = json.data as FareEstimate[];
      setEstimates(nextEstimates);

      if (nextEstimates.length > 0) {
        setSurge(Number(nextEstimates[0].surge_multiplier) || 1.0);
        if (!nextEstimates.some((item) => String(item.category.id) === effectiveSelectedCategory)) {
          setSelectedCategory(String(nextEstimates[0].category.id));
        }
      }
    } catch (e) {
      const msg = e instanceof Error ? e.message : 'Failed to get estimate';
      setError(msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-amber-50 to-orange-50 pt-8 pb-16">
      <div className="max-w-2xl mx-auto px-4">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-4xl font-bold text-gray-900 mb-2">Grabber Taxi</h1>
          <p className="text-gray-600">Quick, reliable, and affordable rides across Sri Lanka</p>
        </div>

        {/* Category selector */}
        <div className="grid grid-cols-5 gap-3 mb-8">
          {categories.map((cat) => (
            <button
              key={String(cat.id)}
              onClick={() => setSelectedCategory(String(cat.id))}
              className={`p-4 rounded-lg font-semibold transition-all ${
                effectiveSelectedCategory === String(cat.id)
                  ? 'bg-amber-500 text-white shadow-lg scale-105'
                  : 'bg-white text-gray-900 border border-gray-200 hover:border-amber-300'
              }`}
            >
              <div className="text-3xl mb-1">{iconForCategory(cat.name)}</div>
              <div className="text-xs">{cat.name}</div>
              <div className="text-xs font-normal text-opacity-80">LKR {cat.per_km_rate ?? '-'} /km</div>
            </button>
          ))}
        </div>

        {/* Location inputs */}
        <div className="space-y-4 mb-6">
          <input
            type="text"
            placeholder="Pickup coordinates (lat,lng)"
            value={pickup}
            onChange={(e) => setPickup(e.target.value)}
            className="w-full px-4 py-3 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-amber-500"
          />
          <input
            type="text"
            placeholder="Dropoff coordinates (lat,lng)"
            value={dropoff}
            onChange={(e) => setDropoff(e.target.value)}
            className="w-full px-4 py-3 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-amber-500"
          />
        </div>

        {error && (
          <p className="mb-4 text-sm text-red-600">{error}</p>
        )}

        {/* Get estimate button */}
        <button
          onClick={getEstimate}
          disabled={!pickup || !dropoff || loading}
          className="w-full py-3 bg-amber-500 text-white rounded-lg font-bold hover:bg-amber-600 disabled:opacity-50 disabled:cursor-not-allowed mb-6 transition-all"
        >
          {loading ? 'Getting estimate...' : 'Get Fare Estimate'}
        </button>

        {/* Fare estimates panel */}
        {estimates.length > 0 && (
          <div className="bg-white rounded-xl p-6 mb-8 shadow-md border border-amber-200">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-lg font-bold">Fare Estimates</h2>
              {surge > 1.0 && (
                <span className="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold">
                  {surge.toFixed(1)}x Surge
                </span>
              )}
            </div>

            <div className="space-y-3">
              {estimates.map((est) => {
                const categoryId = String(est.category.id);
                return (
                  <div
                    key={categoryId}
                    onClick={() => setSelectedCategory(categoryId)}
                    className={`p-4 rounded-lg border-2 transition-all cursor-pointer ${
                      effectiveSelectedCategory === categoryId
                        ? 'bg-amber-50 border-amber-500'
                        : 'bg-gray-50 border-gray-200 hover:border-amber-300'
                    }`}
                  >
                    <div className="flex justify-between items-start">
                      <div>
                        <div className="font-semibold">{est.category.name} {iconForCategory(est.category.name)}</div>
                        <div className="text-xs text-gray-600">~{est.distance_km} km</div>
                      </div>
                      <div className="text-right">
                        <div className="text-2xl font-bold text-amber-600">LKR {Number(est.total_fare).toFixed(0)}</div>
                        <div className="text-xs text-gray-500">Earn {est.pearl_points_earn} Pearl Points</div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>

            <div className="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <p className="text-sm text-blue-900">
                <strong>💡 Tip:</strong> Download our app for faster bookings, real-time tracking, and exclusive rewards!
              </p>
            </div>
          </div>
        )}

        {/* CTA */}
        <div className="space-y-4">
          <button className="w-full py-4 bg-black text-white rounded-lg font-bold text-lg hover:bg-gray-900 transition-all">
            Download Grabber App
          </button>
          <p className="text-center text-sm text-gray-600">
            Available on{' '}
            <Link href="https://www.apple.com/app-store/" className="text-amber-600 font-semibold hover:underline" target="_blank" rel="noopener noreferrer">
              iOS
            </Link>{' '}
            and{' '}
            <Link href="https://play.google.com/store" className="text-amber-600 font-semibold hover:underline" target="_blank" rel="noopener noreferrer">
              Android
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
}

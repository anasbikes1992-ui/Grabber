'use client';

import { useState, useEffect } from 'react';
import { useParams } from 'next/navigation';

type TripProfile = {
  name?: string;
};

type TripParty = {
  profile?: TripProfile;
};

type TaxiTrip = {
  id: string;
  status: string;
  origin_address?: string;
  dest_address?: string;
  final_fare?: number;
  estimated_fare?: number;
  distance_km?: number;
  driver?: TripParty & { driver_rating?: number };
  customer?: TripParty;
};

const API_BASE = process.env.NEXT_PUBLIC_API_URL ?? 'https://api.grabber.lk';

export default function RideTrackingPage() {
  const params = useParams();
  const rideId = params.rideId as string;
  const hasRideId = typeof rideId === 'string' && rideId.length > 0;

  const [trip, setTrip] = useState<TaxiTrip | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!hasRideId) {
      return;
    }

    let active = true;

    const fetchTrip = async (isInitial = false) => {
      try {
        const token = typeof window !== 'undefined' ? localStorage.getItem('grabber_token') : null;
        const headers: Record<string, string> = {
          Accept: 'application/json',
        };

        if (token) {
          headers.Authorization = `Bearer ${token}`;
        }

        const res = await fetch(`${API_BASE}/api/v1/taxi/rides/${rideId}`, {
          method: 'GET',
          headers,
          cache: 'no-store',
        });

        const json = await res.json();
        if (!res.ok || !json?.trip) {
          throw new Error(json?.message ?? 'Unable to load trip');
        }

        if (!active) return;
        setTrip(json.trip as TaxiTrip);
        setError('');
      } catch (e) {
        if (!active) return;
        const message = e instanceof Error ? e.message : 'Failed to fetch trip';
        setError(message);
        if (isInitial) {
          setTrip(null);
        }
      } finally {
        if (active && isInitial) {
          setLoading(false);
        }
      }
    };

    fetchTrip(true);
    const timer = setInterval(() => {
      fetchTrip(false);
    }, 5000);

    return () => {
      active = false;
      clearInterval(timer);
    };
  }, [hasRideId, rideId]);

  if (!hasRideId) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <p className="text-gray-600">Invalid ride ID</p>
      </div>
    );
  }

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-4 border-amber-500 border-t-transparent"></div>
      </div>
    );
  }

  if (!trip) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <p className="text-gray-600">{error || 'Ride not found'}</p>
      </div>
    );
  }

  const statusLabels: Record<string, string> = {
    searching: '🔍 Finding your driver...',
    accepted: '📍 Driver is on the way',
    driver_arrived: '✋ Driver has arrived',
    in_transit: '🚗 On the way to your destination',
    completed: '✅ Ride complete!',
  };

  return (
    <div className="min-h-screen bg-gray-50 pt-6 pb-12">
      <div className="max-w-2xl mx-auto px-4">
        {/* Status header */}
        <div className="bg-white rounded-lg p-6 shadow-sm mb-6">
          <h1 className="text-2xl font-bold mb-2">Live Tracking</h1>
          <p className="text-lg text-amber-600 font-semibold">
            {statusLabels[trip.status] || trip.status}
          </p>
        </div>

        {/* Map section */}
        <div className="bg-gray-300 rounded-lg h-64 mb-6 flex items-center justify-center text-gray-700 text-sm px-4 text-center">
          Live map integration depends on driver location stream. Trip updates are refreshing every 5 seconds.
        </div>

        {error && <p className="mb-4 text-sm text-red-600">{error}</p>}

        {/* Driver info */}
        <div className="bg-white rounded-lg p-6 shadow-sm mb-6">
          <h2 className="font-bold text-lg mb-4">Driver Details</h2>
          <div className="flex items-center gap-4">
            <div className="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center text-2xl font-bold">
              {trip.driver?.profile?.name?.[0] || 'D'}
            </div>
            <div className="flex-1">
              <p className="font-semibold text-lg">{trip.driver?.profile?.name}</p>
              <div className="flex items-center gap-1">
                <span>⭐ {trip.driver?.driver_rating}</span>
              </div>
            </div>
            <button className="px-4 py-2 bg-green-500 text-white rounded-lg font-semibold hover:bg-green-600">
              📞 Call
            </button>
          </div>
        </div>

        {/* Trip details */}
        <div className="bg-white rounded-lg p-6 shadow-sm">
          <h2 className="font-bold text-lg mb-4">Trip Details</h2>
          <div className="space-y-4">
            <div className="flex justify-between items-center pb-4 border-b">
              <span className="text-gray-600">From</span>
              <span className="font-semibold">{trip.origin_address}</span>
            </div>
            <div className="flex justify-between items-center pb-4 border-b">
              <span className="text-gray-600">To</span>
              <span className="font-semibold">{trip.dest_address}</span>
            </div>
            <div className="flex justify-between items-center pb-4 border-b">
              <span className="text-gray-600">Distance</span>
              <span className="font-semibold">{trip.distance_km} km</span>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-gray-600">Estimated Fare</span>
              <span className="text-2xl font-bold text-amber-600">LKR {trip.final_fare ?? trip.estimated_fare ?? 0}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import 'package:go_router/go_router.dart';
import 'package:latlong2/latlong.dart';

import '../services/taxi_driver_service.dart';

class DriverHomeScreen extends ConsumerStatefulWidget {
  const DriverHomeScreen({super.key});

  @override
  ConsumerState<DriverHomeScreen> createState() => _DriverHomeScreenState();
}

class _DriverHomeScreenState extends ConsumerState<DriverHomeScreen> {
  final _mapController = MapController();
  Timer? _locationTimer;
  StreamSubscription<Position>? _locationStream;

  bool _isOnline = false;
  LatLng? _currentPosition;
  double _todayEarnings = 0.0;
  bool _isUpdatingLocation = false;

  @override
  void initState() {
    super.initState();
    _initLocation();
    _loadEarnings();
  }

  @override
  void dispose() {
    _locationTimer?.cancel();
    _locationStream?.cancel();
    _mapController.dispose();
    super.dispose();
  }

  Future<void> _initLocation() async {
    final permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      await Geolocator.requestPermission();
    }

    try {
      final pos = await Geolocator.getCurrentPosition();
      setState(() {
        _currentPosition = LatLng(pos.latitude, pos.longitude);
      });
      _mapController.move(_currentPosition!, 16);
    } catch (_) {}
  }

  Future<void> _loadEarnings() async {
    try {
      final data = await TaxiDriverService.getTodayEarnings();
      setState(() => _todayEarnings = data['total'] as double? ?? 0);
    } catch (_) {}
  }

  Future<void> _toggleOnline() async {
    final newStatus = !_isOnline;
    try {
      await TaxiDriverService.setOnlineStatus(newStatus);
      setState(() => _isOnline = newStatus);

      if (newStatus) {
        // Start location updates
        _startLocationTracking();
      } else {
        // Stop location updates
        _locationTimer?.cancel();
        _locationStream?.cancel();
      }
      _showSnack(newStatus ? 'You are online' : 'You are offline');
    } catch (e) {
      _showSnack('Failed to update status');
    }
  }

  void _startLocationTracking() {
    // Send location every 10s when online
    _locationTimer = Timer.periodic(const Duration(seconds: 10), (_) {
      _updateLocation();
    });

    // Also listen to real-time position changes
    _locationStream = Geolocator.getPositionStream().listen((pos) {
      setState(() => _currentPosition = LatLng(pos.latitude, pos.longitude));
    });
  }

  Future<void> _updateLocation() async {
    if (!_isOnline || _currentPosition == null) return;

    setState(() => _isUpdatingLocation = true);
    try {
      await TaxiDriverService.updateLocation(
        lat: _currentPosition!.latitude,
        lng: _currentPosition!.longitude,
      );
    } catch (_) {
    } finally {
      if (mounted) setState(() => _isUpdatingLocation = false);
    }
  }

  void _showSnack(String msg) =>
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          // ── Map ────────────────────────────────────────────────────────────
          FlutterMap(
            mapController: _mapController,
            options: MapOptions(
              initialCenter: _currentPosition ?? const LatLng(6.9271, 79.8612),
              initialZoom: 16,
            ),
            children: [
              TileLayer(
                urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                userAgentPackageName: 'lk.grabber.provider',
              ),
              MarkerLayer(
                markers: [
                  if (_currentPosition != null)
                    Marker(
                      point: _currentPosition!,
                      width: 40,
                      height: 40,
                      child: const Icon(Icons.my_location, color: Colors.blue, size: 40),
                    ),
                ],
              ),
            ],
          ),

          // ── Top bar with online/offline toggle ─────────────────────────────
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Text('Welcome Back', style: TextStyle(fontSize: 14, color: Colors.grey)),
                      Text(
                        _isOnline ? 'You are Online' : 'You are Offline',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: _isOnline ? Colors.green : Colors.grey,
                        ),
                      ),
                    ],
                  ),
                  AnimatedContainer(
                    duration: const Duration(milliseconds: 300),
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    decoration: BoxDecoration(
                      color: _isOnline ? Colors.green : Colors.grey,
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: GestureDetector(
                      onTap: _toggleOnline,
                      child: Text(
                        _isOnline ? 'ONLINE' : 'OFFLINE',
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                          fontSize: 12,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),

          // ── Bottom earnings card ───────────────────────────────────────────
          Align(
            alignment: Alignment.bottomCenter,
            child: Container(
              margin: const EdgeInsets.all(16),
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                boxShadow: const [BoxShadow(blurRadius: 12, color: Colors.black26)],
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Text('Today\'s Earnings', style: TextStyle(color: Colors.grey, fontSize: 12)),
                  const SizedBox(height: 4),
                  Text(
                    'LKR ${_todayEarnings.toStringAsFixed(0)}',
                    style: const TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: Colors.green),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: _NavButton(
                          icon: Icons.home,
                          label: 'Home',
                          onTap: () {},
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: _NavButton(
                          icon: Icons.trending_up,
                          label: 'Earnings',
                          onTap: () => context.push('/commission'),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: _NavButton(
                          icon: Icons.card_giftcard,
                          label: 'Quests',
                          onTap: () => context.push('/quests'),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: _NavButton(
                          icon: Icons.person,
                          label: 'Profile',
                          onTap: () {
                            // TODO: navigate to profile
                          },
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _NavButton extends StatelessWidget {
  const _NavButton({required this.icon, required this.label, required this.onTap});

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: Colors.amber[50],
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: Colors.amber[200]!),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: Colors.amber[700], size: 24),
            const SizedBox(height: 4),
            Text(label, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w600)),
          ],
        ),
      ),
    );
  }
}

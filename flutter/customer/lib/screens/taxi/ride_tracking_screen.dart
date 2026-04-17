import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:latlong2/latlong.dart';

import '../../services/taxi_service.dart';

class RideTrackingScreen extends ConsumerStatefulWidget {
  final String rideId;
  const RideTrackingScreen({super.key, required this.rideId});

  @override
  ConsumerState<RideTrackingScreen> createState() => _RideTrackingScreenState();
}

class _RideTrackingScreenState extends ConsumerState<RideTrackingScreen> {
  final _mapController = MapController();
  Timer? _pollTimer;
  Map<String, dynamic>? _trip;
  bool _cancelling = false;

  static const _pollInterval = Duration(seconds: 5);

  static const _statusLabels = {
    'searching':      'Finding your driver...',
    'accepted':       'Driver is on the way',
    'driver_arrived': 'Driver has arrived',
    'in_transit':     'On the way to your destination',
    'completed':      'Ride complete!',
    'cancelled':      'Ride cancelled',
    'sos':            'SOS — Emergency services alerted',
  };

  @override
  void initState() {
    super.initState();
    _fetchTrip();
    _pollTimer = Timer.periodic(_pollInterval, (_) => _fetchTrip());
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    _mapController.dispose();
    super.dispose();
  }

  Future<void> _fetchTrip() async {
    try {
      final data = await TaxiService.getTrip(widget.rideId);
      final trip = data['trip'] as Map<String, dynamic>;

      if (!mounted) return;
      setState(() => _trip = trip);

      if (trip['status'] == 'completed') {
        _pollTimer?.cancel();
        await Future.delayed(const Duration(milliseconds: 500));
        if (mounted) context.pushReplacement('/taxi/complete', extra: widget.rideId);
      }
    } catch (_) {}
  }

  Future<void> _cancelRide() async {
    setState(() => _cancelling = true);
    try {
      await TaxiService.cancelRide(widget.rideId, reason: 'Cancelled by customer');
      if (mounted) context.pop();
    } catch (_) {
      _showSnack('Could not cancel. Please try again.');
    } finally {
      if (mounted) setState(() => _cancelling = false);
    }
  }

  void _showSnack(String msg) =>
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

  @override
  Widget build(BuildContext context) {
    final trip     = _trip;
    final status   = trip?['status'] as String? ?? 'searching';
    final label    = _statusLabels[status] ?? status;
    final driverLat = (trip?['driver']?['current_lat'] as num?)?.toDouble();
    final driverLng = (trip?['driver']?['current_lng'] as num?)?.toDouble();
    final pickupLat = (trip?['origin_lat'] as num?)?.toDouble() ?? 6.9271;
    final pickupLng = (trip?['origin_lng'] as num?)?.toDouble() ?? 79.8612;

    final canCancel = status == 'searching' || status == 'accepted';

    return Scaffold(
      body: Stack(
        children: [
          // ── Map ──────────────────────────────────────────────────────────────
          FlutterMap(
            mapController: _mapController,
            options: MapOptions(
              initialCenter: LatLng(pickupLat, pickupLng),
              initialZoom: 15,
            ),
            children: [
              TileLayer(
                urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                userAgentPackageName: 'lk.grabber.customer',
              ),
              MarkerLayer(
                markers: [
                  Marker(
                    point: LatLng(pickupLat, pickupLng),
                    width: 36,
                    height: 36,
                    child: const Icon(Icons.person_pin_circle, color: Colors.blue, size: 36),
                  ),
                  if (driverLat != null && driverLng != null)
                    Marker(
                      point: LatLng(driverLat, driverLng),
                      width: 36,
                      height: 36,
                      child: const Icon(Icons.directions_car, color: Colors.green, size: 36),
                    ),
                ],
              ),
            ],
          ),

          // ── Bottom panel ──────────────────────────────────────────────────────
          Align(
            alignment: Alignment.bottomCenter,
            child: Container(
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
                boxShadow: [BoxShadow(blurRadius: 16, color: Colors.black26)],
              ),
              padding: const EdgeInsets.fromLTRB(16, 20, 16, 32),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Status timeline
                  _StatusTimeline(currentStatus: status),
                  const SizedBox(height: 12),

                  // Status label
                  Text(
                    label,
                    textAlign: TextAlign.center,
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(height: 12),

                  // Driver card
                  if (trip?['driver'] != null) _DriverCard(driver: trip!['driver']),

                  // Cancel button
                  if (canCancel) ...[
                    const SizedBox(height: 12),
                    OutlinedButton(
                      onPressed: _cancelling ? null : _cancelRide,
                      style: OutlinedButton.styleFrom(
                        foregroundColor: Colors.red,
                        side: const BorderSide(color: Colors.red),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        padding: const EdgeInsets.symmetric(vertical: 12),
                      ),
                      child: _cancelling
                          ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.red))
                          : const Text('Cancel Ride'),
                    ),
                  ],

                  // SOS button
                  if (status == 'in_transit' || status == 'driver_arrived' || status == 'accepted') ...[
                    const SizedBox(height: 8),
                    TextButton.icon(
                      onPressed: () async {
                        final confirm = await showDialog<bool>(
                          context: context,
                          builder: (_) => AlertDialog(
                            title: const Text('Emergency SOS'),
                            content: const Text('This will alert emergency services and our team. Confirm?'),
                            actions: [
                              TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
                              ElevatedButton(
                                style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
                                onPressed: () => Navigator.pop(context, true),
                                child: const Text('Send SOS'),
                              ),
                            ],
                          ),
                        );
                        if (confirm == true) {
                          try {
                            await TaxiService.triggerSos(widget.rideId);
                          } catch (_) {}
                        }
                      },
                      icon: const Icon(Icons.warning_amber_rounded, color: Colors.red),
                      label: const Text('Emergency SOS', style: TextStyle(color: Colors.red)),
                    ),
                  ],
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Sub-widgets ────────────────────────────────────────────────────────────────

class _StatusTimeline extends StatelessWidget {
  final String currentStatus;

  const _StatusTimeline({required this.currentStatus});

  static const _stages = [
    ('searching', 'Searching'),
    ('accepted', 'Accepted'),
    ('driver_arrived', 'Arrived'),
    ('in_transit', 'On the way'),
    ('completed', 'Complete'),
  ];

  @override
  Widget build(BuildContext context) {
    final currentIdx = _stages.indexWhere((s) => s.$1 == currentStatus);

    return Row(
      children: List.generate(_stages.length * 2 - 1, (i) {
        if (i.isOdd) {
          // Connector line
          final stageIdx = i ~/ 2;
          final filled = stageIdx < currentIdx;
          return Expanded(
            child: Container(
              height: 2,
              color: filled ? Colors.amber : Colors.grey[300],
            ),
          );
        }
        // Stage dot
        final stageIdx = i ~/ 2;
        final done = stageIdx <= currentIdx;
        return Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 12,
              height: 12,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: done ? Colors.amber : Colors.grey[300],
              ),
            ),
          ],
        );
      }),
    );
  }
}

class _DriverCard extends StatelessWidget {
  final Map<String, dynamic> driver;
  const _DriverCard({required this.driver});

  @override
  Widget build(BuildContext context) {
    final profile = driver['profile'] as Map<String, dynamic>? ?? {};
    final name    = profile['name'] as String? ?? 'Your Driver';
    final rating  = driver['driver_rating']?.toString() ?? '--';

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey[200]!),
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 24,
            backgroundColor: Colors.amber[100],
            child: Text(name.isNotEmpty ? name[0] : 'D',
                style: const TextStyle(fontWeight: FontWeight.bold)),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(name, style: const TextStyle(fontWeight: FontWeight.w600)),
                Row(
                  children: [
                    const Icon(Icons.star, size: 14, color: Colors.amber),
                    const SizedBox(width: 4),
                    Text(rating, style: const TextStyle(fontSize: 12)),
                  ],
                ),
              ],
            ),
          ),
          IconButton(
            icon: const Icon(Icons.call, color: Colors.green),
            onPressed: () {
              // TODO: launch phone dialer
            },
          ),
        ],
      ),
    );
  }
}

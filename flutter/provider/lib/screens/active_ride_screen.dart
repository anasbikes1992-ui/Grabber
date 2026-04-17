import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import 'package:go_router/go_router.dart';
import 'package:latlong2/latlong.dart';

import '../services/taxi_driver_service.dart';

class ActiveRideScreen extends ConsumerStatefulWidget {
  final String rideId;
  const ActiveRideScreen({super.key, required this.rideId});

  @override
  ConsumerState<ActiveRideScreen> createState() => _ActiveRideScreenState();
}

class _ActiveRideScreenState extends ConsumerState<ActiveRideScreen> {
  final _mapController = MapController();
  Timer? _pollTimer;
  Map<String, dynamic>? _trip;
  LatLng? _driverPosition;
  bool _actionLoading = false;
  String _nextAction = 'arrive'; // arrive, start, complete

  static const _pollInterval = Duration(seconds: 5);

  @override
  void initState() {
    super.initState();
    _fetchTrip();
    _pollTimer = Timer.periodic(_pollInterval, (_) => _fetchTrip());
    _getCurrentPosition();
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    _mapController.dispose();
    super.dispose();
  }

  Future<void> _getCurrentPosition() async {
    try {
      final pos = await Geolocator.getCurrentPosition();
      setState(() => _driverPosition = LatLng(pos.latitude, pos.longitude));
    } catch (_) {}
  }

  Future<void> _fetchTrip() async {
    try {
      final data = await TaxiDriverService.getTrip(widget.rideId);
      final trip = data['trip'] as Map<String, dynamic>;

      if (!mounted) return;
      setState(() => _trip = trip);

      // Determine next action based on trip status
      final status = trip['status'] as String? ?? '';
      if (status == 'accepted') {
        setState(() => _nextAction = 'arrive');
      } else if (status == 'driver_arrived') {
        setState(() => _nextAction = 'start');
      } else if (status == 'in_transit') {
        setState(() => _nextAction = 'complete');
      }

      if (status == 'completed') {
        _pollTimer?.cancel();
        // Go back to home
        if (mounted) context.pushReplacement('/home');
      }
    } catch (_) {}
  }

  Future<void> _performAction() async {
    setState(() => _actionLoading = true);
    try {
      switch (_nextAction) {
        case 'arrive':
          await TaxiDriverService.markArrived(widget.rideId);
          break;
        case 'start':
          await TaxiDriverService.startRide(widget.rideId);
          break;
        case 'complete':
          final isCash = _trip?['payment_method'] == 'cash';
          final cashPaid = await _showCashPaymentDialog(isCash);
          if (isCash && !cashPaid) {
            if (mounted) setState(() => _actionLoading = false);
            return;
          }
          await TaxiDriverService.completeRide(widget.rideId);
          break;
      }
      if (mounted) _fetchTrip();
    } catch (e) {
      _showSnack('Action failed. Please try again.');
    } finally {
      if (mounted) setState(() => _actionLoading = false);
    }
  }

  Future<bool> _showCashPaymentDialog(bool isCash) async {
    if (!isCash) return true;

    final result = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Mark Cash Received'),
        content: const Text('Confirm that you have received cash payment from the customer.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Received'),
          ),
        ],
      ),
    );
    return result ?? false;
  }

  void _showSnack(String msg) =>
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

  @override
  Widget build(BuildContext context) {
    final trip = _trip;
    final status = trip?['status'] as String? ?? 'accepted';
    final pickupLat = (trip?['origin_lat'] as num?)?.toDouble() ?? 6.9271;
    final pickupLng = (trip?['origin_lng'] as num?)?.toDouble() ?? 79.8612;
    final destLat = (trip?['dest_lat'] as num?)?.toDouble() ?? 6.9271;
    final destLng = (trip?['dest_lng'] as num?)?.toDouble() ?? 79.8612;
    final customerName = trip?['customer']?['profile']?['name'] as String? ?? 'Customer';
    final customerPhone = trip?['customer']?['phone'] as String? ?? '';
    final estimatedFare = trip?['fare'] as double? ?? 0;

    final actionLabel = {
      'arrive': "I've Arrived",
      'start': 'Start Ride',
      'complete': 'Complete Ride',
    }[_nextAction] ?? 'Next';

    return Scaffold(
      body: Stack(
        children: [
          // ── Map ────────────────────────────────────────────────────────────
          FlutterMap(
            mapController: _mapController,
            options: MapOptions(
              initialCenter: LatLng(pickupLat, pickupLng),
              initialZoom: 15,
            ),
            children: [
              TileLayer(
                urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                userAgentPackageName: 'lk.grabber.provider',
              ),
              MarkerLayer(
                markers: [
                  Marker(
                    point: LatLng(pickupLat, pickupLng),
                    width: 36,
                    height: 36,
                    child: const Icon(Icons.location_on, color: Colors.blue, size: 36),
                  ),
                  Marker(
                    point: LatLng(destLat, destLng),
                    width: 36,
                    height: 36,
                    child: const Icon(Icons.location_pin, color: Colors.red, size: 36),
                  ),
                  if (_driverPosition != null)
                    Marker(
                      point: _driverPosition!,
                      width: 40,
                      height: 40,
                      child: const Icon(Icons.directions_car, color: Colors.green, size: 40),
                    ),
                ],
              ),
            ],
          ),

          // ── Top status bar ─────────────────────────────────────────────────
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: CircleAvatar(
                backgroundColor: Colors.white,
                child: IconButton(
                  icon: const Icon(Icons.arrow_back),
                  onPressed: () => context.pop(),
                ),
              ),
            ),
          ),

          // ── Bottom action panel ────────────────────────────────────────────
          Align(
            alignment: Alignment.bottomCenter,
            child: Container(
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
                boxShadow: [BoxShadow(blurRadius: 16, color: Colors.black26)],
              ),
              padding: const EdgeInsets.fromLTRB(16, 20, 16, 28),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Customer card
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.blue[50],
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      children: [
                        CircleAvatar(
                          backgroundColor: Colors.blue[200],
                          child: Text(customerName.isNotEmpty ? customerName[0] : 'C',
                              style: const TextStyle(fontWeight: FontWeight.bold)),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(customerName, style: const TextStyle(fontWeight: FontWeight.w600)),
                              if (customerPhone.isNotEmpty)
                                Text(customerPhone, style: const TextStyle(fontSize: 12, color: Colors.grey)),
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
                  ),
                  const SizedBox(height: 12),

                  // Trip info
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      _DetailChip(label: 'Status', value: status),
                      _DetailChip(label: 'Fare', value: 'LKR ${estimatedFare.toStringAsFixed(0)}'),
                    ],
                  ),
                  const SizedBox(height: 14),

                  // Action button
                  ElevatedButton(
                    onPressed: _actionLoading ? null : _performAction,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.amber,
                      foregroundColor: Colors.black,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    child: _actionLoading
                        ? const SizedBox(height: 22, width: 22, child: CircularProgressIndicator(strokeWidth: 2))
                        : Text(actionLabel, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
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

class _DetailChip extends StatelessWidget {
  const _DetailChip({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.grey[100],
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(fontSize: 10, color: Colors.grey)),
          const SizedBox(height: 2),
          Text(value, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
        ],
      ),
    );
  }
}

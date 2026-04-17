import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../services/taxi_driver_service.dart';

class IncomingRideSheet extends ConsumerStatefulWidget {
  final String rideId;
  final String pickupAddress;
  final String dropoffAddress;
  final double estimatedFare;
  final double distance;

  const IncomingRideSheet({
    super.key,
    required this.rideId,
    required this.pickupAddress,
    required this.dropoffAddress,
    required this.estimatedFare,
    required this.distance,
  });

  @override
  ConsumerState<IncomingRideSheet> createState() => _IncomingRideSheetState();
}

class _IncomingRideSheetState extends ConsumerState<IncomingRideSheet> {
  Timer? _countdownTimer;
  int _secondsLeft = 30;
  bool _accepting = false;
  bool _declined = false;

  @override
  void initState() {
    super.initState();
    _startCountdown();
  }

  @override
  void dispose() {
    _countdownTimer?.cancel();
    super.dispose();
  }

  void _startCountdown() {
    _countdownTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (mounted) {
        setState(() => _secondsLeft--);
        if (_secondsLeft <= 0) {
          timer.cancel();
          _decline();
        }
      }
    });
  }

  Future<void> _accept() async {
    setState(() => _accepting = true);
    try {
      await TaxiDriverService.acceptRide(widget.rideId);
      if (mounted) {
        context.pushReplacement('/ride/active', extra: widget.rideId);
      }
    } catch (e) {
      _showSnack('Failed to accept. Please try again.');
      if (mounted) setState(() => _accepting = false);
    }
  }

  void _decline() {
    setState(() => _declined = true);
    _countdownTimer?.cancel();
    Navigator.pop(context);
  }

  void _showSnack(String msg) =>
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        _decline();
        return false;
      },
      child: Container(
        padding: const EdgeInsets.fromLTRB(20, 20, 20, 32),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          boxShadow: [BoxShadow(blurRadius: 20, color: Colors.black26)],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // ── Countdown timer ────────────────────────────────────────────
            Center(
              child: Container(
                width: 80,
                height: 80,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: _secondsLeft <= 5 ? Colors.red[50] : Colors.amber[50],
                  border: Border.all(
                    color: _secondsLeft <= 5 ? Colors.red : Colors.amber,
                    width: 3,
                  ),
                ),
                child: Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        '$_secondsLeft',
                        style: TextStyle(
                          fontSize: 32,
                          fontWeight: FontWeight.bold,
                          color: _secondsLeft <= 5 ? Colors.red : Colors.amber,
                        ),
                      ),
                      const Text('seconds', style: TextStyle(fontSize: 10)),
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(height: 20),

            // ── Trip details ───────────────────────────────────────────────
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Colors.grey[50],
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      const Icon(Icons.location_on, color: Colors.blue, size: 20),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Pickup', style: TextStyle(fontSize: 11, color: Colors.grey)),
                            Text(widget.pickupAddress,
                                style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Divider(color: Colors.grey[300]),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      const Icon(Icons.location_pin, color: Colors.red, size: 20),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Dropoff', style: TextStyle(fontSize: 11, color: Colors.grey)),
                            Text(widget.dropoffAddress,
                                style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // ── Fare & distance ────────────────────────────────────────────
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                _DetailItem(label: 'Distance', value: '${widget.distance.toStringAsFixed(1)} km'),
                _DetailItem(label: 'Est. Fare', value: 'LKR ${widget.estimatedFare.toStringAsFixed(0)}'),
              ],
            ),
            const SizedBox(height: 20),

            // ── Action buttons ─────────────────────────────────────────────
            ElevatedButton(
              onPressed: _accepting ? null : _accept,
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.green,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              child: _accepting
                  ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Text('Accept Ride', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            ),
            const SizedBox(height: 10),
            OutlinedButton(
              onPressed: _decline,
              style: OutlinedButton.styleFrom(
                foregroundColor: Colors.red,
                side: const BorderSide(color: Colors.red),
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              child: const Text('Decline', style: TextStyle(fontWeight: FontWeight.w600)),
            ),
          ],
        ),
      ),
    );
  }
}

class _DetailItem extends StatelessWidget {
  const _DetailItem({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: Colors.blue[50],
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: Colors.blue[200]!),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: const TextStyle(fontSize: 11, color: Colors.grey)),
            const SizedBox(height: 2),
            Text(value, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
          ],
        ),
      ),
    );
  }
}

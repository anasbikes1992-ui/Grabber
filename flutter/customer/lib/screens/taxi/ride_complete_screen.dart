import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../services/taxi_service.dart';

class RideCompleteScreen extends ConsumerStatefulWidget {
  final String rideId;
  const RideCompleteScreen({super.key, required this.rideId});

  @override
  ConsumerState<RideCompleteScreen> createState() => _RideCompleteScreenState();
}

class _RideCompleteScreenState extends ConsumerState<RideCompleteScreen> {
  int _rating = 5;
  final _tipController = TextEditingController();
  bool _submitting = false;
  Map<String, dynamic>? _trip;

  @override
  void initState() {
    super.initState();
    _loadTrip();
  }

  @override
  void dispose() {
    _tipController.dispose();
    super.dispose();
  }

  Future<void> _loadTrip() async {
    try {
      final data = await TaxiService.getTrip(widget.rideId);
      setState(() => _trip = data['trip']);
    } catch (_) {}
  }

  Future<void> _submit() async {
    setState(() => _submitting = true);
    try {
      final tip = double.tryParse(_tipController.text.trim()) ?? 0.0;
      await TaxiService.rateRide(widget.rideId, rating: _rating, tipAmount: tip);
      if (mounted) context.go('/home');
    } catch (_) {
      _showSnack('Failed to submit rating. Please try again.');
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  void _showSnack(String msg) =>
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

  @override
  Widget build(BuildContext context) {
    final trip       = _trip;
    final fare       = (trip?['fare'] as num?)?.toDouble() ?? 0.0;
    final distance   = (trip?['distance_km'] as num?)?.toDouble() ?? 0.0;
    final pearlPts   = trip?['pearl_points_earned'] ?? 0;
    final originAddr = trip?['origin_address'] as String? ?? '';
    final destAddr   = trip?['dest_address'] as String? ?? '';

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        automaticallyImplyLeading: false,
        title: const Text('Ride Complete', style: TextStyle(fontWeight: FontWeight.bold)),
        centerTitle: true,
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // ── Success badge ───────────────────────────────────────────────
              const Center(
                child: CircleAvatar(
                  radius: 40,
                  backgroundColor: Color(0xFFE8F5E9),
                  child: Icon(Icons.check_circle, size: 52, color: Colors.green),
                ),
              ),
              const SizedBox(height: 20),

              // ── Fare summary ────────────────────────────────────────────────
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.amber[50],
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Colors.amber[200]!),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Fare Summary', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    const SizedBox(height: 12),
                    _SummaryRow(label: 'Distance', value: '${distance.toStringAsFixed(1)} km'),
                    const SizedBox(height: 6),
                    _SummaryRow(label: 'Total Fare', value: 'LKR ${fare.toStringAsFixed(0)}', bold: true),
                    const SizedBox(height: 6),
                    _SummaryRow(label: 'Pearl Points Earned', value: '$pearlPts pts', valueColor: Colors.amber[700]),
                    if (originAddr.isNotEmpty) ...[
                      const Divider(height: 20),
                      _SummaryRow(label: 'From', value: originAddr),
                      const SizedBox(height: 4),
                      _SummaryRow(label: 'To', value: destAddr),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: 24),

              // ── Star rating ─────────────────────────────────────────────────
              const Text('Rate your driver', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 16)),
              const SizedBox(height: 10),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List.generate(5, (i) {
                  final starIdx = i + 1;
                  return GestureDetector(
                    onTap: () => setState(() => _rating = starIdx),
                    child: AnimatedScale(
                      duration: const Duration(milliseconds: 150),
                      scale: _rating >= starIdx ? 1.15 : 1.0,
                      child: Icon(
                        Icons.star,
                        size: 44,
                        color: _rating >= starIdx ? Colors.amber : Colors.grey[300],
                      ),
                    ),
                  );
                }),
              ),
              const SizedBox(height: 20),

              // ── Tip input ───────────────────────────────────────────────────
              TextField(
                controller: _tipController,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: InputDecoration(
                  labelText: 'Tip Amount (LKR) — optional',
                  prefixIcon: const Icon(Icons.volunteer_activism, color: Colors.amber),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
                ),
              ),
              const Spacer(),

              // ── Submit button ───────────────────────────────────────────────
              ElevatedButton(
                onPressed: _submitting ? null : _submit,
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.amber,
                  foregroundColor: Colors.black,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                child: _submitting
                    ? const SizedBox(height: 22, width: 22, child: CircularProgressIndicator(strokeWidth: 2))
                    : const Text('Submit Rating', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
              ),

              const SizedBox(height: 12),
              TextButton(
                onPressed: () => context.go('/home'),
                child: const Text('Skip'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SummaryRow extends StatelessWidget {
  const _SummaryRow({required this.label, required this.value, this.bold = false, this.valueColor});

  final String label;
  final String value;
  final bool bold;
  final Color? valueColor;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: TextStyle(color: Colors.grey[600], fontSize: 13)),
        Text(
          value,
          style: TextStyle(
            fontWeight: bold ? FontWeight.bold : FontWeight.w500,
            fontSize: bold ? 16 : 13,
            color: valueColor,
          ),
        ),
      ],
    );
  }
}

import 'package:flutter/material.dart';

class BookingConfirmationScreen extends StatelessWidget {
  const BookingConfirmationScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text('Booking confirmation')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Container(
            padding: const EdgeInsets.all(22),
            decoration: BoxDecoration(
              color: const Color(0xFFDCFCE7),
              borderRadius: BorderRadius.circular(24),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(Icons.check_circle_rounded, color: Color(0xFF166534), size: 44),
                const SizedBox(height: 12),
                Text(
                  'Your booking is confirmed',
                  style: theme.textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.w800,
                    color: const Color(0xFF166534),
                  ),
                ),
                const SizedBox(height: 8),
                const Text('Reference: GHLK-2026-0417-8841'),
              ],
            ),
          ),
          const SizedBox(height: 18),
          _InfoTile(label: 'Property', value: 'Bentota Lagoon Escape'),
          _InfoTile(label: 'Guests', value: '2 adults'),
          _InfoTile(label: 'Check-in', value: '18 Apr 2026 • 2:00 PM'),
          _InfoTile(label: 'Check-out', value: '20 Apr 2026 • 11:00 AM'),
          _InfoTile(label: 'Payment', value: 'WebxPay paid • LKR 57,000'),
          const SizedBox(height: 18),
          FilledButton(
            onPressed: () => Navigator.of(context).popUntil((route) => route.isFirst),
            child: const Text('Back to home'),
          ),
        ],
      ),
    );
  }
}

class _InfoTile extends StatelessWidget {
  const _InfoTile({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerLow,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: theme.textTheme.bodyMedium),
          Flexible(
            child: Text(
              value,
              textAlign: TextAlign.end,
              style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
            ),
          ),
        ],
      ),
    );
  }
}
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

class StaysSearchScreen extends StatelessWidget {
  const StaysSearchScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    const cards = [
      ('Ella Ridge Cabanas', 'Ella', 'LKR 28,500 / night', 'Tea trails and sunrise decks'),
      ('Galle Fort Courtyard', 'Galle', 'LKR 32,000 / night', 'Boutique heritage stay inside the fort'),
      ('Sigiriya Canopy Lodge', 'Sigiriya', 'LKR 24,750 / night', 'Pool villas close to Pidurangala'),
    ];

    return Scaffold(
      appBar: AppBar(title: const Text('Find stays')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          TextField(
            decoration: InputDecoration(
              hintText: 'Search by city, property, or landmark',
              prefixIcon: const Icon(Icons.search_rounded),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(18)),
            ),
          ),
          const SizedBox(height: 16),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: const [
              Chip(label: Text('Sea view')),
              Chip(label: Text('Family friendly')),
              Chip(label: Text('Breakfast included')),
              Chip(label: Text('Instant book')),
            ],
          ),
          const SizedBox(height: 22),
          ...cards.map((card) {
            return Container(
              margin: const EdgeInsets.only(bottom: 14),
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: theme.colorScheme.surfaceContainerLow,
                borderRadius: BorderRadius.circular(24),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(card.$1, style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
                  const SizedBox(height: 6),
                  Text(card.$2, style: theme.textTheme.bodyMedium),
                  const SizedBox(height: 10),
                  Text(card.$4, style: theme.textTheme.bodySmall),
                  const SizedBox(height: 14),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(card.$3, style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700)),
                      FilledButton(
                        onPressed: () => context.go(
                          '/stays/detail',
                          extra: <String, dynamic>{
                            'stay_id': 'demo-${card.$1.hashCode.abs()}',
                            'title': card.$1,
                            'city': card.$2,
                            'base_price': double.tryParse(card.$3.replaceAll(RegExp(r'[^0-9.]'), '')) ?? 0,
                            'description': card.$4,
                          },
                        ),
                        child: const Text('Reserve'),
                      ),
                    ],
                  ),
                ],
              ),
            );
          }),
        ],
      ),
    );
  }
}
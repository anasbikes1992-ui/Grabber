import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final cards = const [
      _ServiceCardData('Stays', Icons.hotel_rounded, Color(0xFF0F766E), '/stays/search'),
      _ServiceCardData('Vehicles', Icons.directions_car_filled_rounded, Color(0xFF1D4ED8), '/booking/confirmation'),
      _ServiceCardData('Taxi', Icons.local_taxi_rounded, Color(0xFFB45309), '/taxi'),
      _ServiceCardData('Events', Icons.event_available_rounded, Color(0xFFBE185D), '/events'),
      _ServiceCardData('Experiences', Icons.landscape_rounded, Color(0xFF7C3AED), '/experiences'),
      _ServiceCardData('Wallet', Icons.account_balance_wallet_rounded, Color(0xFF334155), '/booking/confirmation'),
    ];

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar.large(
            pinned: true,
            title: const Text('Grabber Hub LK'),
            actions: [
              IconButton(
                onPressed: () {},
                icon: const Icon(Icons.notifications_none_rounded),
              ),
            ],
          ),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
            sliver: SliverList(
              delegate: SliverChildListDelegate([
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [Color(0xFF082F49), Color(0xFF0F766E)],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(28),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Travel, stay, ride, and book in one place.',
                        style: theme.textTheme.headlineSmall?.copyWith(
                          color: Colors.white,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 12),
                      Text(
                        'Discover trusted hosts, instant taxi estimates, live events, and curated Sri Lankan experiences.',
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: Colors.white.withOpacity(0.82),
                          height: 1.5,
                        ),
                      ),
                      const SizedBox(height: 18),
                      FilledButton.tonal(
                        onPressed: () => context.go('/stays/search'),
                        style: FilledButton.styleFrom(
                          backgroundColor: Colors.white,
                          foregroundColor: const Color(0xFF082F49),
                        ),
                        child: const Text('Start with stays'),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 24),
                Text(
                  'Services',
                  style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 14),
                GridView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: cards.length,
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    mainAxisSpacing: 14,
                    crossAxisSpacing: 14,
                    childAspectRatio: 1.05,
                  ),
                  itemBuilder: (context, index) {
                    final card = cards[index];
                    return InkWell(
                      borderRadius: BorderRadius.circular(24),
                      onTap: () => context.go(card.route),
                      child: Ink(
                        decoration: BoxDecoration(
                          color: card.color.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(24),
                          border: Border.all(color: card.color.withOpacity(0.18)),
                        ),
                        child: Padding(
                          padding: const EdgeInsets.all(18),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              CircleAvatar(
                                backgroundColor: card.color,
                                foregroundColor: Colors.white,
                                child: Icon(card.icon),
                              ),
                              const Spacer(),
                              Text(
                                card.title,
                                style: theme.textTheme.titleMedium?.copyWith(
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                              const SizedBox(height: 6),
                              Text(
                                'Open',
                                style: theme.textTheme.bodySmall?.copyWith(
                                  color: theme.colorScheme.onSurface.withOpacity(0.55),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    );
                  },
                ),
                const SizedBox(height: 24),
                Text(
                  'Your next booking',
                  style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 14),
                Container(
                  padding: const EdgeInsets.all(18),
                  decoration: BoxDecoration(
                    color: theme.colorScheme.surfaceContainerLow,
                    borderRadius: BorderRadius.circular(24),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'Bentota Lagoon Escape',
                            style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                          ),
                          const Chip(label: Text('Confirmed')),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Text(
                        '2 nights • Deluxe room • Check-in tomorrow',
                        style: theme.textTheme.bodyMedium,
                      ),
                      const SizedBox(height: 16),
                      FilledButton(
                        onPressed: () => context.go('/booking/confirmation'),
                        child: const Text('View booking'),
                      ),
                    ],
                  ),
                ),
              ]),
            ),
          ),
        ],
      ),
    );
  }
}

class _ServiceCardData {
  const _ServiceCardData(this.title, this.icon, this.color, this.route);

  final String title;
  final IconData icon;
  final Color color;
  final String route;
}

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

class StayDetailScreen extends StatefulWidget {
  const StayDetailScreen({
    super.key,
    required this.stayId,
    required this.title,
    required this.city,
    required this.basePrice,
    this.description,
  });

  final String stayId;
  final String title;
  final String city;
  final double basePrice;
  final String? description;

  @override
  State<StayDetailScreen> createState() => _StayDetailScreenState();
}

class _StayDetailScreenState extends State<StayDetailScreen> {
  DateTime _checkIn = DateTime.now().add(const Duration(days: 1));
  DateTime _checkOut = DateTime.now().add(const Duration(days: 3));
  int _guests = 2;

  int get _nights => _checkOut.difference(_checkIn).inDays.clamp(1, 365);
  double get _subtotal => widget.basePrice * _nights;

  Future<void> _pickCheckIn() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _checkIn,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (picked == null) return;
    setState(() {
      _checkIn = picked;
      if (!_checkOut.isAfter(_checkIn)) {
        _checkOut = _checkIn.add(const Duration(days: 1));
      }
    });
  }

  Future<void> _pickCheckOut() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _checkOut.isAfter(_checkIn) ? _checkOut : _checkIn.add(const Duration(days: 1)),
      firstDate: _checkIn.add(const Duration(days: 1)),
      lastDate: DateTime.now().add(const Duration(days: 366)),
    );
    if (picked != null) {
      setState(() => _checkOut = picked);
    }
  }

  String _fmt(DateTime dt) {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return '${dt.day} ${months[dt.month - 1]} ${dt.year}';
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar.large(
            pinned: true,
            title: Text(widget.title),
          ),
          SliverToBoxAdapter(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Hero gradient image placeholder
                Container(
                  height: 220,
                  margin: const EdgeInsets.symmetric(horizontal: 20),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(28),
                    gradient: const LinearGradient(
                      colors: [Color(0xFF082F49), Color(0xFF0F766E), Color(0xFF99F6E4)],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                  ),
                  child: Stack(
                    children: [
                      Positioned(
                        bottom: 16,
                        left: 16,
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          decoration: BoxDecoration(
                            color: Colors.black.withOpacity(0.45),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.location_on_rounded, color: Colors.white, size: 16),
                              const SizedBox(width: 4),
                              Text(widget.city, style: const TextStyle(color: Colors.white, fontSize: 13)),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 24),

                // Description
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20),
                  child: Text(
                    widget.description ?? 'A curated, approved stay hosted by a verified Grabber provider. Enjoy local hospitality with island charm.',
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                      height: 1.6,
                    ),
                  ),
                ),

                const SizedBox(height: 24),

                // Date & guest picker
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Your trip', style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800)),
                      const SizedBox(height: 14),

                      // Date row
                      Row(
                        children: [
                          Expanded(
                            child: _DateTile(
                              label: 'Check-in',
                              value: _fmt(_checkIn),
                              onTap: _pickCheckIn,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: _DateTile(
                              label: 'Check-out',
                              value: _fmt(_checkOut),
                              onTap: _pickCheckOut,
                            ),
                          ),
                        ],
                      ),

                      const SizedBox(height: 12),

                      // Guests tile
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
                        decoration: BoxDecoration(
                          color: theme.colorScheme.surfaceContainerLow,
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('Guests', style: theme.textTheme.labelMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant)),
                                const SizedBox(height: 2),
                                Text('$_guests adult${_guests > 1 ? 's' : ''}', style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700)),
                              ],
                            ),
                            Row(
                              children: [
                                IconButton.filledTonal(
                                  onPressed: _guests > 1 ? () => setState(() => _guests--) : null,
                                  icon: const Icon(Icons.remove_rounded),
                                ),
                                const SizedBox(width: 8),
                                IconButton.filledTonal(
                                  onPressed: _guests < 10 ? () => setState(() => _guests++) : null,
                                  icon: const Icon(Icons.add_rounded),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 24),

                // Price breakdown
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Price breakdown', style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800)),
                      const SizedBox(height: 14),
                      _PriceRow(
                        label: 'LKR ${widget.basePrice.toStringAsFixed(0)} × $_nights night${_nights > 1 ? 's' : ''}',
                        value: 'LKR ${_subtotal.toStringAsFixed(0)}',
                      ),
                      const Divider(height: 24),
                      _PriceRow(
                        label: 'Total (before payment fees)',
                        value: 'LKR ${_subtotal.toStringAsFixed(0)}',
                        bold: true,
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 32),
              ],
            ),
          ),
        ],
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 12),
          child: FilledButton(
            onPressed: () => context.go(
              '/payment/method',
              extra: <String, dynamic>{
                'stay_id': widget.stayId,
                'stay_title': widget.title,
                'check_in': _checkIn.toIso8601String(),
                'check_out': _checkOut.toIso8601String(),
                'nights': _nights,
                'guests': _guests,
                'subtotal': _subtotal,
              },
            ),
            style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(56)),
            child: const Text('Proceed to payment', style: TextStyle(fontSize: 16)),
          ),
        ),
      ),
    );
  }
}

class _DateTile extends StatelessWidget {
  const _DateTile({required this.label, required this.value, required this.onTap});

  final String label;
  final String value;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        decoration: BoxDecoration(
          color: theme.colorScheme.surfaceContainerLow,
          borderRadius: BorderRadius.circular(20),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: theme.textTheme.labelMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant)),
            const SizedBox(height: 4),
            Text(value, style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700)),
          ],
        ),
      ),
    );
  }
}

class _PriceRow extends StatelessWidget {
  const _PriceRow({required this.label, required this.value, this.bold = false});

  final String label;
  final String value;
  final bool bold;

  @override
  Widget build(BuildContext context) {
    final style = Theme.of(context).textTheme.bodyMedium?.copyWith(
      fontWeight: bold ? FontWeight.w800 : FontWeight.normal,
    );
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Flexible(child: Text(label, style: style)),
          const SizedBox(width: 12),
          Text(value, style: style),
        ],
      ),
    );
  }
}

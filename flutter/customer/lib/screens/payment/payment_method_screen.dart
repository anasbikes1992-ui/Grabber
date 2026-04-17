import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

enum _PaymentMethod { card, bankTransfer, cash }

class PaymentMethodScreen extends StatefulWidget {
  const PaymentMethodScreen({
    super.key,
    required this.stayTitle,
    required this.subtotal,
    required this.nights,
    required this.guests,
    required this.checkIn,
    required this.checkOut,
  });

  final String stayTitle;
  final double subtotal;
  final int nights;
  final int guests;
  final String checkIn;
  final String checkOut;

  @override
  State<PaymentMethodScreen> createState() => _PaymentMethodScreenState();
}

class _PaymentMethodScreenState extends State<PaymentMethodScreen> {
  _PaymentMethod _selected = _PaymentMethod.card;
  bool _pearlPointsEnabled = false;

  // Pearl Points balance (would come from API in production)
  static const double _pearlBalance = 1240.0;

  static const double _handlingFeeRate = 0.03;
  static const double _maxPearlFraction = 0.30;

  double get _pearlDiscount {
    if (!_pearlPointsEnabled) return 0;
    return (_pearlBalance).clamp(0, widget.subtotal * _maxPearlFraction);
  }

  double get _handlingFee {
    if (_selected != _PaymentMethod.card) return 0;
    return (widget.subtotal - _pearlDiscount) * _handlingFeeRate;
  }

  double get _grandTotal => widget.subtotal - _pearlDiscount + _handlingFee;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text('Payment method')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          // Booking summary chip
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              color: theme.colorScheme.surfaceContainerLow,
              borderRadius: BorderRadius.circular(24),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(widget.stayTitle, style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
                const SizedBox(height: 6),
                Text(
                  '${widget.nights} night${widget.nights > 1 ? 's' : ''} · ${widget.guests} guest${widget.guests > 1 ? 's' : ''}',
                  style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                ),
              ],
            ),
          ),

          const SizedBox(height: 22),

          // Method tiles
          Text('Choose how to pay', style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800)),
          const SizedBox(height: 14),

          _MethodTile(
            selected: _selected == _PaymentMethod.card,
            icon: Icons.credit_card_rounded,
            title: 'Card (WebxPay)',
            subtitle: 'Visa, Mastercard, Amex · 3% handling fee applies',
            onTap: () => setState(() => _selected = _PaymentMethod.card),
          ),

          const SizedBox(height: 10),

          _MethodTile(
            selected: _selected == _PaymentMethod.bankTransfer,
            icon: Icons.account_balance_rounded,
            title: 'Bank Transfer',
            subtitle: 'Transfer to Grabber\'s account · 48 h window',
            onTap: () => setState(() => _selected = _PaymentMethod.bankTransfer),
          ),

          const SizedBox(height: 10),

          _MethodTile(
            selected: _selected == _PaymentMethod.cash,
            icon: Icons.payments_rounded,
            title: 'Cash',
            subtitle: 'Pay at a Grabber office or authorised agent',
            onTap: () => setState(() => _selected = _PaymentMethod.cash),
          ),

          // Bank details panel
          if (_selected == _PaymentMethod.bankTransfer) ...[
            const SizedBox(height: 14),
            _InfoPanel(
              icon: Icons.info_outline_rounded,
              color: const Color(0xFF1D4ED8),
              children: [
                _BankRow(label: 'Bank', value: 'Bank of Ceylon'),
                _BankRow(label: 'Account name', value: 'Grabber Mobility Solutions Pvt Ltd'),
                _BankRow(label: 'Account number', value: '0082 4561 2300'),
                _BankRow(label: 'Branch', value: 'Colombo 03'),
                _BankRow(label: 'Reference', value: 'Your booking ref (shown after confirmation)'),
              ],
            ),
          ],

          // Cash info panel
          if (_selected == _PaymentMethod.cash) ...[
            const SizedBox(height: 14),
            _InfoPanel(
              icon: Icons.store_rounded,
              color: const Color(0xFFB45309),
              children: [
                const _BankRow(label: 'Where to pay', value: 'Any Grabber office or authorised agent'),
                const _BankRow(label: 'Receipt', value: 'Agent issues numbered receipt → enter in app'),
                const _BankRow(label: 'Booking activated', value: 'After receipt confirmed by Grabber staff'),
              ],
            ),
          ],

          const SizedBox(height: 24),

          // Pearl Points toggle
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
                    Row(
                      children: [
                        const Icon(Icons.star_rounded, size: 18, color: Color(0xFFB45309)),
                        const SizedBox(width: 6),
                        Text('Pearl Points', style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700)),
                      ],
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'Balance: ${_pearlBalance.toInt()} pts · saves LKR ${_pearlDiscount > 0 ? _pearlDiscount.toStringAsFixed(0) : '0'}',
                      style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                    ),
                  ],
                ),
                Switch(
                  value: _pearlPointsEnabled,
                  onChanged: (v) => setState(() => _pearlPointsEnabled = v),
                ),
              ],
            ),
          ),

          const SizedBox(height: 22),

          // Price breakdown
          Text('Order total', style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800)),
          const SizedBox(height: 14),

          _PriceLine(label: 'Subtotal', value: 'LKR ${widget.subtotal.toStringAsFixed(0)}'),
          if (_pearlPointsEnabled)
            _PriceLine(label: 'Pearl Points discount', value: '− LKR ${_pearlDiscount.toStringAsFixed(0)}', accent: true),
          if (_selected == _PaymentMethod.card)
            _PriceLine(label: 'Card handling fee (3%)', value: '+ LKR ${_handlingFee.toStringAsFixed(0)}'),
          const Divider(height: 24),
          _PriceLine(label: 'Total due', value: 'LKR ${_grandTotal.toStringAsFixed(0)}', bold: true),

          const SizedBox(height: 32),
        ],
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 12),
          child: FilledButton(
            onPressed: () => context.go('/booking/confirmation'),
            style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(56)),
            child: Text(
              _selected == _PaymentMethod.card ? 'Pay LKR ${_grandTotal.toStringAsFixed(0)} with card' : 'Confirm booking',
              style: const TextStyle(fontSize: 16),
            ),
          ),
        ),
      ),
    );
  }
}

class _MethodTile extends StatelessWidget {
  const _MethodTile({
    required this.selected,
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  final bool selected;
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(
          color: selected ? theme.colorScheme.primaryContainer : theme.colorScheme.surfaceContainerLow,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: selected ? theme.colorScheme.primary : Colors.transparent,
            width: 2,
          ),
        ),
        child: Row(
          children: [
            Icon(icon, color: selected ? theme.colorScheme.primary : theme.colorScheme.onSurfaceVariant),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700)),
                  const SizedBox(height: 2),
                  Text(subtitle, style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant)),
                ],
              ),
            ),
            if (selected)
              Icon(Icons.check_circle_rounded, color: theme.colorScheme.primary),
          ],
        ),
      ),
    );
  }
}

class _InfoPanel extends StatelessWidget {
  const _InfoPanel({required this.icon, required this.color, required this.children});

  final IconData icon;
  final Color color;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: color.withOpacity(0.07),
        border: Border.all(color: color.withOpacity(0.25)),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(children: [
            Icon(icon, size: 18, color: color),
            const SizedBox(width: 8),
            Text('Payment details', style: TextStyle(color: color, fontWeight: FontWeight.w700, fontSize: 13)),
          ]),
          const SizedBox(height: 12),
          ...children,
        ],
      ),
    );
  }
}

class _BankRow extends StatelessWidget {
  const _BankRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(label, style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant)),
          ),
          Expanded(
            child: Text(value, style: theme.textTheme.bodySmall?.copyWith(fontWeight: FontWeight.w600)),
          ),
        ],
      ),
    );
  }
}

class _PriceLine extends StatelessWidget {
  const _PriceLine({required this.label, required this.value, this.bold = false, this.accent = false});

  final String label;
  final String value;
  final bool bold;
  final bool accent;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final color = accent ? const Color(0xFF0F766E) : null;
    final style = theme.textTheme.bodyMedium?.copyWith(
      fontWeight: bold ? FontWeight.w800 : FontWeight.normal,
      color: color,
    );
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: style),
          Text(value, style: style),
        ],
      ),
    );
  }
}

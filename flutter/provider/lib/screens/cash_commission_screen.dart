import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../services/taxi_driver_service.dart';

class CashCommissionScreen extends ConsumerStatefulWidget {
  const CashCommissionScreen({super.key});

  @override
  ConsumerState<CashCommissionScreen> createState() => _CashCommissionScreenState();
}

class _CashCommissionScreenState extends ConsumerState<CashCommissionScreen> {
  List<Map<String, dynamic>> _invoices = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadInvoices();
  }

  Future<void> _loadInvoices() async {
    setState(() => _loading = true);
    try {
      final data = await TaxiDriverService.getCommissionInvoices();
      setState(() => _invoices = List<Map<String, dynamic>>.from(data['invoices'] ?? []));
    } catch (_) {
    } finally {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final currentInvoice = _invoices.isNotEmpty ? _invoices.first : null;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Cash Commission'),
        centerTitle: true,
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  if (currentInvoice != null) ...[
                    // Current statement card
                    _InvoiceCard(invoice: currentInvoice, isCurrent: true),
                    const SizedBox(height: 20),
                  ],

                  // Past invoices
                  if (_invoices.length > 1) ...[
                    const Text('Past Statements', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    const SizedBox(height: 12),
                    ..._invoices.skip(1).map((inv) => Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: _InvoiceCard(invoice: inv),
                    )),
                  ] else
                    Center(
                      child: Column(
                        children: [
                          const SizedBox(height: 40),
                          Icon(Icons.receipt_long, size: 64, color: Colors.grey[300]),
                          const SizedBox(height: 16),
                          Text('No statements yet', style: TextStyle(color: Colors.grey[600], fontSize: 14)),
                        ],
                      ),
                    ),
                ],
              ),
            ),
    );
  }
}

class _InvoiceCard extends StatelessWidget {
  const _InvoiceCard({required this.invoice, this.isCurrent = false});

  final Map<String, dynamic> invoice;
  final bool isCurrent;

  @override
  Widget build(BuildContext context) {
    final period = invoice['period'] as String? ?? 'N/A';
    final totalFares = (invoice['total_fares'] as num?)?.toDouble() ?? 0;
    final commissionRate = (invoice['commission_rate'] as num?)?.toDouble() ?? 15;
    final commissionDue = (invoice['commission_amount'] as num?)?.toDouble() ?? 0;
    final status = invoice['status'] as String? ?? 'unpaid';
    final isPaid = status == 'paid';
    final isOverdue = status == 'overdue';

    final statusColor = isPaid ? Colors.green : (isOverdue ? Colors.red : Colors.amber);

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: isCurrent ? Colors.blue[50] : Colors.white,
        border: Border.all(color: isCurrent ? Colors.blue[200]! : Colors.grey[200]!),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(period, style: const TextStyle(fontWeight: FontWeight.w600)),
                  const SizedBox(height: 2),
                  Text('$commissionRate% commission', style: TextStyle(fontSize: 12, color: Colors.grey[600])),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  color: statusColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  status.toUpperCase(),
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                    color: statusColor,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Divider(color: Colors.grey[200]),
          const SizedBox(height: 10),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('Total Fares', style: TextStyle(color: Colors.grey)),
              Text('LKR ${totalFares.toStringAsFixed(0)}', style: const TextStyle(fontWeight: FontWeight.w600)),
            ],
          ),
          const SizedBox(height: 6),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('Commission Due', style: TextStyle(color: Colors.grey, fontWeight: FontWeight.w600)),
              Text('LKR ${commissionDue.toStringAsFixed(0)}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            ],
          ),
          if (!isPaid) ...[
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () {
                  // TODO: launch WebxPay payment flow
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.green,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                child: const Text('Pay via WebxPay', style: TextStyle(color: Colors.white)),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

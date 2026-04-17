import 'package:flutter/material.dart';

import '../../services/event_service.dart';

class EventDetailScreen extends StatefulWidget {
  const EventDetailScreen({super.key, required this.eventId});

  final String eventId;

  @override
  State<EventDetailScreen> createState() => _EventDetailScreenState();
}

class _EventDetailScreenState extends State<EventDetailScreen> {
  bool _loading = true;
  bool _buying = false;
  String _message = '';
  Map<String, dynamic>? _event;
  String? _selectedTicketTypeId;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _message = '';
    });

    try {
      final event = await EventService.getEvent(widget.eventId);
      final ticketTypes = (event['ticket_types'] as List?) ?? const [];
      setState(() {
        _event = event;
        if (ticketTypes.isNotEmpty) {
          final first = ticketTypes.first as Map<String, dynamic>;
          _selectedTicketTypeId = first['id']?.toString();
        }
      });
    } catch (_) {
      setState(() => _message = 'Failed to load event details');
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _purchase() async {
    if (_selectedTicketTypeId == null || _selectedTicketTypeId!.isEmpty) {
      setState(() => _message = 'Select a ticket type first');
      return;
    }

    setState(() {
      _buying = true;
      _message = '';
    });

    try {
      await EventService.purchaseTicket(
        eventId: widget.eventId,
        ticketTypeId: _selectedTicketTypeId!,
      );
      setState(() => _message = 'Ticket purchased successfully');
    } catch (e) {
      setState(() => _message = 'Ticket purchase failed');
    } finally {
      if (mounted) {
        setState(() => _buying = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    final event = _event;
    if (event == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Event')),
        body: Center(child: Text(_message.isEmpty ? 'Event not found' : _message)),
      );
    }

    final ticketTypes = (event['ticket_types'] as List?)
            ?.whereType<Map>()
            .map((e) => Map<String, dynamic>.from(e))
            .toList() ??
        const <Map<String, dynamic>>[];

    return Scaffold(
      appBar: AppBar(title: Text((event['title'] ?? 'Event').toString())),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text((event['description'] ?? 'No description').toString()),
          const SizedBox(height: 16),
          const Text('Ticket Types', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
          const SizedBox(height: 8),
          ...ticketTypes.map((ticket) {
            final id = (ticket['id'] ?? '').toString();
            return RadioListTile<String>(
              value: id,
              groupValue: _selectedTicketTypeId,
              title: Text((ticket['name'] ?? 'Ticket').toString()),
              subtitle: Text('LKR ${ticket['price'] ?? 0}'),
              onChanged: (value) => setState(() => _selectedTicketTypeId = value),
            );
          }),
          const SizedBox(height: 12),
          FilledButton(
            onPressed: _buying ? null : _purchase,
            child: Text(_buying ? 'Purchasing...' : 'Purchase Ticket'),
          ),
          if (_message.isNotEmpty) ...[
            const SizedBox(height: 12),
            Text(_message),
          ],
        ],
      ),
    );
  }
}

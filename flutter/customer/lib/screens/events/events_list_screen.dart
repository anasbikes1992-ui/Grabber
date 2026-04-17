import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../services/event_service.dart';

class EventsListScreen extends StatefulWidget {
  const EventsListScreen({super.key});

  @override
  State<EventsListScreen> createState() => _EventsListScreenState();
}

class _EventsListScreenState extends State<EventsListScreen> {
  bool _loading = true;
  String _error = '';
  List<Map<String, dynamic>> _events = const [];

  @override
  void initState() {
    super.initState();
    _loadEvents();
  }

  Future<void> _loadEvents() async {
    setState(() {
      _loading = true;
      _error = '';
    });

    try {
      final list = await EventService.fetchEvents();
      setState(() => _events = list);
    } catch (e) {
      setState(() => _error = 'Failed to load events');
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Events')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error.isNotEmpty
              ? Center(child: Text(_error))
              : ListView.separated(
                  padding: const EdgeInsets.all(16),
                  itemCount: _events.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 12),
                  itemBuilder: (context, index) {
                    final event = _events[index];
                    return Card(
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      child: InkWell(
                        borderRadius: BorderRadius.circular(16),
                        onTap: () => context.go('/events/detail', extra: {'event_id': event['id']}),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                (event['title'] ?? 'Event').toString(),
                                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
                              ),
                              const SizedBox(height: 6),
                              Text((event['city'] ?? 'Sri Lanka').toString()),
                              const SizedBox(height: 4),
                              Text((event['starts_at'] ?? '').toString()),
                            ],
                          ),
                        ),
                      ),
                    );
                  },
                ),
    );
  }
}

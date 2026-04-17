import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import 'package:go_router/go_router.dart';
import 'package:latlong2/latlong.dart';

import '../../models/taxi_category.dart';
import '../../services/taxi_service.dart';

class TaxiHomeScreen extends ConsumerStatefulWidget {
  const TaxiHomeScreen({super.key});

  @override
  ConsumerState<TaxiHomeScreen> createState() => _TaxiHomeScreenState();
}

class _TaxiHomeScreenState extends ConsumerState<TaxiHomeScreen> {
  final _mapController = MapController();
  final _pickupController = TextEditingController();
  final _dropoffController = TextEditingController();

  LatLng? _currentPosition;
  LatLng? _pickupLatLng;
  LatLng? _dropoffLatLng;
  String _selectedCategory = '';
  String _paymentMethod = 'card';
  Map<String, dynamic>? _fareEstimate;
  bool _isLoading = false;
  bool _isRequesting = false;
  List<TaxiCategory> _categories = [];

  @override
  void initState() {
    super.initState();
    _requestLocation();
    _loadCategories();
  }

  @override
  void dispose() {
    _mapController.dispose();
    _pickupController.dispose();
    _dropoffController.dispose();
    super.dispose();
  }

  Future<void> _requestLocation() async {
    final permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      await Geolocator.requestPermission();
    }

    try {
      final pos = await Geolocator.getCurrentPosition();
      setState(() {
        _currentPosition = LatLng(pos.latitude, pos.longitude);
        _pickupLatLng = _currentPosition;
        _pickupController.text = 'Current Location';
      });
      _mapController.move(_currentPosition!, 15);
    } catch (_) {}
  }

  Future<void> _loadCategories() async {
    try {
      final cats = await TaxiService.fetchCategories();
      setState(() {
        _categories = cats;
        if (cats.isNotEmpty) _selectedCategory = cats.first.id;
      });
    } catch (_) {}
  }

  Future<void> _getFareEstimate() async {
    if (_pickupLatLng == null || _dropoffLatLng == null || _selectedCategory.isEmpty) {
      _showSnack('Please set pickup, dropoff, and category.');
      return;
    }

    setState(() => _isLoading = true);
    try {
      final estimate = await TaxiService.fareEstimate(
        originLat: _pickupLatLng!.latitude,
        originLng: _pickupLatLng!.longitude,
        destLat: _dropoffLatLng!.latitude,
        destLng: _dropoffLatLng!.longitude,
        categoryId: _selectedCategory,
      );
      setState(() => _fareEstimate = estimate);
    } catch (e) {
      _showSnack('Failed to get fare estimate.');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _requestRide() async {
    if (_pickupLatLng == null || _dropoffLatLng == null || _selectedCategory.isEmpty) {
      _showSnack('Please complete all fields.');
      return;
    }

    setState(() => _isRequesting = true);
    try {
      final trip = await TaxiService.requestRide(
        categoryId: _selectedCategory,
        originLat: _pickupLatLng!.latitude,
        originLng: _pickupLatLng!.longitude,
        originAddress: _pickupController.text,
        destLat: _dropoffLatLng!.latitude,
        destLng: _dropoffLatLng!.longitude,
        destAddress: _dropoffController.text,
        paymentMethod: _paymentMethod,
      );
      if (mounted) {
        context.push('/taxi/tracking', extra: trip['trip']['id']);
      }
    } catch (e) {
      _showSnack('Failed to request ride. Please try again.');
    } finally {
      if (mounted) setState(() => _isRequesting = false);
    }
  }

  void _showSnack(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
  }

  @override
  Widget build(BuildContext context) {
    final surge = _fareEstimate?['surge_multiplier'] ?? 1.0;
    final total = _fareEstimate?['breakdown']?['total'];

    return Scaffold(
      body: Stack(
        children: [
          // ── Map ──────────────────────────────────────────────────────────────
          FlutterMap(
            mapController: _mapController,
            options: MapOptions(
              initialCenter: _currentPosition ?? const LatLng(6.9271, 79.8612),
              initialZoom: 14,
            ),
            children: [
              TileLayer(
                urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                userAgentPackageName: 'lk.grabber.customer',
              ),
              MarkerLayer(
                markers: [
                  if (_pickupLatLng != null)
                    Marker(
                      point: _pickupLatLng!,
                      width: 36,
                      height: 36,
                      child: const Icon(Icons.my_location, color: Colors.blue, size: 36),
                    ),
                  if (_dropoffLatLng != null)
                    Marker(
                      point: _dropoffLatLng!,
                      width: 36,
                      height: 36,
                      child: const Icon(Icons.location_pin, color: Colors.red, size: 36),
                    ),
                ],
              ),
            ],
          ),

          // ── Back button ───────────────────────────────────────────────────────
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: CircleAvatar(
                backgroundColor: Colors.white,
                child: IconButton(
                  icon: const Icon(Icons.arrow_back),
                  onPressed: () => context.pop(),
                ),
              ),
            ),
          ),

          // ── Bottom sheet panel ────────────────────────────────────────────────
          Align(
            alignment: Alignment.bottomCenter,
            child: Container(
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
                boxShadow: [BoxShadow(blurRadius: 12, color: Colors.black26)],
              ),
              padding: const EdgeInsets.fromLTRB(16, 20, 16, 24),
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    // Drag handle
                    Center(
                      child: Container(
                        width: 40,
                        height: 4,
                        decoration: BoxDecoration(
                          color: Colors.grey[300],
                          borderRadius: BorderRadius.circular(2),
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),

                    // ── Category selector ───────────────────────────────────────
                    SizedBox(
                      height: 80,
                      child: ListView.separated(
                        scrollDirection: Axis.horizontal,
                        itemCount: _categories.length,
                        separatorBuilder: (_, __) => const SizedBox(width: 8),
                        itemBuilder: (_, i) {
                          final cat = _categories[i];
                          final selected = cat.id == _selectedCategory;
                          return GestureDetector(
                            onTap: () {
                              setState(() => _selectedCategory = cat.id);
                              _fareEstimate = null;
                            },
                            child: AnimatedContainer(
                              duration: const Duration(milliseconds: 200),
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                              decoration: BoxDecoration(
                                color: selected ? Colors.amber : Colors.grey[100],
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(
                                  color: selected ? Colors.amber : Colors.grey[300]!,
                                ),
                              ),
                              child: Column(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Text(cat.icon, style: const TextStyle(fontSize: 22)),
                                  const SizedBox(height: 4),
                                  Text(cat.name, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
                    ),
                    const SizedBox(height: 14),

                    // ── Pickup field ───────────────────────────────────────────
                    _LocationField(
                      controller: _pickupController,
                      label: 'Pickup',
                      icon: Icons.my_location,
                      iconColor: Colors.blue,
                      onTap: () {
                        // TODO: open place search
                      },
                    ),
                    const SizedBox(height: 8),

                    // ── Dropoff field ──────────────────────────────────────────
                    _LocationField(
                      controller: _dropoffController,
                      label: 'Where to?',
                      icon: Icons.location_pin,
                      iconColor: Colors.red,
                      onTap: () {
                        // TODO: open place search, set _dropoffLatLng
                      },
                    ),
                    const SizedBox(height: 12),

                    // ── Payment toggle ─────────────────────────────────────────
                    Row(
                      children: [
                        const Text('Pay with:', style: TextStyle(fontWeight: FontWeight.w500)),
                        const SizedBox(width: 12),
                        _PaymentChip(
                          label: 'Card',
                          selected: _paymentMethod == 'card',
                          onTap: () => setState(() => _paymentMethod = 'card'),
                        ),
                        const SizedBox(width: 8),
                        _PaymentChip(
                          label: 'Cash',
                          selected: _paymentMethod == 'cash',
                          onTap: () => setState(() => _paymentMethod = 'cash'),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),

                    // ── Fare estimate panel ────────────────────────────────────
                    if (_fareEstimate != null) ...[
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.amber[50],
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: Colors.amber[200]!),
                        ),
                        child: Column(
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                const Text('Estimated Fare', style: TextStyle(fontWeight: FontWeight.w600)),
                                if ((surge as double) > 1.0)
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                    decoration: BoxDecoration(
                                      color: Colors.orange,
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: Text(
                                      '${surge.toStringAsFixed(1)}x Surge',
                                      style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                                    ),
                                  ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            Text(
                              'LKR ${total?.toStringAsFixed(0) ?? '--'}',
                              style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Colors.amber),
                            ),
                            Text(
                              '${_fareEstimate!['distance_km']?.toStringAsFixed(1)} km  •  Earn ${_fareEstimate!['pearl_points_earn']} Pearl Pts',
                              style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 10),
                    ],

                    // ── CTA buttons ────────────────────────────────────────────
                    if (_fareEstimate == null)
                      ElevatedButton(
                        onPressed: _isLoading ? null : _getFareEstimate,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.amber,
                          foregroundColor: Colors.black,
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        child: _isLoading
                            ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
                            : const Text('Get Fare Estimate', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      )
                    else
                      ElevatedButton(
                        onPressed: _isRequesting ? null : _requestRide,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.green,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        child: _isRequesting
                            ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                            : const Text('Request Ride', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Sub-widgets ────────────────────────────────────────────────────────────────

class _LocationField extends StatelessWidget {
  const _LocationField({
    required this.controller,
    required this.label,
    required this.icon,
    required this.iconColor,
    required this.onTap,
  });

  final TextEditingController controller;
  final String label;
  final IconData icon;
  final Color iconColor;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AbsorbPointer(
        child: TextField(
          controller: controller,
          decoration: InputDecoration(
            labelText: label,
            prefixIcon: Icon(icon, color: iconColor),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          ),
        ),
      ),
    );
  }
}

class _PaymentChip extends StatelessWidget {
  const _PaymentChip({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
        decoration: BoxDecoration(
          color: selected ? Colors.amber : Colors.grey[200],
          borderRadius: BorderRadius.circular(20),
        ),
        child: Text(
          label,
          style: TextStyle(fontWeight: selected ? FontWeight.bold : FontWeight.normal),
        ),
      ),
    );
  }
}

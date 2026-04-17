# Sprint 3 Code Review

Date: 2026-04-17
Scope: Commit 0e0ad3f (Taxi full logic + Flutter + Next.js)
Reviewer: GitHub Copilot

## Verdict

Status: NOT READY FOR PRODUCTION

Reason: Core features are implemented, but there are release-blocking gaps in API contract alignment, auth handling, and web pages still using mock/placeholder behavior.

## Findings (ordered by severity)

### CRITICAL

1. Provider app location payload does not match backend contract
- Backend expects `lat` and `lng` keys.
- Provider service sends `latitude` and `longitude`.
- Impact: Driver location updates fail validation and live tracking/dispatch quality degrades.
- Evidence:
  - `flutter/provider/lib/services/taxi_driver_service.dart:32` sends `{'latitude': latitude, 'longitude': longitude}`
  - `api/app/Http/Controllers/Api/V1/TaxiDriverController.php:50-51` validates `lat`, `lng`
- Required fix: Align client payload with API (`lat`,`lng`) or accept both in backend.

2. Mobile taxi services use hardcoded auth placeholder token
- Both customer and provider services send `Bearer YOUR_TOKEN_HERE`.
- Impact: All protected taxi endpoints fail in real usage.
- Evidence:
  - `flutter/customer/lib/services/taxi_service.dart:139`
  - `flutter/provider/lib/services/taxi_driver_service.dart:143`
- Required fix: Inject token from secure storage/session provider and rotate on refresh.

### HIGH

3. Admin taxi endpoints are exposed under generic auth guard without admin/role middleware
- Routes are only inside `auth:sanctum` group with no explicit role/permission middleware at route group level.
- Impact: Any authenticated account may access admin taxi operations if controller-level gates are absent.
- Evidence:
  - `api/routes/api.php:134` (`Route::prefix('admin/taxi')->group(...)`)
  - `api/app/Http/Controllers/Api/V1/AdminTaxiController.php` (no explicit role checks in methods)
- Required fix: Add route middleware (e.g. `role:admin|super_admin` or permission middleware) and policy checks.

4. Next.js taxi pages still use mock data and placeholders instead of live API integration
- Fare estimation and ride tracking rely on local mocked objects.
- Tracking page map is placeholder text, not live map/realtime.
- Impact: Web taxi flow is demo-level, not production functional.
- Evidence:
  - `web/app/[locale]/taxi/page.tsx:37,46`
  - `web/app/[locale]/taxi/[rideId]/page.tsx:16-17,84`
- Required fix: Replace mocks with real API calls and hook to live trip state updates (polling or realtime).

### MEDIUM

5. Web taxi CTA links are non-functional placeholders
- Store links use `href="#"`.
- Impact: Conversion loss and broken UX.
- Evidence:
  - `web/app/[locale]/taxi/page.tsx:166,170`
- Required fix: Replace with real app store/deep links.

6. Type safety gaps in web pages (`any` usage)
- `useState<any>` used for estimates and trip data.
- Impact: Runtime bugs can bypass compile-time checks.
- Evidence:
  - `web/app/[locale]/taxi/page.tsx`
  - `web/app/[locale]/taxi/[rideId]/page.tsx`
- Required fix: Add typed interfaces for fare estimates/trip payload and API responses.

## Positive Notes

- Taxi backend architecture is solidly separated into services/controllers/jobs.
- Scheduler jobs are present and correctly configured for Laravel 11 in `routes/console.php`.
- Driver acceptance flow uses DB transaction + lock to reduce race condition risk.

## Required Before Marking Sprint 3 as Production-Ready

1. Fix provider location payload mismatch (`latitude/longitude` -> `lat/lng`).
2. Replace token placeholders with real auth token injection.
3. Protect `admin/taxi/*` routes with role/permission middleware.
4. Replace web taxi mocks with real API integration.
5. Add basic integration tests for:
   - Driver location update contract
   - Admin route authorization
   - Web fare estimate API flow

## Full Development Status vs Master Plan

- Completed: Sprint 3 implementation and push to repository.
- Not fully completed overall: Sprints 4-10 from final development document remain pending.


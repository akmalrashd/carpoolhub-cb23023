# CarpoolHub Current System Context For Claude

Last audited from local repo: `c:\xampp\htdocs\CarpoolHub`

## Project Identity

CarpoolHub is a Laravel-based web application for public and private carpool coordination. The thesis title is:

`CARPOOLHUB: AN AI-ASSISTED RIDE-SHARING SYSTEM WITH CUSTOM ROUTE PREFERENCE`

The implemented system is already beyond a basic CRUD prototype. It currently supports user roles, saved routes, trip creation, public ride discovery, join requests, custom passenger pickup/drop-off preferences, fare split/payment tracking, notifications, billing cycles, archives, admin reports, and a rule-based AI decision-support layer.

## Tech Stack

- Backend: PHP 8.2+, Laravel 12
- Frontend: Blade templates, CSS, JavaScript
- Build tooling: none. There is no Node/npm/Vite toolchain. Every stylesheet and
  script the app serves is a plain committed file under `public/css/` and
  `public/js/`, linked with `asset(...)?v={{ filemtime(...) }}`. The Tailwind
  base layer lives pre-compiled at `public/css/app.css` (see
  `resources/css/app.css` for the source it was generated from).
- Database: MySQL/MariaDB expected through Laravel migrations
- Local environment: XAMPP-compatible Laravel app
- Map/routing UI: Leaflet, OpenStreetMap tiles, OSRM public routing API, Nominatim search/reverse geocoding in Blade JavaScript
- AI implementation status: internal rule-based decision support services, not an external LLM API chatbox yet

Important packages:

- `laravel/framework`
- `laravel/tinker`
- `minishlink/web-push`

## Current Git State

The worktree is dirty. Many changes are already present and should not be overwritten blindly.

Modified files include:

- `app/Http/Controllers/ArchivedPaymentController.php`
- `app/Http/Controllers/ExploreController.php`
- `app/Http/Controllers/PaymentController.php`
- `app/Http/Requests/Explore/StoreTripJoinRequest.php`
- `app/Models/Trip.php`
- `app/Models/TripJoinRequest.php`
- `app/Models/TripParticipant.php`
- `app/Services/ArchivedPaymentService.php`
- `app/Services/PaymentService.php`
- `app/Services/TripJoinRequestService.php`
- `app/Services/TripService.php`
- multiple Blade views under `resources/views`

Untracked files:

- `app/Models/TripPassengerRoutePoint.php`
- `database/migrations/2026_04_17_000000_create_trip_passenger_route_points_table.php`
- this context file

Any future assistant should inspect the current diff before editing.

## Roles And Access

Users have roles:

- `admin`
- `driver`
- `passenger`

Middleware:

- `auth`
- `active`
- `role:admin`

Driver/admin features:

- create, edit, delete trips
- manage public join requests
- confirm/reject passenger payments
- send payment reminders

Passenger features:

- browse public trips
- request to join a trip
- request custom pickup/drop-off points
- mark own payment as paid after trip time

Admin features:

- manage users
- deactivate/reactivate users
- view/export reports

## Main Routes

Defined in `routes/web.php`.

Public/guest:

- `/login`

Authenticated:

- `/home`
- `/trips`
- `/explore`
- `/explore/search`
- `/explore/{trip}`
- `/explore/{trip}/request-join`
- `/connections`
- `/saved-routes`
- `/payments`
- `/billing-cycles`
- `/archive`
- `/notifications`
- `/settings`
- `/admin/users`
- `/admin/reports`

Refresh endpoints:

- `/refresh/notifications/latest`
- `/refresh/trips/{trip}/requests`
- `/refresh/trips/{trip}/status`
- `/refresh/payments/summary`

## Core Data Model

Important models:

- `User`
- `SavedRoute`
- `Connection`
- `Trip`
- `TripParticipant`
- `TripPayment`
- `TripJoinRequest`
- `TripPassengerRoutePoint`
- `BillingCycle`
- `ArchivedTrip`
- `ArchivedTripParticipant`
- `ArchivedTripPayment`
- `UserNotification`
- `PassengerRiskProfile`
- `TripRecommendationLog`
- `TripStrategySuggestion`

## Database Tables

Main app tables from migrations:

- `users`
- `connections`
- `saved_routes`
- `trips`
- `trip_participants`
- `trip_payments`
- `trip_join_requests`
- `trip_passenger_route_points`
- `notifications`
- `billing_cycles`
- `archived_trips`
- `archived_trip_participants`
- `archived_trip_payments`
- `passenger_risk_profiles`
- `trip_recommendation_logs`
- `trip_strategy_suggestions`

Laravel infrastructure tables:

- `cache`
- `cache_locks`
- `jobs`
- `job_batches`
- `failed_jobs`
- `sessions`
- `password_reset_tokens`

## Trip Model

`Trip` represents one trip instance. It supports:

- driver ownership
- saved route link
- pickup/destination names and coordinates
- one-way/two-way grouping through `parent_trip_id` and `is_return_trip`
- billing cycle ownership
- status lifecycle
- visibility: `private` or `public`
- public seat limit
- request open/closed state
- fare total and fare per person
- participant count
- private note and public note
- passenger route points

Trip statuses in current code are normalized to:

- `draft`
- `scheduled`
- `recorded`
- `cancelled`

Older migrations originally used `confirmed` and `completed`, but a later migration/code path maps those concepts to `scheduled` and `recorded`.

## Saved Routes

Drivers create saved routes before creating trips.

Current saved route schema uses:

- `route_name`
- `point_a_name`
- `point_a_latitude`
- `point_a_longitude`
- `point_b_name`
- `point_b_latitude`
- `point_b_longitude`
- `default_fare`
- `is_active`

The saved route create/edit Blade pages include a map UI:

- Leaflet map
- search address/place
- current location button
- click map to set Point A and Point B
- OSRM route options
- suggested fare display based on distance/duration

## Trip Creation

Implemented in:

- `TripController`
- `TripService`
- `resources/views/trips/_form.blade.php`

Current trip creation flow:

1. Driver/admin selects an active saved route.
2. Driver chooses one-way or two-way trip.
3. Driver chooses route direction using Point A/Point B.
4. Driver sets trip datetime.
5. Driver chooses visibility: private or public.
6. For private trips, driver selects participants from accepted connections.
7. For public trips, driver sets seat limit and opens trip for public requests.
8. System calculates fare per person from route default fare and split count.
9. System creates participants and payment records.
10. For two-way trips, system creates a return trip linked to the outbound trip.

Important detail:

- Public trip fare split is based on `seat_limit` plus driver inclusion setting.
- Payment records are regenerated when participants are resynced.
- Public trips can receive join requests before passengers become participants.

## Public Explore And Search

Implemented in:

- `ExploreController`
- `TripService::paginateExplore`
- `resources/views/explore/index.blade.php`
- `resources/views/explore/search.blade.php`
- `resources/views/explore/show.blade.php`

Explore only shows:

- public trips
- open for request
- scheduled
- future trip time
- not full based on passenger count vs seat limit
- base outbound trip only, not return child trip

Filters:

- destination text
- pickup text
- driver name
- date
- sort: nearest/latest
- timeframe: today/tomorrow/weekend
- seat availability: 1 or 2+
- geographic center latitude/longitude/radius

Explore ranking:

- Results are decorated and sorted by the AI rule-based `TripMatchingService`.
- Top 3 become recommended trip IDs.
- Cards can display match score and explanation chips.

## Join Request Flow

Implemented in:

- `ExploreController::requestJoin`
- `TripJoinRequestController`
- `TripJoinRequestService`
- `resources/views/trips/requests.blade.php`
- `resources/views/trips/partials/requests-list.blade.php`

Passenger can request to join a public trip.

Rules:

- driver cannot join own trip
- trip must be public
- trip must be open for request
- trip must be scheduled and future
- passenger cannot already be participant
- seat must be available
- pending duplicate request is blocked
- rejected/cancelled previous request can be resubmitted

Driver/admin can approve or reject.

On approve:

- system checks seats again
- system blocks approval if any payment processing has already started for that trip group
- passenger is attached to base trip and return trip group if applicable
- fare split is recalculated
- route point is linked to the created participant
- if seats become full, trip closes for further requests

On reject:

- route point status becomes `rejected`

Notifications are created for driver and passenger.

## Custom Route Preference

This is currently implemented as a passenger route preference feature, centered on `TripPassengerRoutePoint`.

Passenger can choose:

- default pickup or custom pickup
- default drop-off or custom drop-off
- optional requested pickup time
- optional detour distance
- optional fare override amount
- optional request note

Stored fields:

- pickup/dropoff name
- pickup/dropoff coordinates
- whether pickup/dropoff uses default trip point
- requested pickup time
- route fit score
- route fit label
- pickup distance from nearest original endpoint
- dropoff distance from nearest original endpoint
- detour distance
- detour duration
- fare override amount
- status: requested, accepted, rejected, cancelled, removed

Current scoring is heuristic:

- default pickup and drop-off: score 100
- custom point near original endpoints: high score
- further custom point: lower score
- missing coordinates: fallback labels such as driver review needed

Current frontend:

- `explore/show.blade.php` uses Leaflet.
- Passenger can pin custom stops on map.
- UI shows current route and suggested join route.
- It calculates/sets hidden fields for custom coordinate and fare preview.
- It uses OpenStreetMap/Leaflet and client-side distance calculations.

Important limitation:

- The system does not yet appear to use a paid Map API or robust backend route-distance API.
- The custom route fare is mostly frontend-assisted plus stored field, not a full backend route optimization engine.

## Payment Workflow

Implemented in:

- `PaymentController`
- `PaymentService`
- `ArchivedPaymentController`
- `ArchivedPaymentService`
- `resources/views/payments/index.blade.php`

Payment statuses:

- `unpaid`
- `pending_confirmation`
- `paid`

Rules:

- payments are only processed after trip time
- draft trips do not require payment
- passenger can mark own payment as paid
- if driver marks their own self-driven payment, it becomes paid directly
- otherwise passenger mark-paid creates `pending_confirmation`
- driver/admin confirms paid
- driver/admin rejects pending confirmation with reason
- driver/admin can send reminder once per 24 hours

Payment details are stored on user profile:

- account name
- account number
- bank name
- DuitNow QR
- TNG QR

Boundary from thesis still applies:

- No built-in online payment gateway.
- System tracks manual payment status.

## Passenger Reliability And AI Risk

There are two layers:

1. `PassengerReliabilityService`
2. `App\Services\Ai\PassengerRiskScoringService`

`PassengerReliabilityService` computes a 1.0 to 5.0 payment reliability score from:

- unpaid cases
- outstanding amount
- overdue days
- case count

Labels:

- Excellent
- Good
- Moderate
- Risky
- High Risk

`PassengerRiskScoringService` computes a 0 to 100 broader AI-style risk card from:

- outstanding amount
- overdue case count
- cancelled request count
- absent attendance count
- payment reliability score
- basic driver-specific context placeholder

Risk bands:

- 80-100: Low Risk
- 60-79: Moderate Risk
- 40-59: High Risk
- 0-39: Very High Risk

This is explainable, rule-based decision support, not trained ML.

## AI Decision Support Services

Located under `app/Services/Ai`.

### `AiDecisionSupportService`

Facade/orchestrator for:

- trip recommendations
- passenger risk card
- fare/seat strategy suggestion

### `TripMatchingService`

Scores and ranks public trips for a passenger.

Score components:

- route score
- time score
- seat score
- fare score
- connection score
- history score

The sum becomes `match_score`.

Possible explanations:

- near saved route
- matches typical travel time
- driver already in connections
- good seat availability
- favorable fare

Can log to `trip_recommendation_logs`, although current controller call does not enable logging.

### `FeatureEngineeringService`

Centralizes reusable feature calculations:

- passenger risk features
- recommendation features
- route demand features

### `FareSeatSuggestionService`

Suggests:

- fare total
- fare per person
- seat limit
- demand score
- confidence level

Uses historical public trips for the same saved route.

Important status:

- Service exists.
- It is not clearly integrated into the trip create/edit controller UI yet.

## AI Chatbox Status

The thesis describes an AI-assisted chatbox where drivers type natural language and the system drafts trip fields.

Current code audit did not find an implemented chatbox or external AI API integration for natural language trip drafting.

What exists instead:

- rule-based AI decision support for recommendations, risk, and fare/seat strategy
- no LLM prompt processing controller/route found
- no OpenAI/Gemini/etc service call found

So future work can implement the chatbox as a new module without claiming it already exists.

## Connections

Implemented in:

- `ConnectionController`
- `ConnectionService`
- `connections` table

Used for:

- friend/connection management
- selecting private trip participants
- profile contact visibility
- AI recommendation connection score

Connections are directional rows with requester/receiver but accepted connections are treated as mutual in queries.

## Notifications

Implemented with custom `UserNotification` model and `notifications` table.

Used for:

- trip created/updated
- join request submitted
- join request approved/rejected
- payment marked/confirmed/rejected
- payment reminders

Layout includes notification dropdown and refresh endpoint.

## Billing Cycles And Archive

Billing cycles group trips by month and allow closing/archiving operational data.

Archive models:

- `ArchivedTrip`
- `ArchivedTripParticipant`
- `ArchivedTripPayment`

Archive views:

- `resources/views/archive/index.blade.php`
- `resources/views/archive/trips.blade.php`
- `resources/views/archive/payments.blade.php`

Archive service supports:

- month options
- archived trip listing
- archived payment listing
- month summary
- user/admin visibility rules

Archived payments can still be marked/confirmed/rejected/reminded through archived payment services/controllers.

## Admin Reporting

Implemented in:

- `AdminReportController`
- `ReportService`
- `resources/views/admin/reports`

Reports include:

- total users
- total drivers
- total passengers
- active users
- total trips
- completed/recorded trips
- fare total
- payment total
- paid total
- pending/unpaid total
- payment status breakdown
- billing cycle financial summary

Export:

- CSV stream
- PDF-like Blade view, not necessarily a generated binary PDF

## Admin User Management

Implemented in:

- `AdminUserController`
- `AdminUserService`
- `resources/views/admin/users/index.blade.php`

Purpose:

- manage user roles/profile state
- deactivate problematic users

## Authentication

Custom login controller:

- `app/Http/Controllers/Auth/LoginController.php`

Seeder test users:

- `admin@carpoolhub.test`
- `driver1@carpoolhub.test`
- `driver2@carpoolhub.test`
- `passenger1@carpoolhub.test`
- `passenger2@carpoolhub.test`
- `passenger3@carpoolhub.test`

Default password from seeder:

- `password`

## Frontend Layout

Main app layout:

- `resources/views/layouts/app.blade.php`

Navigation includes:

- Home
- New Trip
- My Trips
- Explore
- Routes
- Payments
- Connections
- Monthly Summary
- Archive
- Settings
- Admin Users
- Reports

The app uses a responsive desktop sidebar and mobile header/bottom nav partials.

## Implemented Thesis Mapping

Already implemented or mostly implemented:

- web-based Laravel ride-sharing platform
- user roles: admin, driver, passenger
- saved route management with map UI
- public trip discovery
- private trip management
- public trip join request workflow
- driver approval/rejection of passenger requests
- custom pickup/drop-off request using map pins
- fare split calculation
- manual payment status tracking
- payment reminder/confirmation workflow
- passenger reliability scoring
- rule-based AI passenger risk score
- rule-based trip recommendation score
- archive/monthly summary/reporting

Partially implemented:

- fair custom route extra fare: UI/model supports fare override and route preview, but backend calculation is heuristic and not a full robust route API calculation
- AI fare/seat suggestion: service exists, UI/controller integration may be incomplete
- decision support: implemented as rule-based scoring, not trained ML

Not implemented yet:

- AI chatbox for natural language trip drafting
- external AI API integration
- real-time vehicle GPS tracking
- online payment gateway
- full ML no-show prediction using trained model

## Suggested Next Work

High-impact next tasks:

1. Integrate `FareSeatSuggestionService` into trip create/edit UI if thesis requires visible AI fare/seat suggestion.
2. Build the AI chatbox module for trip drafting:
   - new route/controller
   - prompt/parser service
   - validation against saved routes
   - output draft payload only, never auto-submit
3. Strengthen backend custom fare calculation:
   - calculate detour distance server-side
   - validate custom points are within allowed route corridor
   - persist fare suggestion separately from driver override
4. Add tests around join request approval, custom route point persistence, and payment window rules.
5. Update thesis Chapter 3/4 to say current AI is rule-based decision support unless an actual external AI API is implemented.

## Advice For Claude

Before modifying anything:

1. Run `git status --short`.
2. Inspect existing diffs because many files are modified.
3. Do not overwrite untracked `TripPassengerRoutePoint` work.
4. Prefer Laravel service-layer changes over controller-heavy logic.
5. Keep AI outputs explainable and non-blocking.
6. Do not claim full machine learning unless a trained model and labels are added.


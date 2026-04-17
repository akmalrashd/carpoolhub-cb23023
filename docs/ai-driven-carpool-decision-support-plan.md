# AI-Driven Carpool Decision Support

## Goal

Add one AI module with three subfeatures:

1. Smart Trip Matching & Recommendation
2. Passenger Risk / No-Show Prediction
3. Auto-Suggest Fare / Seat Strategy

This plan is based on the current Laravel codebase, especially:

- `app/Services/TripService.php`
- `app/Services/TripJoinRequestService.php`
- `app/Services/PassengerReliabilityService.php`
- `app/Services/PaymentService.php`
- `app/Services/ArchiveService.php`
- `app/Models/Trip.php`
- `app/Models/TripJoinRequest.php`
- `app/Models/TripParticipant.php`
- `app/Models/TripPayment.php`
- `app/Models/ArchivedTrip.php`

## What The Current System Already Has

### Existing flows

- Public trip discovery through Explore
- Join request workflow for public trips
- Driver approval or rejection for join requests
- Dynamic fare split based on participants and public seat limit
- Active and archived trip/payment history
- Passenger reliability score based on unpaid and overdue payment behavior

### Existing data that is useful for the AI module

#### Trip data

- trip datetime
- pickup and destination names
- pickup and destination coordinates
- trip mode
- visibility
- seat limit
- fare total
- fare per person
- participant count
- route ownership and route default fare

#### Join and participation data

- join request status: pending, approved, rejected, cancelled
- request notes and response notes
- who responded and when
- participant records per trip
- attendance status field exists on participants

#### Payment and reliability data

- amount due
- payment status
- marked paid time
- confirmed paid time
- payment method
- outstanding debt rollups
- archived payment history by billing cycle

#### Social and user context

- accepted connections between users
- driver and passenger role
- profile visibility settings
- saved routes per user

## Important Data Gaps

The current system is strong for recommendation and payment-based risk scoring, but weaker for true no-show prediction.

### Gaps for prediction

- No dedicated no-show label history yet
- `attendance_status` exists, but current code mostly writes `joined`
- No request-to-trip lead time metric stored explicitly
- No passenger response latency metric
- No cancellation reason categories
- No route popularity aggregates
- No driver acceptance-rate and trip fill-rate snapshots
- No feature snapshot table for training or scoring audit

Because of that, this module should start with:

- rule-based recommendation
- rule-based risk scoring with predictive framing
- heuristic fare and seat suggestion

Then later upgrade selected parts into prediction-based services once more labels exist.

## Recommended Architecture

### New module namespace

Create a focused AI support layer instead of mixing logic into `TripService`:

- `app/Services/Ai/AiDecisionSupportService.php`
- `app/Services/Ai/TripMatchingService.php`
- `app/Services/Ai/PassengerRiskScoringService.php`
- `app/Services/Ai/FareSeatSuggestionService.php`
- `app/Services/Ai/FeatureEngineeringService.php`

Optional later:

- `app/Services/Ai/Model/NoShowPredictionService.php`
- `app/Services/Ai/Model/RecommendationRankingService.php`

### Integration points

- `ExploreController` and `TripService::paginateExplore()` for ranking and recommendation explanations
- `TripJoinRequestController` and `TripJoinRequestService` for request review risk cards
- `TripController@create` and `TripController@edit` for fare and seat suggestion
- `DashboardController` for AI summary widgets later

### Design principle

Keep AI logic read-heavy and non-blocking.

- Core trip creation and approval must still work without AI
- AI outputs should be suggestions or scores, not hard requirements
- Every score should expose explanation fields for UI and debugging

## Proposed Database Changes

### 1. Trip recommendation snapshot table

Create `trip_recommendation_logs` for observability and tuning.

Suggested columns:

- `id`
- `user_id`
- `trip_id`
- `context_source` (`explore`, `search`, `dashboard`)
- `match_score`
- `route_score`
- `time_score`
- `seat_score`
- `connection_score`
- `fare_score`
- `explanation_json`
- timestamps

Purpose:

- store why a trip ranked highly
- help tune weights later
- create evidence for report/demo

### 2. Passenger risk feature table

Create `passenger_risk_profiles`.

Suggested columns:

- `id`
- `user_id`
- `risk_score`
- `risk_level`
- `payment_reliability_score`
- `join_request_count`
- `approved_request_count`
- `rejected_request_count`
- `cancelled_request_count`
- `attendance_absent_count`
- `outstanding_amount`
- `overdue_case_count`
- `avg_payment_delay_hours`
- `last_scored_at`
- `feature_payload`
- timestamps

Purpose:

- avoid recalculating complex rollups on every request page load
- prepare a stable record for future model training

### 3. Trip strategy suggestion table

Create `trip_strategy_suggestions`.

Suggested columns:

- `id`
- `trip_id` nullable
- `driver_id`
- `saved_route_id`
- `suggested_fare_total`
- `suggested_fare_per_person`
- `suggested_seat_limit`
- `demand_score`
- `confidence_level`
- `strategy_type` (`draft_form`, `edit_form`, `pre_publish`)
- `input_payload`
- `explanation_json`
- timestamps

Purpose:

- cache suggestion results
- support UI preview and later analytics

### 4. Add explicit historical labels

Add columns to `trip_participants` and `archived_trip_participants`:

- `joined_at` nullable timestamp
- `cancelled_at` nullable timestamp
- `attendance_marked_at` nullable timestamp
- `attendance_source` nullable string

Add columns to `trip_join_requests`:

- `requested_at` nullable timestamp if separate audit is preferred, otherwise use `created_at`
- `decision_duration_minutes` nullable integer
- `decision_feature_snapshot` nullable json

### 5. Optional future table for training data

Create `ai_feature_snapshots`.

Suggested columns:

- `id`
- `entity_type` (`trip`, `join_request`, `user`)
- `entity_id`
- `feature_set`
- `label`
- `snapshot_payload`
- timestamps

This is optional for phase 1, but useful if you later train a real classifier.

## Subfeature Design

## 1. Smart Trip Matching & Recommendation

### User problem

Passengers currently browse public trips with filters, but ranking is still basic. The system can suggest trips that better match passenger route, timing, social trust, and price.

### Practical implementation type

Rule-based ranking engine now.

Prediction-based ranking can come later after enough interaction logs exist.

### Inputs already available

- trip pickup and destination text/coordinates
- trip datetime
- seat availability
- fare per person
- passenger saved routes
- passenger accepted connections
- past joined trips

### Suggested score formula

`match_score = route_score + time_score + seat_score + fare_score + connection_score + history_score`

Example weights:

- route score: 0 to 40
- time score: 0 to 20
- seat score: 0 to 10
- fare score: 0 to 10
- connection score: 0 to 10
- history score: 0 to 10

### Rule-based features

- exact or near destination name match
- pickup proximity using coordinates
- destination proximity using coordinates
- preferred time window based on user past trips
- route repetition match against user saved routes
- available seats bonus
- accepted-connection bonus if driver is already connected
- lower fare bonus relative to route average

### Output for UI

- overall match score
- top 3 explanation chips such as:
  - "Near your usual destination"
  - "Matches your morning travel pattern"
  - "Driver is already in your connections"

### Best insertion point

- decorate results returned by `TripService::paginateExplore()`
- optionally add separate `recommendedTripsForUser(User $user, array $filters = [])`

## 2. Passenger Risk / No-Show Prediction

### User problem

Drivers need a better basis to approve or reject join requests.

### Practical implementation type

Phase 1:

- rule-based predictive risk scoring

Phase 2:

- lightweight prediction model once actual no-show labels exist

### Existing base to reuse

`app/Services/PassengerReliabilityService.php` already computes a payment-based score and labels.

That service should become one input into a broader risk engine, not the whole risk engine.

### New risk dimensions

- payment risk
- request behavior risk
- attendance risk
- cancellation behavior risk
- social trust bonus
- trip-fit bonus

### Proposed score structure

`final_risk_score = 100 - penalties + bonuses`

Penalties:

- outstanding debt
- overdue debt age
- repeated unpaid cases
- repeated rejected requests
- repeated late cancellations
- past absent attendance

Bonuses:

- accepted connection with driver
- previously approved and attended trips
- timely payment history

### Risk bands

- 80 to 100: Low Risk
- 60 to 79: Moderate Risk
- 40 to 59: High Risk
- 0 to 39: Very High Risk

### Explainable output

The driver-facing request card should show:

- risk score
- risk level
- 2 to 4 reason lines
- recommended action hint

Example:

- Risk: Moderate
- Reasons: no outstanding dues, but 2 cancelled requests in past 30 days
- Suggestion: approve if seat demand is low

### What is genuinely prediction-based later

Once labels exist, train on:

- prior absent attendance
- payment delay
- lead time between request and trip datetime
- count of cancelled requests
- history with same driver or route
- connection status with driver

Target label:

- no-show or late-cancel event

## 3. Auto-Suggest Fare / Seat Strategy

### User problem

Drivers manually decide fare split and seat limit, but may underprice, overprice, or choose weak seat settings for public trips.

### Practical implementation type

Heuristic decision-support engine now.

Simple regression or optimization later if enough trip outcome data exists.

### Inputs already available

- route default fare
- trip mode
- seat limit
- current fare split rules
- route history from trips and archived trips
- payment completion and demand signals

### Suggested heuristics

- calculate route median fare from current and archived records
- calculate historical fill rate by route and time bucket
- calculate approval demand by route and time bucket
- suggest seat limit based on observed fill rate
- suggest fare band:
  - conservative
  - balanced
  - fast-fill

### Example outputs

- Suggested seat limit: 3
- Suggested fare per passenger: RM 6.50
- Confidence: Medium
- Explanation:
  - "Trips on this route around 8:00 AM usually fill 3 seats"
  - "RM 6.50 is near your route median and improves join probability"

### Best insertion point

- trip create form
- trip edit form
- optional preview button before publish

## Service Layer Proposal

### `FeatureEngineeringService`

Responsibilities:

- compute reusable route, payment, request, attendance, and social features
- centralize historical aggregates
- normalize data for scoring services

### `TripMatchingService`

Responsibilities:

- score public trips for a passenger
- provide explanation payload
- support Explore sorting by recommendation score

Methods:

- `rankTripsForUser(User $user, Collection $trips, array $filters = []): Collection`
- `scoreTripForUser(User $user, Trip $trip): array`

### `PassengerRiskScoringService`

Responsibilities:

- extend payment reliability into broader risk scoring
- cache risk profile snapshots
- return UI-friendly reason objects

Methods:

- `scoreUserForTrip(User $passenger, Trip $trip, ?User $driver = null): array`
- `refreshRiskProfile(User $user): array`

### `FareSeatSuggestionService`

Responsibilities:

- generate fare and seat suggestions using route history
- explain tradeoffs for driver

Methods:

- `suggestForDraft(User $driver, SavedRoute $route, array $context = []): array`
- `suggestForTrip(Trip $trip): array`

### `AiDecisionSupportService`

Responsibilities:

- facade/orchestrator for controllers
- keep controller integration thin

Methods:

- `recommendTrips(...)`
- `buildPassengerRiskCard(...)`
- `suggestTripStrategy(...)`

## UI Flow Proposal

## Explore flow

Current:

- passenger browses list

New:

- show `Recommended for you` section on top
- add match badge on cards
- add explanation chips below trip meta

Suggested UI additions:

- `92% Match`
- `Near your saved route`
- `2 seats left`
- `Driver in your connections`

## Join request review flow

Current:

- driver sees payment reliability on request card

New:

- replace single reliability widget with broader AI risk widget
- keep payment reliability as one component inside the explanation

Suggested card blocks:

- risk score
- risk reasons
- confidence label
- quick recommendation

## Trip creation flow

Current:

- driver selects route, date, visibility, participants, seat limit

New:

- show AI suggestion panel once route/date/visibility are selected
- suggest:
  - seat limit
  - fare per person
  - demand level
  - strategy mode

Suggested strategy presets:

- `Fast Fill`
- `Balanced`
- `Higher Margin`

## Rule-Based vs Prediction-Based Boundary

### Rule-based in phase 1

- smart trip ranking
- risk scoring
- fare and seat suggestions
- explanation generation

### Prediction-based after more labels

- no-show probability
- probability of trip filling all seats
- probability of payment delay
- personalized ranking based on click/join history

This boundary matters because the current project does not yet store enough high-quality labels for a credible ML-first implementation.

## Practical Phase Plan

## Phase 1: Foundation

- add AI service namespace
- add feature engineering service
- add risk profile table
- add strategy suggestion table
- add recommendation logging table
- extend participant/join-request audit fields

Deliverable:

- backend infrastructure without changing critical workflows

## Phase 2: Passenger Risk

- build broader `PassengerRiskScoringService`
- integrate into request review page
- retain old payment reliability logic as a subscore

Deliverable:

- strongest demo feature

## Phase 3: Smart Trip Matching

- rank Explore trips by recommendation score
- add explanation chips and recommendation section
- add logging for ranked views

Deliverable:

- clear user-facing AI feature for passengers

## Phase 4: Fare / Seat Suggestion

- compute route and time-bucket demand summaries
- show draft-time suggestions in trip create/edit form
- persist accepted suggestion choices for later tuning

Deliverable:

- practical decision support for drivers

## Phase 5: ML Upgrade

- start capturing explicit labels
- export snapshots for offline evaluation
- replace selected heuristics with simple trained models if metrics justify it

Deliverable:

- defendable transition from rule-based AI support to prediction-based support

## Safest Implementation Scope

If this needs to stay FYP-safe and demo-safe:

1. Make Passenger Risk the main feature
2. Make Smart Trip Matching a ranking and explanation layer
3. Make Fare / Seat Suggestion a heuristic recommendation panel

This gives broad AI coverage without overclaiming full machine learning.

## Recommended First Build Order In Code

1. Build migrations for new AI support tables and audit columns
2. Build `FeatureEngineeringService`
3. Build `PassengerRiskScoringService`
4. Integrate request review UI
5. Build `TripMatchingService`
6. Integrate Explore ranking UI
7. Build `FareSeatSuggestionService`
8. Integrate trip form strategy panel

## Notes For Future ML

When enough data exists, export features from:

- join request history
- attendance labels
- payment delays
- route fill rates
- trip acceptance and cancellation patterns

Best early models for this project are simple:

- logistic regression
- gradient boosted trees

Avoid claiming deep learning unless there is a real need and enough data volume.

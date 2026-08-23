<?php

return [
    /**
     * Once a passenger marks a payment paid, the trip driver has this many
     * days to confirm or reject it before the overdue-days clock below stops
     * counting that trip against the passenger — a slow driver shouldn't be
     * able to keep tanking a passenger's score just by sitting on a review.
     * Matches the delay before SendPendingPaymentApprovalReminder first nags
     * the driver, so "driver had a fair chance to act" means the same thing
     * in both places.
     *
     * This isn't a free pass for a passenger who marks paid dishonestly: the
     * clock freezes wherever it already was, it doesn't reset to zero, so
     * lying gains nothing while still unpaid at the point of marking. And if
     * the driver later rejects the request, marked_paid_at is cleared and the
     * trip goes back to counting against real elapsed time from scratch.
     */
    'driver_review_grace_days' => 3,

    /**
     * Most real users pay in one lump sum after seeing what they owe, not
     * per-trip right after each ride — that's exactly why the monthly
     * summary exists. Counting overdue days from trip_datetime punished that
     * completely normal behavior: a trip from early in the month could
     * already be deep into a penalty tier before the summary reporting it
     * had even been sent.
     *
     * Instead, the overdue clock for a trip now starts from summary_day_of_
     * month of the month AFTER the trip (the day the summary reporting that
     * trip goes out — matches SendMonthlyPaymentSummary's monthlyOn(3, ...)),
     * plus days_after_summary as a settle-up window. Only delay past that
     * point counts against the passenger.
     */
    'overdue_grace' => [
        'summary_day_of_month' => 3,
        'days_after_summary' => 14,
    ],

    'score' => [
        'base' => 5.0,
        'min' => 1.0,
        'max' => 5.0,
        'precision' => 1,
    ],

    'amount_penalties' => [
        ['min' => 0.0, 'max' => 0.0, 'penalty' => 0.0],
        ['min' => 0.01, 'max' => 10.0, 'penalty' => 0.1],
        ['min' => 10.01, 'max' => 30.0, 'penalty' => 0.3],
        ['min' => 30.01, 'max' => 50.0, 'penalty' => 0.6],
        ['min' => 50.01, 'max' => null, 'penalty' => 1.0],
    ],

    'overdue_penalties' => [
        ['min' => 0, 'max' => 7, 'penalty' => 0.1],
        ['min' => 8, 'max' => 14, 'penalty' => 0.3],
        ['min' => 15, 'max' => 30, 'penalty' => 0.7],
        ['min' => 31, 'max' => 60, 'penalty' => 1.2],
        ['min' => 61, 'max' => null, 'penalty' => 1.8],
    ],

    'case_penalties' => [
        ['min' => 0, 'max' => 0, 'penalty' => 0.0],
        ['min' => 1, 'max' => 1, 'penalty' => 0.2],
        ['min' => 2, 'max' => 3, 'penalty' => 0.5],
        ['min' => 4, 'max' => null, 'penalty' => 1.0],
    ],

    'risk_labels' => [
        ['min' => 4.5, 'max' => 5.0, 'label' => 'Excellent'],
        ['min' => 3.8, 'max' => 4.4, 'label' => 'Good'],
        ['min' => 3.0, 'max' => 3.7, 'label' => 'Moderate'],
        ['min' => 2.0, 'max' => 2.9, 'label' => 'Risky'],
        ['min' => 1.0, 'max' => 1.9, 'label' => 'High Risk'],
    ],
];


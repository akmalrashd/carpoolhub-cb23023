<?php

return [
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


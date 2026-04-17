<?php

return [
    'trip_matching' => [
        'weights' => [
            'route' => 40,
            'time' => 20,
            'seat' => 10,
            'fare' => 10,
            'connection' => 10,
            'history' => 10,
        ],
    ],

    'passenger_risk' => [
        'score' => [
            'min' => 0,
            'max' => 100,
            'base' => 70,
        ],
        'bands' => [
            ['min' => 80, 'max' => 100, 'label' => 'Low Risk'],
            ['min' => 60, 'max' => 79, 'label' => 'Moderate Risk'],
            ['min' => 40, 'max' => 59, 'label' => 'High Risk'],
            ['min' => 0, 'max' => 39, 'label' => 'Very High Risk'],
        ],
    ],

    'fare_strategy' => [
        'demand_bands' => [
            ['min' => 0, 'max' => 39, 'label' => 'Low'],
            ['min' => 40, 'max' => 69, 'label' => 'Medium'],
            ['min' => 70, 'max' => 100, 'label' => 'High'],
        ],
    ],
];

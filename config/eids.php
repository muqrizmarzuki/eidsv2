<?php

// Weightage, sampling formulas, and the checklist question bank now live in the
// database (weightage_overall, weightage_architectural_elements, weightage_locations,
// sampling_rules, checklist_items — see WeightageSeeder/ChecklistItemSeeder) so they
// can vary per building category and be admin-edited. Rating thresholds and default
// sample locations remain here / in Settings, unaffected by that redesign.
return [
    'rating' => [
        'baik'      => 85,
        'sederhana' => 70,
    ],

    'default_locations' => [
        'Living Room', 'Service Area', 'Passageway',
        'Bedroom 1', 'Bedroom 2', 'Bedroom 3', 'Bathroom',
    ],
];

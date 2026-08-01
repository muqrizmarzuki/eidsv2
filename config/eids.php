<?php

return [
    'sample_divisor'   => 60,
    'levelling_max_mm' => 3.0,
    'joint_max_mm'     => 1.0,
    'me_score'         => 2.00,
    'external_score'   => 11.80,

    'rating' => [
        'baik'      => 85,
        'sederhana' => 70,
    ],

    'default_locations' => [
        'Living Room', 'Service Area', 'Passageway',
        'Bedroom 1', 'Bedroom 2', 'Bedroom 3', 'Bathroom',
    ],

    'components' => [
        'A1_FLOOR'    => ['name' => 'Floor (Lantai)',                  'weightage' => 18],
        'A2_WALL'     => ['name' => 'Internal Wall (Dinding Dalam)',   'weightage' => 18],
        'A3_CEILING'  => ['name' => 'Ceiling (Siling)',                'weightage' => 10],
        'A4_DOOR'     => ['name' => 'Door (Pintu)',                    'weightage' => 10],
        'A5_WINDOW'   => ['name' => 'Window (Tingkap)',                'weightage' =>  8],
        'A6_FIXTURES' => ['name' => 'Internal Fixtures',               'weightage' =>  5],
        'A7_ROOF'     => ['name' => 'Roof (Bumbung)',                  'weightage' => 10],
        'A8_EXT_WALL' => ['name' => 'External Wall (Dinding Luar)',    'weightage' => 10],
    ],
];

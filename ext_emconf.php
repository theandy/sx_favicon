<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Site Favicons (light/dark + svg)',
    'description' => 'Generiert Favicons aus SVG/PNG/JPG-Quellen und liefert sie unter stabilen Root-Pfaden aus.',
    'category' => 'be',
    'version' => '1.0.0',
    'state' => 'stable',
    'author' => 'Andreas Loewer',
    'author_company' => 'andreas-loewer',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99'
        ],
    ],
];

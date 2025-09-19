<?php
return [
    'ctrl' => [
        'title' => 'Favicon-Konfiguration',
        'label' => 'site_identifier',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'iconfile' => 'EXT:sx_favicon/Resources/Public/Icons/ext.svg',
        'rootLevel' => 1,
    ],
    'columns' => [
        'site_identifier' => [
            'label' => 'Site Identifier',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
                'eval' => 'trim',
            ],
        ],
        'svg' => [
            'label' => 'SVG Quelle',
            'config' => [
                'type' => 'file',
                'appearance' => ['createNewRelationLinkTitle' => 'SVG auswählen'],
                'allowed' => 'svg',
                'maxitems' => 1,
            ],
        ],
        'light' => [
            'label' => 'PNG/JPG (Light)',
            'config' => [
                'type' => 'file',
                'appearance' => ['createNewRelationLinkTitle' => 'Light-Icon auswählen'],
                'allowed' => 'png,jpg,jpeg',
                'maxitems' => 1,
            ],
        ],
        'dark' => [
            'label' => 'PNG/JPG (Dark)',
            'config' => [
                'type' => 'file',
                'appearance' => ['createNewRelationLinkTitle' => 'Dark-Icon auswählen'],
                'allowed' => 'png,jpg,jpeg',
                'maxitems' => 1,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'site_identifier, svg, light, dark']
    ],
];

<?php
declare(strict_types=1);

return [
    'site_favicons_v2' => [
        'parent' => 'site',
        'access' => 'admin',
        'workspaces' => 'live',
        'iconIdentifier' => 'module-sx-favicon',
        'labels' => 'LLL:EXT:sx_favicon/Resources/Private/Language/locallang_mod.xlf',

        // WICHTIG: benannte Route aus Routes.php
        'route' => 'sx_favicon.module',
        'additionalRoutes' => [
            'sx_favicon.module.save',
        ],
    ],
];

<?php
declare(strict_types=1);

use AndreasLoewer\SxFavicon\Controller\ConfigController;

return [
    'site_favicons_v2' => [              // <— NEUER KEY
        'parent' => 'site',
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/site/sxfv',     // <— NEUER PFAD
        'iconIdentifier' => 'module-sx-favicon',
        'labels' => 'LLL:EXT:sx_favicon/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => ['target' => ConfigController::class.'::index'],
            'save'     => ['path'=>'/save','target'=>ConfigController::class.'::save'],
        ],
    ],
];

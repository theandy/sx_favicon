<?php

declare(strict_types=1);

use AndreasLoewer\SxFavicon\Controller\ConfigController;

return [
    // interner Bezeichner – wird auch als Route-Identifer genutzt
    'site_favicons' => [
        'parent' => 'site',                    // „Site Management“
        'access' => 'admin',                   // nur Admins
        'workspaces' => 'live',
        // optional eigener Pfad; sonst Fallback: /module/<parent>/<identifier>
        'path' => '/module/site/favicons',
        'iconIdentifier' => 'module-sx-favicon', // siehe Icons.php unten
        'labels' => 'LLL:EXT:sx_favicon/Resources/Private/Language/locallang_mod.xlf',

        // Extbase-Modul: Extension-Name und Controller/Actions
        'extensionName' => 'SxFavicon',
        'controllerActions' => [
            ConfigController::class => ['index', 'save'],
        ],
    ],
];

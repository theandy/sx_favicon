<?php
declare(strict_types=1);

use AndreasLoewer\SxFavicon\Controller\ConfigController;

return [
    'sx_favicon.module' => [
        'path'   => '/module/site/sxfv',
        'target' => ConfigController::class . '::index',
    ],
    'sx_favicon.module.save' => [
        'path'   => '/module/site/sxfv/save',
        'target' => ConfigController::class . '::save',
    ],
];

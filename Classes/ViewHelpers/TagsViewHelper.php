<?php
declare(strict_types=1);

namespace AndreasLoewer\SxFavicon\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class TagsViewHelper extends AbstractViewHelper
{
    public function render(): string
    {
        return implode("\n", [
            '<link rel="icon" href="/favicon.ico" sizes="any">',
            '<link rel="icon" type="image/svg+xml" href="/favicon.svg">',
            '<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png" media="(prefers-color-scheme: light)">',
            '<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32-dark.png" media="(prefers-color-scheme: dark)">',
            '<link rel="apple-touch-icon" href="/apple-touch-icon.png">',
            '<link rel="manifest" href="/site.webmanifest">'
        ]);
    }
}

<?php
declare(strict_types=1);

namespace AndreasLoewer\SxFavicon\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Site\SiteFinder;

final class FaviconMiddleware implements \Psr\Http\Server\MiddlewareInterface
{
    public function __construct(private readonly SiteFinder $siteFinder) {}

    public function process(ServerRequestInterface $request, \Psr\Http\Server\RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        $map = [
            '/favicon.ico'            => 'favicon.ico',
            '/favicon-16x16.png'      => 'favicon-16x16.png',
            '/favicon-32x32.png'      => 'favicon-32x32.png',
            '/favicon-48x48.png'      => 'favicon-48x48.png',
            '/favicon-96x96.png'      => 'favicon-96x96.png',
            '/favicon-32x32-dark.png' => 'favicon-32x32-dark.png',
            '/apple-touch-icon.png'   => 'apple-touch-icon.png',
            '/site.webmanifest'       => 'site.webmanifest',
            '/favicon.svg'            => 'favicon.svg',
        ];

        if (!isset($map[$path])) {
            return $handler->handle($request);
        }

        $site = $request->getAttribute('site') ?? $this->siteFinder->getDefaultSite();
        $siteId = $site->getIdentifier();
        $file = PATH_site . 'typo3temp/assets/favicons/' . $siteId . '/' . $map[$path];

        if (!is_file($file)) {
            return $handler->handle($request);
        }

        $mime = match (true) {
            str_ends_with($file, '.ico') => 'image/x-icon',
            str_ends_with($file, '.png') => 'image/png',
            str_ends_with($file, '.svg') => 'image/svg+xml',
            str_ends_with($file, '.webmanifest') => 'application/manifest+json',
            default => 'application/octet-stream',
        };

        $response = (new Response())
            ->withHeader('Content-Type', $mime)
            ->withHeader('Cache-Control', 'public, max-age=31536000, immutable');

        $response->getBody()->write(file_get_contents($file));
        return $response;
    }
}

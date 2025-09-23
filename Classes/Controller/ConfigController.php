<?php
declare(strict_types=1);

namespace AndreasLoewer\SxFavicon\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;

final class ConfigController
{
    public function __construct(private readonly ModuleTemplateFactory $moduleTemplateFactory) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $mt = $this->moduleTemplateFactory->create($request);
        $mt->setTitle('Favicons');
        $mt->setContent('<div class="module"><h1>Favicons</h1><p>It works 🎉</p></div>');
        return $mt->renderResponse();
    }

    public function save(ServerRequestInterface $request): ResponseInterface
    {
        return $this->index($request);
    }
}

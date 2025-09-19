<?php
declare(strict_types=1);

namespace AndreasLoewer\SxFavicon\Controller;

use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use AndreasLoewer\SxFavicon\Service\GeneratorService;

final class ConfigController
{
    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly ConnectionPool $connectionPool,
        private readonly SiteFinder $siteFinder,
        private readonly GeneratorService $generator
    ) {}

    public function index(): HtmlResponse
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request ?? null);
        $sites = iterator_to_array($this->siteFinder->getAllSites());
        $content = $this->renderTemplate('Index.html', [
            'sites' => $sites,
            'config' => null
        ]);
        $moduleTemplate->setContent($content);
        return new HtmlResponse($moduleTemplate->renderContent());
    }

    public function save(): HtmlResponse
    {
        $post = $_POST ?? [];

        $siteIdentifier = (string)($post['site_identifier'] ?? '');
        $svg = (int)($post['svg'] ?? 0);
        $light = (int)($post['light'] ?? 0);
        $dark = (int)($post['dark'] ?? 0);

        $qb = $this->connectionPool->getQueryBuilderForTable('tx_sxfavicon_config');

        // upsert simple
        $exists = $qb->count('uid')->from('tx_sxfavicon_config')
            ->where($qb->expr()->eq('site_identifier', $qb->createNamedParameter($siteIdentifier)))
            ->executeQuery()->fetchOne();

        if ($exists) {
            $qb->update('tx_sxfavicon_config')
                ->set('svg', $svg)
                ->set('light', $light)
                ->set('dark', $dark)
                ->set('tstamp', time())
                ->where($qb->expr()->eq('site_identifier', $qb->createNamedParameter($siteIdentifier)))
                ->executeStatement();
        } else {
            $qb->insert('tx_sxfavicon_config')
                ->values([
                    'site_identifier' => $siteIdentifier,
                    'svg' => $svg,
                    'light' => $light,
                    'dark' => $dark,
                    'crdate' => time(),
                    'tstamp' => time(),
                ])->executeStatement();
        }

        // direkt generieren
        $this->generator->generateForSite($siteIdentifier);

        // zurück zum Index
        return $this->index();
    }

    private function renderTemplate(string $templateName, array $vars = []): string
    {
        // Minimalist: einfache PHP-Template-Einbindung mit Variablen
        extract($vars, EXTR_SKIP);
        ob_start();
        include GeneralUtility::getFileAbsFileName('EXT:sx_favicon/Resources/Private/Templates/Config/' . $templateName);
        return (string)ob_get_clean();
    }
}

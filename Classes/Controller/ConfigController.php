<?php
declare(strict_types=1);

namespace AndreasLoewer\SxFavicon\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

final class ConfigController
{
    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly ConnectionPool $connectionPool,
        private readonly SiteFinder $siteFinder,
    ) {}

    /**
     * Formular anzeigen.
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->setTitle('Favicons');

        [$site, $siteIdentifier] = $this->resolveSite($request);
        $config = $this->fetchConfig($siteIdentifier);

        $moduleTemplate->setContent(
            $this->renderForm($site, $siteIdentifier, $config)
        );

        return $moduleTemplate->renderResponse();
    }

    /**
     * Speichern & zurück zur Übersicht.
     */
    public function save(ServerRequestInterface $request): ResponseInterface
    {
        [$site, $siteIdentifier] = $this->resolveSite($request);

        $post  = $request->getParsedBody() ?? [];
        $svg   = trim((string)($post['svg']   ?? ''));
        $light = trim((string)($post['light'] ?? ''));
        $dark  = trim((string)($post['dark']  ?? ''));

        $this->upsertConfig($siteIdentifier, $svg, $light, $dark);

        // Optional: Hier könnte ein Generator-Service aufgerufen werden
        // $this->generatorService->generate($siteIdentifier, $svg, $light, $dark);

        return $this->index($request);
    }

    // ---------------------------------------------------------------------
    // Intern: Datenzugriff & Rendering
    // ---------------------------------------------------------------------

    /**
     * Aktuelle Site aus Request (Query ?site=...) oder erste verfügbare Site.
     *
     * @return array{0: Site, 1: string}
     */
    private function resolveSite(ServerRequestInterface $request): array
    {
        $query = $request->getQueryParams();
        $requestedIdentifier = isset($query['site']) ? (string)$query['site'] : null;

        $siteAttr = $request->getAttribute('site');
        if ($siteAttr instanceof Site) {
            return [$siteAttr, $siteAttr->getIdentifier()];
        }

        $sites = $this->siteFinder->getAllSites();
        if ($requestedIdentifier && isset($sites[$requestedIdentifier])) {
            return [$sites[$requestedIdentifier], $requestedIdentifier];
        }

        /** @var Site $first */
        $first = reset($sites);
        $identifier = $first instanceof Site ? $first->getIdentifier() : 'default';

        return [$first, $identifier];
    }

    /**
     * Konfiguration für Site lesen.
     *
     * @return array{svg:string, light:string, dark:string}
     */
    private function fetchConfig(string $siteIdentifier): array
    {
        $qb = $this->connection()->createQueryBuilder();
        $row = $qb->select('svg', 'light', 'dark')
            ->from('tx_sxfavicon_config')
            ->where(
                $qb->expr()->eq('site_identifier', $qb->createNamedParameter($siteIdentifier))
            )
            ->executeQuery()
            ->fetchAssociative() ?: [];

        return [
            'svg'   => (string)($row['svg']   ?? ''),
            'light' => (string)($row['light'] ?? ''),
            'dark'  => (string)($row['dark']  ?? ''),
        ];
    }

    /**
     * Konfiguration upserten.
     */
    private function upsertConfig(string $siteIdentifier, string $svg, string $light, string $dark): void
    {
        $conn = $this->connection();
        $qb = $conn->createQueryBuilder();

        $exists = (int)$qb->count('uid')
            ->from('tx_sxfavicon_config')
            ->where($qb->expr()->eq('site_identifier', $qb->createNamedParameter($siteIdentifier)))
            ->executeQuery()
            ->fetchOne();

        if ($exists > 0) {
            $conn->update(
                'tx_sxfavicon_config',
                [
                    'svg'    => $svg,
                    'light'  => $light,
                    'dark'   => $dark,
                    'tstamp' => time(),
                ],
                ['site_identifier' => $siteIdentifier]
            );
        } else {
            $conn->insert(
                'tx_sxfavicon_config',
                [
                    'site_identifier' => $siteIdentifier,
                    'svg'    => $svg,
                    'light'  => $light,
                    'dark'   => $dark,
                    'tstamp' => time(),
                    'crdate' => time(),
                ]
            );
        }
    }

    /**
     * Einfaches Formular (ohne Fluid) für TYPO3 BE.
     *
     * @param array{svg:string, light:string, dark:string} $config
     */
    private function renderForm(Site $site, string $siteIdentifier, array $config): string
    {
        $h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $siteOptions = '';
        foreach ($this->siteFinder->getAllSites() as $id => $s) {
            $sel = $id === $siteIdentifier ? ' selected' : '';
            $siteOptions .= '<option value="'.$h((string)$id).'"'.$sel.'>'.$h((string)$id).'</option>';
        }

        $style = 'style="display:block;width:100%;max-width:640px"';

        $html = [];
        $html[] = '<div class="module">';
        $html[] = '  <h1>Favicons</h1>';

        // Site-Wechsel (GET)
        $html[] = '  <form method="get" action="">';
        $html[] = '    <input type="hidden" name="route" value="/module/site/favicons">';
        $html[] = '    <label>Site&nbsp;';
        $html[] = '      <select name="site" onchange="this.form.submit()">'.$siteOptions.'</select>';
        $html[] = '    </label>';
        $html[] = '  </form>';

        // Speichern (POST)
        $html[] = '  <form method="post" action="?route=/module/site/favicons/save&amp;site='.$h($siteIdentifier).'">';
        $html[] = '    <div class="form-section">';
        $html[] = '      <label>SVG (FileReference UID oder Pfad)';
        $html[] = '        <input '.$style.' type="text" name="svg" value="'.$h($config['svg']).'">';
        $html[] = '      </label>';
        $html[] = '    </div>';

        $html[] = '    <div class="form-section">';
        $html[] = '      <label>PNG/JPG (Light)';
        $html[] = '        <input '.$style.' type="text" name="light" value="'.$h($config['light']).'">';
        $html[] = '      </label>';
        $html[] = '    </div>';

        $html[] = '    <div class="form-section">';
        $html[] = '      <label>PNG/JPG (Dark)';
        $html[] = '        <input '.$style.' type="text" name="dark" value="'.$h($config['dark']).'">';
        $html[] = '      </label>';
        $html[] = '    </div>';

        $html[] = '    <div class="form-section">';
        $html[] = '      <button type="submit" class="btn btn-primary">Speichern</button>';
        $html[] = '    </div>';
        $html[] = '  </form>';

        $html[] = '  <hr>';
        $html[] = '  <p>Bereitgestellte Pfade (wenn Quellen konfiguriert & generiert):</p>';
        $html[] = '  <ul>';
        $html[] = '    <li><code>/favicon.ico</code></li>';
        $html[] = '    <li><code>/favicon.svg</code></li>';
        $html[] = '    <li><code>/favicon-32x32.png</code> (hell)</li>';
        $html[] = '    <li><code>/favicon-32x32-dark.png</code> (dunkel)</li>';
        $html[] = '    <li><code>/apple-touch-icon.png</code></li>';
        $html[] = '    <li><code>/site.webmanifest</code></li>';
        $html[] = '  </ul>';
        $html[] = '</div>';

        return implode("\n", $html);
    }

    private function connection(): Connection
    {
        return $this->connectionPool->getConnectionForTable('tx_sxfavicon_config');
    }
}

<?php
declare(strict_types=1);

namespace AndreasLoewer\SxFavicon\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Backend-Controller für das Favicons-Modul (TYPO3 12.4).
 *
 * - PSR-7 kompatibel (ServerRequestInterface)
 * - Verwendet ModuleTemplateFactory::create($request)
 * - Einfache HTML-Ausgabe (kein Fluid notwendig)
 *
 * Erwartete Umgebung:
 * - Configuration/Backend/Modules.php mit routes -> target auf ::index / ::save
 * - Configuration/Services.yaml: Controller als public + autowire
 * - Configuration/Icons.php: iconIdentifier registriert
 * - Tabelle tx_sxfavicon_config (site_identifier, svg, light, dark, tstamp)
 */
final class ConfigController
{
    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly ConnectionPool $connectionPool,
        private readonly SiteFinder $siteFinder,
        // Falls du später Generator-Logik einhängen willst, kannst du hier eine Service-Dependency ergänzen.
        // private readonly \AndreasLoewer\SxFavicon\Service\GeneratorService $generatorService,
    ) {}

    /**
     * Übersicht + Formular.
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->setTitle('Favicons');

        // Aktuelle Site ermitteln (aus Query ?site=... oder Erste im System)
        [$site, $siteIdentifier] = $this->resolveSite($request);

        // Aktuelle Konfiguration lesen
        $config = $this->fetchConfig($siteIdentifier);

        // HTML rendern (einfaches Formular)
        $content = $this->renderForm($site, $siteIdentifier, $config);

        // Optional: Messages/Buttons könnten hier ergänzt werden
        $moduleTemplate->setContent($content);

        return $moduleTemplate->renderResponse();
    }

    /**
     * Speichern & zurück zur Übersicht.
     */
    public function save(ServerRequestInterface $request): ResponseInterface
    {
        [$site, $siteIdentifier] = $this->resolveSite($request);

        $post = $request->getParsedBody() ?? [];
        $svg   = trim((string)($post['svg']   ?? ''));
        $light = trim((string)($post['light'] ?? ''));
        $dark  = trim((string)($post['dark']  ?? ''));

        $this->upsertConfig($siteIdentifier, $svg, $light, $dark);

        // TODO: Hier ggf. Favicons generieren lassen:
        // $this->generatorService->generate($siteIdentifier, $svg, $light, $dark);

        // Zurück zur Übersicht (gleiches Request-Objekt wiederverwenden)
        return $this->index($request);
    }

    // ---------------------------------------------------------------------
    // Intern: Datenzugriff & Rendering
    // ---------------------------------------------------------------------

    /**
     * Ermittelt die aktuelle Site anhand von ?site=<identifier> oder nimmt die erste Site.
     *
     * @return array{0: Site, 1: string} [Site, siteIdentifier]
     */
    private function resolveSite(ServerRequestInterface $request): array
    {
        $query = $request->getQueryParams();
        $requestedIdentifier = isset($query['site']) ? (string)$query['site'] : null;

        // Versuche direkte Ermittlung über Request-Attribute (falls vorhanden)
        $siteAttr = $request->getAttribute('site');
        if ($siteAttr instanceof Site) {
            return [$siteAttr, $siteAttr->getIdentifier()];
        }

        // Sonst alle Sites holen
        $sites = $this->siteFinder->getAllSites();
        if ($requestedIdentifier && isset($sites[$requestedIdentifier])) {
            return [$sites[$requestedIdentifier], $requestedIdentifier];
        }

        // Fallback: erste Site
        /** @var Site $firstSite */
        $firstSite = reset($sites);
        $identifier = $firstSite instanceof Site ? $firstSite->getIdentifier() : 'default';

        return [$firstSite, $identifier];
    }

    /**
     * Liest die Konfiguration zu einer Site.
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
            ->fetchAssociative();

        return [
            'svg'   => (string)($row['svg']   ?? ''),
            'light' => (string)($row['light'] ?? ''),
            'dark'  => (string)($row['dark']  ?? ''),
        ];
    }

    /**
     * Upsert der Konfiguration für eine Site.
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
                ['site_identifier' => $siteIdentifier],
                [
                    \PDO::PARAM_STR,
                    \PDO::PARAM_STR,
                    \PDO::PARAM_STR,
                    \PDO::PARAM_INT,
                ]
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
                ],
                [
                    \PDO::PARAM_STR,
                    \PDO::PARAM_STR,
                    \PDO::PARAM_STR,
                    \PDO::PARAM_STR,
                    \PDO::PARAM_INT,
                    \PDO::PARAM_INT,
                ]
            );
        }
    }

    /**
     * Simples HTML-Formular (ohne Fluid), kompatibel mit TYPO3 BE.
     *
     * @param Site   $site
     * @param string $siteIdentifier
     * @param array{svg:string, light:string, dark:string} $config
     */
    private function renderForm(Site $site, string $siteIdentifier, array $config): string
    {
        $h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $siteOptions = '';
        foreach ($this->siteFinder->getAllSites() as $id => $s) {
            $sel = $id === $siteIdentifier ? ' selected' : '';
            $siteOptions .= '<option value="'.$h($id).'"'.$sel.'>'.$h($id).'</option>';
        }

        // Einfache Styles für Abstände im BE
        $style = 'style="display:block;width:100%;max-width:640px"';

        $html = [];
        $html[] = '<div class="module">';
        $html[] = '  <h1>Favicons</h1>';
        $html[] = '  <form method="get" action="">';
        $html[] = '    <input type="hidden" name="route" value="/module/site/favicons">';
        $html[] = '    <label>Site&nbsp;';
        $html[] = '      <select name="site" onchange="this.form.submit()">'.$siteOptions.'</select>';
        $html[] = '    </label>';
        $html[] = '  </form>';

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
        $html[] = '  <p>Nach dem Speichern liefert die Middleware (bei korrekt konfigurierten Quellen) folgende Pfade aus:</p>';
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

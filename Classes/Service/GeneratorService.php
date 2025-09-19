<?php
declare(strict_types=1);

namespace AndreasLoewer\SxFavicon\Service;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Processing\ImageProcessingInstructions;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class GeneratorService
{
    /** gängige Größen inkl. Apple & PWA */
    private const SIZES_PNG = [16, 32, 48, 96, 180, 192, 512];

    public function __construct(
        private readonly ImageService $imageService,
        private readonly ConnectionPool $connectionPool
    ) {}

    public function generateForSite(string $siteIdentifier): void
    {
        $config = $this->loadConfig($siteIdentifier);
        if (!$config) {
            return;
        }

        $targetDir = PATH_site . 'typo3temp/assets/favicons/' . $siteIdentifier . '/';
        if (!is_dir($targetDir)) {
            GeneralUtility::mkdir_deep($targetDir);
        }

        // PNGs für light/dark
        foreach (['light' => $config['light'], 'dark' => $config['dark']] as $mode => $fileUid) {
            if (!$fileUid) {
                continue;
            }
            $fileRef = $this->resolveFileReference((int)$fileUid);
            if (!$fileRef) {
                continue;
            }
            foreach (self::SIZES_PNG as $size) {
                $processed = $this->imageService->applyProcessingInstructions(
                    $fileRef,
                    [
                        ImageProcessingInstructions::WIDTH => $size,
                        ImageProcessingInstructions::HEIGHT => $size
                    ]
                );
                $suffix = $mode === 'dark' ? '-dark' : '';
                copy(
                    $processed->getForLocalProcessing(),
                    $targetDir . "favicon-{$size}x{$size}{$suffix}.png"
                );
            }
        }

        // ICO erzeugen (bevorzugt aus light)
        $icoSourceUid = $config['light'] ?: ($config['svg'] ?: $config['dark']);
        if ($icoSourceUid) {
            $icoRef = $this->resolveFileReference((int)$icoSourceUid);
            if ($icoRef) {
                $this->buildIco($icoRef, $targetDir . 'favicon.ico');
            }
        }

        // Apple Touch (180x180 aus Light)
        if ($config['light']) {
            $lightRef = $this->resolveFileReference((int)$config['light']);
            if ($lightRef) {
                $processed = $this->imageService->applyProcessingInstructions($lightRef, [
                    ImageProcessingInstructions::WIDTH => 180,
                    ImageProcessingInstructions::HEIGHT => 180
                ]);
                copy($processed->getForLocalProcessing(), $targetDir . 'apple-touch-icon.png');
            }
        }

        // SVG kopieren + Raster-Fallback
        if ($config['svg']) {
            $svgRef = $this->resolveFileReference((int)$config['svg']);
            if ($svgRef) {
                copy($svgRef->getOriginalFile()->getForLocalProcessing(false), $targetDir . 'favicon.svg');
                $processed = $this->imageService->applyProcessingInstructions($svgRef, [
                    ImageProcessingInstructions::WIDTH => 512,
                    ImageProcessingInstructions::HEIGHT => 512
                ]);
                copy($processed->getForLocalProcessing(), $targetDir . 'favicon.svg.png');
            }
        }

        // Webmanifest
        file_put_contents($targetDir . 'site.webmanifest', json_encode([
            'name' => $siteIdentifier,
            'icons' => [
                ['src' => '/favicon-192x192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/favicon-512x512.png', 'sizes' => '512x512', 'type' => 'image/png']
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    private function resolveFileReference(int $uid): ?FileReference
    {
        try {
            /** @var \TYPO3\CMS\Core\Resource\ResourceFactory $resourceFactory */
            $resourceFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class);
            return $resourceFactory->getFileReferenceObject($uid);
        } catch (\Throwable) {
            return null;
        }
    }

    /** Lädt die Konfig für eine Site als einfache Row */
    private function loadConfig(string $siteIdentifier): ?array
    {
        $qb = $this->connectionPool->getQueryBuilderForTable('tx_sxfavicon_config');
        $row = $qb->select('*')
            ->from('tx_sxfavicon_config')
            ->where($qb->expr()->eq('site_identifier', $qb->createNamedParameter($siteIdentifier)))
            ->executeQuery()
            ->fetchAssociative();

        return $row ?: null;
    }

    private function buildIco(FileReference $fileRef, string $icoPath): void
    {
        if (class_exists(\Imagick::class)) {
            $sizes = [16, 32, 48, 64];
            $ico = new \Imagick();
            foreach ($sizes as $s) {
                $processed = $this->imageService->applyProcessingInstructions($fileRef, [
                    ImageProcessingInstructions::WIDTH => $s,
                    ImageProcessingInstructions::HEIGHT => $s
                ]);
                $im = new \Imagick($processed->getForLocalProcessing());
                $im->setImageFormat('png');
                $ico->addImage($im);
            }
            $ico->setImageFormat('ico');
            $ico->writeImage($icoPath);
            return;
        }

        // Fallback (kein Imagick): 32x32 PNG als sichtbarstes Favicon
        $processed = $this->imageService->applyProcessingInstructions($fileRef, [
            ImageProcessingInstructions::WIDTH => 32,
            ImageProcessingInstructions::HEIGHT => 32
        ]);
        copy($processed->getForLocalProcessing(), dirname($icoPath) . '/favicon-32x32.png');
    }
}

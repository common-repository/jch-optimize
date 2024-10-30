<?php

/**
 * JCH Optimize - Performs several front-end optimizations for fast downloads
 *
 * @package   jchoptimize/core
 * @author    Samuel Marshall <samuel@jch-optimize.net>
 * @copyright Copyright (c) 2022 Samuel Marshall / JCH Optimize
 * @license   GNU/GPLv3, or later. See LICENSE file
 *
 *  If LICENSE file missing, see <http://www.gnu.org/licenses/>.
 */

namespace JchOptimize\Core\Css\Callbacks;

use JchOptimize\Core\Combiner;
use JchOptimize\Core\Html\FilesManager;
use JchOptimize\Core\Uri\Utils;

use function array_merge;
use function defined;
use function extension_loaded;
use function preg_match;
use function preg_replace;

defined('_JCH_EXEC') or die('Restricted access');

class HandleAtRules extends AbstractCallback
{
    private array $atImports = [];

    private array $gFonts = [];

    private array $fontFace = [];
    /**
     * @var array
     */
    private array $cssInfos;

    /**
     * @inheritDoc
     */
    public function processMatches(array $matches, string $context): string
    {
        if ($context == 'charset') {
            return '';
        }

        if ($context == 'font-face') {
            if (!preg_match('#font-display#i', $matches[0])) {
                $matches[0] = preg_replace('#;?\s*}$#', ';font-display:swap;}', $matches[0]);
            } elseif (preg_match('#font-display#i', $matches[0]) && $this->params->get('pro_force_swap_policy', '1')) {
                $matches[0] = preg_replace('#font-display[^;}]++#i', 'font-display:swap', $matches[0]);
            }

            if ($this->params->get('pro_optimizeFonts_enable', '0') && empty($this->cssInfos['combining-fontface'])) {
                $this->fontFace[] = [
                    'content' => $matches[0],
                    'media' => $this->cssInfos['media']
                ];

                return '';
            }

            return $matches[0];
        }

        //At this point we should be in import context
        $uri = Utils::uriFor($matches[3]);
        $media = $matches[4];

        //If we're importing a Google font file we may need to optimize it
        if ($this->params->get('pro_optimizeFonts_enable', '0')
            && $uri->getHost() == 'fonts.googleapis.com') {
            //We have to add Gfonts here so this info will be cached
            $this->gFonts[] = [
                'url' => $uri,
                'media' => $media
            ];

            return '';
        }

        //Don't import Google font files even if replaceImports is enabled
        if (!$this->params->get('replaceImports', '0')
            || $uri->getHost() == 'fonts.googleapis.com') {
            $this->atImports[] = $matches[0];

            return '';
        }

        /** @var FilesManager $oFilesManager */
        $oFilesManager = $this->getContainer()->get(FilesManager::class);

        if ((string)$uri == ''
            || ($uri->getScheme() == 'https' && !extension_loaded('openssl'))
        ) {
            return $matches[0];
        }

        if ($oFilesManager->isDuplicated($uri)) {
            return '';
        }

        $aUrlArray = array();

        $aUrlArray[0]['url'] = $uri;
        $aUrlArray[0]['media'] = $media;

        /** @var Combiner $oCombiner */
        $oCombiner = $this->getContainer()->get(Combiner::class);

        try {
            $importContents = $oCombiner->combineFiles($aUrlArray, 'css');
        } catch (\Exception $e) {
            return $matches[0];
        }

        $this->atImports = array_merge($this->atImports, [$importContents['import']]);
        $this->fontFace = array_merge($this->fontFace, $importContents['font-face']);
        $this->gFonts = array_merge($this->gFonts, $importContents['gfonts']);

        return $importContents['content'];
    }

    public function setCssInfos($cssInfos): void
    {
        $this->cssInfos = $cssInfos;
    }

    public function getImports(): array
    {
        return $this->atImports;
    }

    public function getGFonts(): array
    {
        return $this->gFonts;
    }

    public function getFontFace(): array
    {
        return $this->fontFace;
    }
}

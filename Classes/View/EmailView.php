<?php

namespace Blueways\BwEmail\View;

use Blueways\BwEmail\Domain\Model\Contact;
use Hampe\Inky\Inky;
use PHPHtmlParser\Exceptions\CircularException;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Error\Http\ServiceUnavailableException;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class EmailView extends StandaloneView
{
    protected ?int $pid = null;

    protected string $html = '';

    protected array $marker = [];

    protected string $backupHtml = '';

    protected ContentObjectRenderer $contentObjectRenderer;

    protected PageRepository $pageRepository;

    public function __construct(PageRepository $pageRepository, ContentObjectRenderer $contentObjectRenderer = null)
    {
        parent::__construct($contentObjectRenderer);
        $this->contentObjectRenderer = $contentObjectRenderer;
        $this->pageRepository = $pageRepository;
    }

    /**
     * @param null $actionName
     * @return string
     */
    public function render($actionName = null): string
    {
        $this->html = parent::render($actionName);

        $this->findMarkersInHtml();
        $this->inkyHtml();
        $this->makeAbsoluteUrls();
        $this->inlineCss();
        $this->cleanHeadTag();

        return $this->html;
    }

    public function renderWithMarkerOverrides($actionName = null, array $markerOverrides = [], Contact $contact = null): string
    {
        $this->html = parent::render($actionName);

        $this->findMarkersInHtml();
        $this->applyMarkerOverrides($markerOverrides);
        $this->insertContact($contact);
        $this->inkyHtml();
        $this->makeAbsoluteUrls();
        $this->inlineCss();
        $this->cleanHeadTag();

        return $this->html;
    }

    /**
     * Run the Zurb Foundation Parser for the html
     */
    public function inkyHtml(): void
    {
        $gridColumns = 12; //optional, default is 12
        $additionalComponentFactories = []; //optional
        $inky = new Inky($gridColumns, $additionalComponentFactories);

        try {
            $this->html = $inky->releaseTheKraken($this->html);
        } catch (CircularException $e) {
        }
    }

    /**
     * @TODO: refactor with SiteFinder
     */
    public function makeAbsoluteUrls(): void
    {
        if ($this->pid) {
            $rootline = BackendUtility::BEgetRootLine($this->pid);
            $host = BackendUtility::firstDomainRecord($rootline);
        }
        $host = isset($host) ? $host : GeneralUtility::getIndpEnv('TYPO3_SITE_URL');

        // image src paths
        $regex = '/(src=\")()(?=\/fileadmin|\/typo3conf|\/typo3temp|\/uploads)/';
        $this->html = preg_replace($regex, '$1' . $host, $this->html);
    }

    /**
     * Find all inline stylesheets and inline them
     */
    public function inlineCss(): void
    {
        preg_match_all('/(?<=href=")[^."]+\.css/', $this->html, $cssFiles);
        if ($cssFiles && count($cssFiles)) {
            $cssFiles = $cssFiles[0];
        }
        $css = '';
        foreach ($cssFiles as $cssFile) {
            $cssFilePath = GeneralUtility::getFileAbsFileName($cssFile);
            if (file_exists($cssFilePath)) {
                $css .= file_get_contents($cssFilePath);
            }
        }

        $cssToInlineStyles = new CssToInlineStyles();
        $this->html = $cssToInlineStyles->convert(
            $this->html,
            $css
        );

        // extract media queries and put them in the head
        $mediaBlocks = self::extractMediaQueries($css);
        $mediaBlock = '<style>' . implode(' ', $mediaBlocks) . '</style>';
        $this->html = preg_replace('/<\/head>/', $mediaBlock . '</head>', $this->html);
    }

    /**
     * @TODO: documentation
     */
    public static function extractMediaQueries(string $css): array
    {
        $mediaBlocks = [];

        $start = 0;
        while (($start = strpos($css, '@media', $start)) !== false) {
            // stack to manage brackets
            $stack = [];

            // get the first opening bracket
            $firstOpen = strpos($css, '{', $start);

            // if $i is false, then there is probably a css syntax error
            if ($firstOpen !== false) {
                // push bracket onto stack
                $stack[] = $css[$firstOpen];

                // move past first bracket
                $firstOpen++;

                while (!empty($stack)) {
                    // if the character is an opening bracket, push it onto the stack, otherwise pop the stack
                    if ($css[$firstOpen] === '{') {
                        $stack[] = '{';
                    } elseif ($css[$firstOpen] === '}') {
                        array_pop($stack);
                    }

                    $firstOpen++;
                }

                // cut the media block out of the css and store
                $mediaBlocks[] = substr($css, $start, ($firstOpen + 1) - $start);

                // set the new $start to the end of the block
                $start = $firstOpen;
            }
        }

        return $mediaBlocks;
    }

    /**
     * Remove all stylesheet tags from the head
     */
    public function cleanHeadTag()
    {
        $this->html = preg_replace(
            '/<link\b[^>]*?>/',
            '',
            $this->html
        );
    }

    /**
     * Reads the html and finds all markers
     */
    public function findMarkersInHtml(): void
    {
        preg_match_all('/(<!--\s+###)([\w\_]+)(###\s+-->)/', $this->html, $foundMarker);

        // abort if no marker were found
        if (!count($foundMarker[2])) {
            return;
        }

        // ensure that two markers were found
        $markerOccurrences = array_count_values($foundMarker[2]);
        $markerOccurrences = array_filter($markerOccurrences, static function ($occurrences) {
            return $occurrences === 2;
        });

        $markerNames = array_keys($markerOccurrences);

        foreach ($markerNames as $m) {
            preg_match(
                '/(<!--\s+###' . $m . '###\s+-->)([\s\S]*)(<!--\s+###' . $m . '###\s+-->)/',
                $this->html,
                $result
            );

            $this->marker[] = [
                'name' => $m,
                'content' => $result[2],
                'override' => '',
            ];
        }
    }

    /**
     * @return array
     */
    public function getMarker(): array
    {
        return $this->marker;
    }

    /**
     * @param array $markerOverrides
     */
    public function applyMarkerOverrides(array $markerOverrides)
    {
        foreach ($markerOverrides as $markerName => $overrideValue) {
            // abort if no override content
            if (!$overrideValue) {
                continue;
            }

            // find this->marker with name of $markerName
            $markerIndex = array_search($markerName, array_column($this->marker, 'name'));
            if ($markerIndex === false) {
                continue;
            }
            $this->marker[$markerIndex]['override'] = $overrideValue;

            // replace everything from marker start to marker end with override content
            $regex = '/<!--\s+###' . $markerName . '###\s+-->[\s\S]*<!--\s+###' . $markerName . '###\s+-->/';
            $this->html = preg_replace($regex, $overrideValue, $this->html);
        }
    }

    public function getHtml(): string
    {
        return $this->html;
    }

    /**
     * Return all backend internal links from the html
     */
    public function getInternalLinks(): array
    {
        // find all links
        $regex = '/(<a[^>]href=")(.[^"]*)/';
        preg_match_all($regex, $this->html, $links);

        // abort if no links were found
        if (!count($links)) {
            return [];
        }

        // remove links that are not internal
        $links = array_filter($links[2], function ($uri) {
            return strpos($uri, '/typo3/index.php?M=');
        });

        return $links;
    }

    public function setPid(int $pid): void
    {
        $this->pid = $pid;
    }

    /**
     * @param string $markerName
     * @param array $typoscript
     * @throws ServiceUnavailableException
     */
    public function addTyposcriptSelect(string $markerName, array $typoscript)
    {
        if (!$pid = $this->pid) {
            return;
        }

        // check pid if FE Context can be created (not possible if sys_folder) or go page level upwards
        $rootline = BackendUtility::BEgetRootLine($pid);
        for ($i = count($rootline); $i > 0; $i--) {
            $pid = $rootline[$i]['doktype'];
            if ($pid === 1) {
                break;
            }
        }

        $this->initTSFE($pid);

        if (isset($typoscript['render']) && $typoscript['render'] === '1') {
            $contentElements = $this->contentObjectRenderer->getContentObject('CONTENT')->render($typoscript);
        } else {
            $tableName = $this->contentObjectRenderer->stdWrapValue('table', $typoscript);
            // fix: to make typoScript query work, we need to enter a pid
            if (!isset($typoscript['select.']['pidInList'])) {
                $typoscript['select.']['pidInList'] = 'root,-1';
                $typoscript['select.']['recursive'] = '9';
            }
            $contentElements = $this->contentObjectRenderer->getRecords($tableName, $typoscript['select.']);
        }

        $this->assign($markerName, $contentElements);
    }

    /**
     * @param int $id
     * @param int $typeNum
     * @throws ServiceUnavailableException
     */
    protected function initTSFE($id = 1, $typeNum = 0)
    {
        if (!is_object($GLOBALS['TT'])) {
            $GLOBALS['TT'] = new TimeTracker(false);
            GeneralUtility::makeInstance(TimeTracker::class)->start();
        }

        $GLOBALS['TSFE'] = GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController',
            $GLOBALS['TYPO3_CONF_VARS'],
            $id,
            $typeNum
        );
        $GLOBALS['TSFE']->sys_page = $this->objectManager->get(PageRepository::class);
        $GLOBALS['TSFE']->sys_page->init(true);
        $GLOBALS['TSFE']->connectToDB();
        $GLOBALS['TSFE']->determineId();
        $GLOBALS['TSFE']->rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $id, '')->get();
        $GLOBALS['TSFE']->getConfigArray();

        // @TODO: check new condition for TYPO3 v9 url handling
        if (ExtensionManagementUtility::isLoaded('realurl')) {
            $rootline = BackendUtility::BEgetRootLine($id);
            $host = BackendUtility::firstDomainRecord($rootline);
            $_SERVER['HTTP_HOST'] = $host;
        }
    }

    public function insertContact(?Contact $contact): void
    {
        if (!$contact) {
            return;
        }

        $attributes = array_keys((array)$contact);
        foreach ($attributes as $attr) {
            $regex = '/\$' . $attr . '/';
            $this->html = preg_replace($regex, $contact->{$attr}, $this->html);
        }
    }
}

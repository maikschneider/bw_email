<?php

namespace Blueways\BwEmail\Utility;

use Hampe\Inky\Inky;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TemplateParserUtility
{

    /**
     * @var string
     */
    protected $html;

    /**
     * @var array
     */
    protected $marker;

    /**
     * TemplateParserUtility constructor.
     *
     * @param $html
     */
    public function __construct($html)
    {
        $this->html = $html;
        $this->marker = [];
    }

    /**
     * @return array
     */
    public function getMarker()
    {
        return $this->marker;
    }

    /**
     * Parses the html for marker (like ###HEADLINE###) and extracts the content
     */
    public function parseMarker()
    {
        preg_match_all('/(<!--\s+###)([\w\d]\w+)(###\s+-->)/', $this->html, $foundMarker);

        // abort if no marker were found
        if (!sizeof($foundMarker[2])) {
            return;
        }

        // ensure that two markers were found
        $markerOccurrences = array_count_values($foundMarker[2]);
        $markerOccurrences = array_filter($markerOccurrences, function ($occurrences) {
            return $occurrences === 2 ? true : false;
        });

        $markerNames = array_keys($markerOccurrences);

        foreach ($markerNames as $m) {
            preg_match(
                '/(<!--\s+###' . $m . '###\s+-->)([\s\S]*)(<!--\s+###' . $m . '###\s+-->)/',
                $this->html,
                $result
            );

            $this->marker[] = array(
                'name' => $m,
                'content' => $result[2]
            );
        }
    }

    /**
     * @return string
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * @param $overrides
     */
    public function overrideMarker($overrides)
    {
        // abort if no overrides
        if (!$overrides || !sizeof($overrides)) {
            return;
        }

        // checks that there are no overrides for marker that dont exist
        $validOverrides = array_intersect(array_column($this->marker, 'name'), array_keys($overrides));

        foreach ($validOverrides as $overrideName) {
            // abort if no override content
            if (!$overrides[$overrideName]) {
                continue;
            }

            // replace everything from marker start to marker end with override content
            $regex = '/<!--\s+###' . $overrideName . '###\s+-->[\s\S]*<!--\s+###' . $overrideName . '###\s+-->/';
            $this->html = preg_replace($regex, $overrides[$overrideName], $this->html);
        }
    }

    /**
     * @param \Blueways\BwEmail\Domain\Model\Contact
     */
    public function insertContact($contact)
    {
        $attributes = array_keys((array)$contact);
        foreach ($attributes as $attr) {
            $regex = '/\$' . $attr . '/';
            $this->html = preg_replace($regex, $contact->{$attr}, $this->html);
        }
    }

    /**
     * Return all backend internal links from the html
     *
     * @return array
     */
    public function getInternalLinks()
    {
        // find all links
        $regex = '/(<a[^>]href=")(.[^"]*)/';
        preg_match_all($regex, $this->html, $links);

        // abbort if no links were found
        if (!sizeof($links)) {
            return [];
        }

        // remove links that are not internal
        $links = array_filter($links[2], function ($uri) {
            return strpos($uri, '/typo3/index.php?M=');
        });

        return $links;
    }

    /**
     * Run the Zurb Foundation Parser for the html
     */
    public function inkyHtml()
    {
        $gridColumns = 12; //optional, default is 12
        $additionalComponentFactories = []; //optional
        $inky = new Inky($gridColumns, $additionalComponentFactories);

        try {
            $this->html = $inky->releaseTheKraken($this->html);
        } catch (\PHPHtmlParser\Exceptions\CircularException $e) {
        }
    }

    /**
     * Find all inline stylesheets and inline them
     */
    public function inlineCss()
    {
        preg_match_all('/(?<=href=")[^."]+\.css/', $this->html, $cssFiles);
        if ($cssFiles && sizeof($cssFiles)) {
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
    }

    /**
     * Cleans the output of the header tag
     */
    public function cleanHeadTag()
    {
        // remove stylesheet tags
        $this->html = preg_replace(
            '/<link\b[^>]*?>/',
            '',
            $this->html
        );
    }

}

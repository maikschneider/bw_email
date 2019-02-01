<?php

namespace Blueways\BwEmail\Utility;

use Hampe\Inky\Inky;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class TemplateParserUtility
 *
 * @package Blueways\BwEmail\Utility
 */
class TemplateParserUtility
{

    /**
     * @var string|null
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
    public function __construct($html = null)
    {
        $this->html = $html ?: null;
        $this->marker = null;
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
        // abort if no marker or overrides
        if (!$this->marker || !sizeof($this->marker) || !$overrides || !sizeof($overrides)) {
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
     * @deprecated
     * @return array
     */
    public function getInternalLinks()
    {
        // find all links
        $regex = '/(<a[^>]href=")(.[^"]*)/';
        preg_match_all($regex, $this->html, $links);

        // abort if no links were found
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

        // extract media queries and put them in the head
        $mediaBlocks = self::extractMediaQueries($css);
        $mediaBlock = '<style type="text/css">' . implode(' ', $mediaBlocks) . '</style>';
        $this->html = preg_replace('/<\/head>/', $mediaBlock . '</head>', $this->html);
    }

    /**
     * @param string $css
     * @return array
     */
    public static function extractMediaQueries($css)
    {
        $mediaBlocks = array();

        $start = 0;
        while (($start = strpos($css, "@media", $start)) !== false) {
            // stack to manage brackets
            $stack = array();

            // get the first opening bracket
            $firstOpen = strpos($css, "{", $start);

            // if $i is false, then there is probably a css syntax error
            if ($firstOpen !== false) {
                // push bracket onto stack
                array_push($stack, $css[$firstOpen]);

                // move past first bracket
                $firstOpen++;

                while (!empty($stack)) {
                    // if the character is an opening bracket, push it onto the stack, otherwise pop the stack
                    if ($css[$firstOpen] == "{") {
                        array_push($stack, "{");
                    } elseif ($css[$firstOpen] == "}") {
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

    /**
     * @param $host
     */
    public function makeAbsoluteUrls($host)
    {
        // image src paths
        $regex = '/(src=\")()(?=\/fileadmin|\/typo3conf|\/typo3temp|\/uploads)/';
        $this->html = preg_replace($regex, '$1' . $host, $this->html);
    }

    /**
     * @param $html
     */
    public function setHtml($html)
    {
        $this->html = $html;
    }

}

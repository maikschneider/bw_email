<?php

namespace Blueways\BwEmail\Utility;

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
     * Return all backend internal links from the html
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
}

<?php

namespace Blueways\BwEmail\Hooks;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class ContentPostProcessor
{

    public function render($_funcRef, $_params)
    {
        $cssToInlineStyles = new CssToInlineStyles();

        $css = '';

        $_params->content = $cssToInlineStyles->convert(
            $_params->content,
            $css
        );
    }
}

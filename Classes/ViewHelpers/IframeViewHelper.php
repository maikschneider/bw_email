<?php

namespace Blueways\BwEmail\ViewHelpers;

use Blueways\BwEmail\Controller\Ajax\EmailWizardController;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * Class IframeViewHelper
 *
 * @package Blueways\BwEmail\ViewHelpers
 */
class IframeViewHelper extends AbstractViewHelper
{

    use CompileWithContentArgumentAndRenderStatic;

    protected $escapeOutput = false;

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $html = $renderChildrenClosure();

        return 'data:text/html;charset=utf-8,' . EmailWizardController::encodeURIComponent($html);
    }

    public function initializeArguments()
    {
        $this->registerArgument('html', 'string', 'HTML content that should be encoded', false);
    }
}

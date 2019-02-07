<?php

namespace Blueways\BwEmail\Domain\Finishers;

use Blueways\BwEmail\View\EmailView;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\ViewHelpers\RenderRenderableViewHelper;

/**
 * Class EmailFinisher
 *
 * @package Blueways\BwEmail\Domain\Finishers
 */
class EmailFinisher extends \TYPO3\CMS\Form\Domain\Finishers\EmailFinisher
{

    const FORMAT_BWEMAIL = 'bwemail';

    /**
     * @param FormRuntime $formRuntime
     * @return StandaloneView
     * @throws \TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException
     */
    protected function initializeStandaloneView(FormRuntime $formRuntime): StandaloneView
    {
        $format = $this->parseOption('format');

        if ($format !== self::FORMAT_BWEMAIL) {
            parent::initializeStandaloneView($formRuntime);
        }

        $template = $this->parseOption('template');

        $standaloneView = $this->objectManager->get(EmailView::class);
        $standaloneView->setTemplate($template);

        $standaloneView->assign('finisherVariableProvider', $this->finisherContext->getFinisherVariableProvider());

        if (isset($this->options['variables'])) {
            $standaloneView->assignMultiple($this->options['variables']);
        }

        $standaloneView->assign('form', $formRuntime);
        $standaloneView->getRenderingContext()
            ->getViewHelperVariableContainer()
            ->addOrUpdate(RenderRenderableViewHelper::class, 'formRuntime', $formRuntime);

        return $standaloneView;
    }
}

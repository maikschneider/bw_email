<?php

namespace Blueways\BwEmail\Controller\Ajax;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Backend\Routing\UriBuilder;

/**
 * Class EmailWizardController
 *
 * @package Blueways\BwEmail\Controller\Ajax
 */
class ImapController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * @var array
     */
    protected $typoscript;

    /**
     * @var StandaloneView
     */
    private $templateView;

    /**
     * SendmailWizard constructor.
     *
     * @param \TYPO3\CMS\Fluid\View\StandaloneView|null $templateView
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function __construct(StandaloneView $templateView = null)
    {
        parent::__construct();

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $configurationManager = $this->objectManager->get(ConfigurationManager::class);
        $this->typoscript = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

        if (!$templateView) {
            /** @var StandaloneView $templateView */
            $templateView = $this->objectManager->get(StandaloneView::class);
            $templateView->setLayoutRootPaths($this->typoscript['plugin.']['tx_bwemail.']['view.']['layoutRootPaths.']);
            $templateView->setPartialRootPaths($this->typoscript['plugin.']['tx_bwemail.']['view.']['partialRootPaths.']);
            $templateView->setTemplateRootPaths($this->typoscript['plugin.']['tx_bwemail.']['view.']['templateRootPaths.']);
        }

        $this->templateView = $templateView;
        $this->uriBuilder = $this->objectManager->get(UriBuilder::class);
    }

    /**
     * @param \TYPO3\CMS\Core\Http\ServerRequest $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function inboxAction(\TYPO3\CMS\Core\Http\ServerRequest $request, ResponseInterface $response): \Psr\Http\Message\ResponseInterface
    {
        $content = json_encode(array(
            'message' => 'hello'
        ));

        $response->getBody()->write($content);

        return $response;
    }
}

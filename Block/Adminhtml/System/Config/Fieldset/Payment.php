<?php

declare(strict_types=1);

namespace Novalnet\Payment\Block\Adminhtml\System\Config\Fieldset;

use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\View\Helper\Js;
use Magento\Config\Model\Config;
use Magento\Framework\Math\Random;

/**
 * Novalnet payment fieldset block with Configure button
 */
class Payment extends Fieldset
{
    /**
     * @var Config
     */
    private $backendConfig;

    /**
     * @var object|null
     */
    private $secureRenderer = null;


    /**
     * @var Random
     */
    private $random;

    /**
     * Constructor
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        Config $backendConfig,
        Random $random,
        array $data = []
    ) {
        $this->backendConfig = $backendConfig;
        $this->random         = $random;

        // Load SecureHtmlRenderer only if available (Magento >= 2.4)
        if (class_exists(\Magento\Framework\View\Helper\SecureHtmlRenderer::class)) {

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $this->secureRenderer = $objectManager->create(\Magento\Framework\View\Helper\SecureHtmlRenderer::class);

        }
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    /**
    * Add custom css class
    *
    * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
    * @return string
    */
    protected function _getFrontendClass($element)
    {
        return parent::_getFrontendClass($element) . ' with-button';
    }



    /**
    * Return header title part of html for fieldset
    *
    * @param AbstractElement $element
    * @return string
    */
    protected function _getHeaderTitleHtml($element)
    {
        $html = '<div class="config-heading">';
        $htmlId = $element->getHtmlId();
        $html .= '<div class="button-container"><button type="button"'
            . ' class="button action-configure"'
            . ' id="' . $htmlId . '-head">'
            . '<span class="state-closed">' . __('Configure') . '</span>'
            . '<span class="state-opened">' . __('Close') . '</span>'
            . '</button>';

        $html .= $this->fallbackRenderEventListenerAsTag(
            'onclick',
            "novalnetToggleConfig.call(this, '"
                . $htmlId
                . "', '"
                . $this->getUrl('adminhtml/*/state')
                . "'); event.preventDefault();",
            'button#' . $htmlId . '-head'
        );

        $html .= '</div><div class="heading"><strong>'
            . $element->getLegend()
            . '</strong>';

        if ($element->getComment()) {
            $html .= '<div class="heading-intro">'
                . $element->getComment()
                . '</div>';
        }

        $html .= '<div class="config-alt"></div></div></div>';

        return $html;
    }
    /**
    * Return header comment part of html for fieldset
    *
    * @param AbstractElement $element
    * @return string
    * @SuppressWarnings(PHPMD.UnusedFormalParameter)
    */
    protected function _getHeaderCommentHtml($element)
    {
        return '';
    }


    /**
     * Collapsed or expanded fieldset when page loaded?
     *
     * @param AbstractElement $element
     * @return bool
     */
    protected function _isCollapseState($element)
    {
        return false;
    }

     /**
     * Return extra Js.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getExtraJs($element)
    {
        $script = "require(['jquery', 'prototype'], function(jQuery){
            window.novalnetToggleConfig = function (id, url) {
                var doScroll = false;
                Fieldset.toggleCollapse(id, url);
                if ($(this).hasClassName(\"open\")) {
                    \$$(\".with-button button.button\").each(function(anotherButton) {
                        if (anotherButton != this && $(anotherButton).hasClassName(\"open\")) {
                            $(anotherButton).click();
                            doScroll = true;
                        }
                    }.bind(this));
                }
                if (doScroll) {
                    var pos = Element.cumulativeOffset($(this));
                    window.scrollTo(pos[0], pos[1] - 45);
                }
            }
        });";

        return $this->_jsHelper->getScript($script);
    }


     /**
     * Compatibility-safe event listener renderer
     */
    private function fallbackRenderEventListenerAsTag( string $eventName, string $attributeJavascript, string $elementSelector) {

        if ($this->secureRenderer
            && method_exists($this->secureRenderer, 'renderEventListenerAsTag')
        ) {
            return $this->secureRenderer->renderEventListenerAsTag(
                $eventName,
                $attributeJavascript,
                $elementSelector
            );
        }

        if (!$eventName || !$attributeJavascript || !$elementSelector || mb_strpos($eventName, 'on') !== 0) {
            throw new \InvalidArgumentException('Invalid JS event handler data provided');
        }

        $random = $this->random->getRandomString(10);
        $listenerFunction = 'eventListener' . $random;
        $elementName = 'listenedElement' . $random;
        $script = <<<script
            function {$listenerFunction} () {
                {$attributeJavascript};
            }
            var {$elementName}Array = document.querySelectorAll("{$elementSelector}");
            if({$elementName}Array.length !== 'undefined'){
                {$elementName}Array.forEach(function(element) {
                    if (element) {
                        element.{$eventName} = function (event) {
                            var targetElement = element;
                            if (event && event.target) {
                                targetElement = event.target;
                            }
                            return {$listenerFunction}.apply(targetElement);
                        };
                    }
                });
            }
        script;

        return '<script type="text/javascript">' . $script . '</script>';
    }
}
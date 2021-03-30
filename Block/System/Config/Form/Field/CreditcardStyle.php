<?php
/**
 * Novalnet payment extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Novalnet End User License Agreement
 * that is bundled with this package in the file LICENSE.txt
 *
 * DISCLAIMER
 *
 * If you wish to customize Novalnet payment extension for your needs,
 * please contact technic@novalnet.de for more information.
 *
 * @category   Novalnet
 * @package    Novalnet_Payment
 * @copyright  Copyright (c) Novalnet AG
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 */
namespace Novalnet\Payment\Block\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Novalnet Creditcard style block
 */
class CreditcardStyle extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Novalnet Credit Card form default styles
     */
    public $ccLocalFormConfig = [
        'standard_style_label' => 'font-family: Raleway,Helvetica Neue,Verdana,Arial,sans-serif;font-size: 13px;font-weight: 600;color: #636363;line-height: 1.5;',
        'standard_style_input' => 'color: #636363;font-family: Helvetica Neue,Verdana,Arial,sans-serif;font-size: 14px;',
        'standard_style_css' => ''
    ];

    /**
     * Set template to itself
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('Novalnet_Payment::system/config/form/field/creditcardStyle.phtml');
        }
        return $this;
    }

    /**
     * Remove scope label
     *
     * @param  Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElements($element);
        return $this->_toHtml();
    }

    /**
     * Return element value
     *
     * @param  none
     * @return object
     */
    public function getElement()
    {
        return $this->getElements();
    }

    /**
     * Retrieve Novalnet Credit Card form style
     *
     * @param  string $param
     * @return string|null
     */
    public function getElementValue($param)
    {
        $values = $this->getElements()->getValue($param);
        if (!empty($values)) {
            return $values;
        } elseif (isset($this->ccLocalFormConfig[$param])) {
            return $this->ccLocalFormConfig[$param];
        }

        return '';
    }
}

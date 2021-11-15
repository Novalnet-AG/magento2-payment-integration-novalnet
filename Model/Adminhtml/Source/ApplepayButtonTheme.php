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
namespace Novalnet\Payment\Model\Adminhtml\Source;

/**
 * Source model for Credit card types options
 */
class ApplepayButtonTheme implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter (Applepay button themes)
     *
     * @param  none
     * @return array $options
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'apple-pay-button-black-with-text',
                'label' => __('Dark')
            ],
            [
                'value' => 'apple-pay-button-white-with-text',
                'label' => __('Light')
            ],
            [
                'value' => 'apple-pay-button-white-with-line-with-text',
                'label' => __('Light-Outline')
            ],
        ];
    }
}

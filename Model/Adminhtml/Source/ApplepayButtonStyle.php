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
class ApplepayButtonStyle implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter (Applepay button style)
     *
     * @param  none
     * @return array $options
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'apple-pay-button-text-plain',
                'label' => __('Default')
            ],
            [
                'value' => 'apple-pay-button-text-buy',
                'label' => __('Buy')
            ],
            [
                'value' => 'apple-pay-button-text-donate',
                'label' => __('Donate')
            ],
            [
                'value' => 'apple-pay-button-text-book',
                'label' => __('Book')
            ],
            [
                'value' => 'apple-pay-button-text-contribute',
                'label' => __('Contribute')
            ],
            [
                'value' => 'apple-pay-button-text-check-out',
                'label' => __('Check out')
            ],
            [
                'value' => 'apple-pay-button-text-order',
                'label' => __('Order')
            ],
            [
                'value' => 'apple-pay-button-text-subscribe',
                'label' => __('Subscribe')
            ],
            [
                'value' => 'apple-pay-button-text-tip',
                'label' => __('Tip')
            ],
            [
                'value' => 'apple-pay-button-text-rent',
                'label' => __('Rent')
            ],
            [
                'value' => 'apple-pay-button-text-reload',
                'label' => __('Reload')
            ],
            [
                'value' => 'apple-pay-button-text-support',
                'label' => __('Support')
            ],
        ];
    }
}

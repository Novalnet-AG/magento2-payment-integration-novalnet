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
     * @return array $options
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'plain',
                'label' => __('Default')
            ],
            [
                'value' => 'buy',
                'label' => __('Buy')
            ],
            [
                'value' => 'donate',
                'label' => __('Donate')
            ],
            [
                'value' => 'book',
                'label' => __('Book')
            ],
            [
                'value' => 'contribute',
                'label' => __('Contribute')
            ],
            [
                'value' => 'check-out',
                'label' => __('Check out')
            ],
            [
                'value' => 'order',
                'label' => __('Order')
            ],
            [
                'value' => 'subscribe',
                'label' => __('Subscribe')
            ],
            [
                'value' => 'tip',
                'label' => __('Tip')
            ],
            [
                'value' => 'rent',
                'label' => __('Rent')
            ],
            [
                'value' => 'reload',
                'label' => __('Reload')
            ],
            [
                'value' => 'support',
                'label' => __('Support')
            ],
        ];
    }
}

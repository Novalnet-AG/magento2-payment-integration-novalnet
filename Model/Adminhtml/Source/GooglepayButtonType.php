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

class GooglepayButtonType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter (GooglePay button types)
     *
     * @return array $options
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'book',
                'label' => __('Book')
            ],
            [
                'value' => 'buy',
                'label' => __('Buy')
            ],
            [
                'value' => 'checkout',
                'label' => __('Checkout')
            ],
            [
                'value' => 'donate',
                'label' => __('Donate')
            ],
            [
                'value' => 'order',
                'label' => __('Order')
            ],
            [
                'value' => 'pay',
                'label' => __('Pay')
            ],
            [
                'value' => 'plain',
                'label' => __('Plain')
            ],
            [
                'value' => 'subscribe',
                'label' => __('Subscribe')
            ]
        ];
    }
}

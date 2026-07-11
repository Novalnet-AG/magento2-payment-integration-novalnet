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
class GooglepayEnabledPages implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter (Googlepay Enabled Pages in the shop)
     *
     * @return array $options
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'shopping_cart_page',
                'label' => __('Shopping cart page')
            ],
            [
                'value' => 'mini_cart_page',
                'label' => __('Mini cart page')
            ],
            [
                'value' => 'product_page',
                'label' => __('Product page')
            ],
            [
                'value' => 'guest_checkout_page',
                'label' => __('Guest checkout page')
            ],
        ];
    }
}

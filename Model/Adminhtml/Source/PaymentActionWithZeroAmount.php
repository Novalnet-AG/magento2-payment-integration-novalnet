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

use Magento\Payment\Model\Method\AbstractMethod;
use Novalnet\Payment\Model\NNConfig;

class PaymentActionWithZeroAmount implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter (Payment Actions with zero amount booking)
     *
     * @param  none
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => AbstractMethod::ACTION_AUTHORIZE,
                'label' => __('Authorize')
            ],
            [
                'value' => AbstractMethod::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Capture')
            ],
            [
                'value' => NNConfig::ACTION_ZERO_AMOUNT_BOOKING,
                'label' => __('Authorize with zero amount')
            ]
        ];
    }
}

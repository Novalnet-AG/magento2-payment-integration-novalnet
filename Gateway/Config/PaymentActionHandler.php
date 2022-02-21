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
namespace Novalnet\Payment\Gateway\Config;

use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Payment\Model\Method\AbstractMethod;

class PaymentActionHandler implements ValueHandlerInterface
{
    /**
     * @var \Novalnet\Payment\Model\NNConfig
     */
    private $novalnetConfig;
    
    /**
     * @var \Novalnet\Payment\Helper\Request
     */
    private $novalnetRequestHelper;

    /**
     * @param \Novalnet\Payment\Model\NNConfig $novalnetConfig
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     */
    public function __construct(
        \Novalnet\Payment\Model\NNConfig $novalnetConfig,
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper
    ) {
        $this->novalnetConfig = $novalnetConfig;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
    }

    /**
     * Validate minimum limit for authorization
     *
     * @param array $subject
     * @param int|null $storeId
     * @return bool
     */
    public function handle(array $subject, $storeId = null)
    {
        $paymentDataObject = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($subject);
        $orderTotalAmount = $paymentDataObject->getOrder()->getGrandTotalAmount();
        $paymentMethodCode = $paymentDataObject->getPayment()->getMethodInstance()->getCode();
        $paymentAction = $this->novalnetConfig->getPaymentConfig($paymentMethodCode, 'payment_action');
        $minAuthAmount = $this->novalnetConfig->getPaymentConfig($paymentMethodCode, 'manual_checking_amount');

        if ($paymentAction == AbstractMethod::ACTION_AUTHORIZE &&
            (string) $this->novalnetRequestHelper->getFormattedAmount($orderTotalAmount) >= (string) $minAuthAmount
        ) {
            return AbstractMethod::ACTION_AUTHORIZE;
        } else {
            return AbstractMethod::ACTION_AUTHORIZE_CAPTURE;
        }
    }
}

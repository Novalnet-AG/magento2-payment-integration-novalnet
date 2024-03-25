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
use \Novalnet\Payment\Model\NNConfig;

class PaymentActionHandler implements ValueHandlerInterface
{
    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    private $novalnetHelper;

    /**
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     */
    public function __construct(
        \Novalnet\Payment\Helper\Data $novalnetHelper
    ) {
        $this->novalnetHelper = $novalnetHelper;
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
        $paymentMethodCode = $paymentDataObject->getPayment()->getMethodInstance()->getCode();
        $methodSession = $this->novalnetHelper->getMethodSession($paymentMethodCode);
        $paymentAction = $methodSession->getData($paymentMethodCode . '_payment_action');
        $paymentType = $methodSession->getData($paymentMethodCode . '_type');

        if ($paymentAction == NNConfig::ACTION_AUTHORIZE || $paymentAction == NNConfig::ACTION_ZERO_AMOUNT || in_array($paymentType, ['PREPAYMENT', 'CASHPAYMENT', 'MULTIBANCO'])) {
            return AbstractMethod::ACTION_AUTHORIZE;
        } else {
            return AbstractMethod::ACTION_AUTHORIZE_CAPTURE;
        }
    }
}

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
namespace Novalnet\Payment\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Novalnet\Payment\Model\Ui\ConfigProvider;

class CaptureDataBuilder extends AbstractDataBuilder
{
    /**
     * Builds ENV request for Capture action
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDataObject = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $paymentMethodCode = $payment->getMethodInstance()->getCode();
        $additionalData = [];
        if (!empty($payment->getAdditionalData())) {
            $additionalData = $this->novalnetHelper->isSerialized($payment->getAdditionalData())
                ? $this->serializer->unserialize($payment->getAdditionalData())
                : json_decode($payment->getAdditionalData(), true);
        }
        $order = $payment->getOrder();
        $paymentType = !empty($additionalData['NnPaymentType']) ? $additionalData['NnPaymentType'] : '';
        $transactionStatus = !empty($additionalData['NnStatus'])
            ? $this->novalnetHelper->getStatus($additionalData['NnStatus'], $order, $paymentType) : '';

        if ($transactionStatus == 'ON_HOLD' && !empty($this->urlInterface->getCurrentUrl()) &&
            !preg_match('/callback/i', $this->urlInterface->getCurrentUrl())
        ) {
            return parent::buildExtensionParams($buildSubject);
        } elseif (!empty($this->urlInterface->getCurrentUrl()) && preg_match('/callback/i', $this->urlInterface->getCurrentUrl())) {
            return ['action' => 'NN_Capture', 'Async' => 'callback'];
        } elseif (!empty($additionalData['NnZeroAmountCapture'])) {
            return ['action' => 'NN_ZeroCapture'];
        } elseif (!empty($additionalData['NnTxnSecret']) ||
            (!empty($this->urlInterface->getCurrentUrl()) && preg_match('/callback/i', $this->urlInterface->getCurrentUrl())) ||
            (in_array(
                $paymentType,
                [
                    'GUARANTEED_DIRECT_DEBIT_SEPA',
                    'GUARANTEED_INVOICE',
                    'INSTALMENT_DIRECT_DEBIT_SEPA',
                    'INSTALMENT_INVOICE'
                ]
            ) && $transactionStatus == 'CONFIRMED')
        ) {
            return ['action' => 'NN_Capture'];
        }

        return parent::build($buildSubject);
    }
}

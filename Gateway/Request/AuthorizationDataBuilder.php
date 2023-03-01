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

class AuthorizationDataBuilder extends AbstractDataBuilder
{
    /**
     * Builds ENV request for Authorize action
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDataObject = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $additionalData = $this->novalnetHelper->getPaymentAdditionalData($payment->getAdditionalData());
        $paymentType = !empty($additionalData['NnPaymentType']) ? $additionalData['NnPaymentType'] : '';

        if (!empty($additionalData['NnTxnSecret']) ||
            in_array(
                $paymentType,
                [
                    'GUARANTEED_DIRECT_DEBIT_SEPA',
                    'GUARANTEED_INVOICE',
                    'INSTALMENT_DIRECT_DEBIT_SEPA',
                    'INSTALMENT_INVOICE'
                ]
            )
        ) {
            return ['action' => 'NN_Authorize'];
        }

        return parent::build($buildSubject);
    }
}

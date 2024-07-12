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

use Novalnet\Payment\Model\Ui\ConfigProvider;

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
        $paymentMethodCode = $payment->getMethodInstance()->getCode();
        $additionalData = [];
        if (!empty($payment->getAdditionalData())) {
            $additionalData = $this->novalnetRequestHelper->isSerialized($payment->getAdditionalData())
                ? $this->serializer->unserialize($payment->getAdditionalData())
                : json_decode($payment->getAdditionalData(), true);
        }

        if (!empty($additionalData['NnTxnSecret']) ||
            in_array(
                $paymentMethodCode,
                [
                    ConfigProvider::NOVALNET_SEPA_GUARANTEE,
                    ConfigProvider::NOVALNET_INVOICE_GUARANTEE,
                    ConfigProvider::NOVALNET_SEPA_INSTALMENT,
                    ConfigProvider::NOVALNET_INVOICE_INSTALMENT
                ]
            )
        ) {
            return ['action' => 'NN_Authorize'];
        }

        return parent::build($buildSubject);
    }
}

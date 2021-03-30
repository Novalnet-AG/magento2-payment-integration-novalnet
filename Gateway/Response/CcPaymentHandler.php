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
namespace Novalnet\Payment\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

class CcPaymentHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $datetime;

    /**
     * @var \Novalnet\Payment\Helper\Request
     */
    private $novalnetRequestHelper;
    
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    private $serializer;

    /**
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer
    ) {
        $this->dateTime = $datetime;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->jsonHelper = $jsonHelper;
        $this->serializer = $serializer;
    }

    /**
     * Handles transaction authorize and capture for Credit Card payment
     *
     * @param array $handlingSubject
     * @param array $paymentResponse
     * @return void
     */
    public function handle(array $handlingSubject, array $paymentResponse)
    {
        $response = new \Magento\Framework\DataObject();
        $response->setData($paymentResponse);
        $paymentDataObject = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($handlingSubject);
        $order = $paymentDataObject->getOrder();
        $payment = $paymentDataObject->getPayment();
        $paymentMethodCode = $payment->getMethodInstance()->getCode();
        $additionalData = $payment->getAdditionalData()
            ? ($this->novalnetRequestHelper->isSerialized($payment->getAdditionalData())
            ? $this->serializer->unserialize($payment->getAdditionalData())
            : json_decode($payment->getAdditionalData(), true)) : [];
        $transactionStatus = !empty($additionalData['NnStatus'])
            ? $this->novalnetRequestHelper->getStatus($additionalData['NnStatus'], $order) : '';

        if (in_array($response->getData('action'), ['NN_Authorize', 'NN_Capture']) &&
            isset($additionalData['NnTid'])
        ) {
            // Authorize or Capture CC3d initial transaction
            $payment->setTransactionId($additionalData['NnTid'])
                ->setAmount($order->getGrandTotalAmount())
                ->setIsTransactionClosed(false)
                ->setShouldCloseParentTransaction(false);

            if ($transactionStatus == 'ON_HOLD') {
                $payment->setTransactionId($additionalData['NnTid'] . '-capture');
            }
        } elseif (!empty($response->getData())) {
            if ($response->getData('result/redirect_url')) {
                $payment->setAdditionalData(
                    $this->jsonHelper->jsonEncode($this->novalnetRequestHelper->buildRedirectAdditionalData($response))
                );
            } else {
                if ($transactionStatus == 'ON_HOLD') {
                    $additionalData['ApiProcess'] = 'capture';
                    $additionalData['ApiProcessedAt'] = $this->dateTime->date('d-m-Y H:i:s');
                    $additionalData['NnStatus'] = $response->getData('transaction/status');
                    // Capture the Authorized transaction
                    $payment->setTransactionId($response->getData('transaction/tid') . '-capture')
                        ->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))
                        ->setIsTransactionClosed(false)
                        ->setShouldCloseParentTransaction(false);
                } else {
                    // Authorize or Capture the CC transaction
                    $payment->setTransactionId($response->getData('transaction/tid'))
                        ->setAmount($order->getGrandTotalAmount())
                        ->setAdditionalData(
                            $this->jsonHelper->jsonEncode(
                                $this->novalnetRequestHelper->buildAdditionalData($response, $payment)
                            )
                        )
                        ->setIsTransactionClosed(false)
                        ->setShouldCloseParentTransaction(false);

                    // Store payment data if token exist
                    if ($response->getData('result/status') == 'SUCCESS' && $response->getData('transaction/payment_data/token')) {
                        $this->novalnetRequestHelper->savePaymentToken($order, $paymentMethodCode, $response);
                    }
                }
            }
        }
    }
}

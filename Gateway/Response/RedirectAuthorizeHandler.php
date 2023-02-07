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

class RedirectAuthorizeHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    private $novalnetHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    private $serializer;

    /**
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     */
    public function __construct(
        \Novalnet\Payment\Helper\Data $novalnetHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer
    ) {
        $this->novalnetHelper = $novalnetHelper;
        $this->serializer = $serializer;
    }

    /**
     * Handles transaction authorize for Redirect payments
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDataObject = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($handlingSubject);
        $order = $paymentDataObject->getOrder();
        $payment = $paymentDataObject->getPayment();
        $additionalData = [];
        if (!empty($payment->getAdditionalData())) {
            $additionalData = $this->novalnetHelper->isSerialized($payment->getAdditionalData())
                    ? $this->serializer->unserialize($payment->getAdditionalData())
                    : json_decode($payment->getAdditionalData(), true);
        }

        // Authorize initial transaction
        $payment->setTransactionId($additionalData['NnTid'])
                ->setAmount($order->getGrandTotalAmount())
                ->setIsTransactionClosed(false)
                ->setShouldCloseParentTransaction(false);
    }
}

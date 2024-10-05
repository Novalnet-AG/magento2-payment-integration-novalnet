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

class RedirectAuthorizeHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
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
        $additionalData = $this->novalnetHelper->getPaymentAdditionalData($payment->getAdditionalData());

        // Authorize initial transaction
        $payment->setTransactionId($additionalData['NnTid'])
                ->setAmount($order->getGrandTotalAmount())
                ->setIsTransactionClosed(false)
                ->setShouldCloseParentTransaction(false);
    }
}

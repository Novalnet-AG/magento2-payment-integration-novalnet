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

class RefundHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    private $pricingHelper;

    /**
     * @var \Novalnet\Payment\Model\TransactionStatus
     */
    private $transactionStatusModel;

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
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     */
    public function __construct(
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel,
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer
    ) {
        $this->pricingHelper = $pricingHelper;
        $this->transactionStatusModel = $transactionStatusModel;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->jsonHelper = $jsonHelper;
        $this->serializer = $serializer;
    }

    /**
     * Handles transaction refund
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
        $refundAmount = \Magento\Payment\Gateway\Helper\SubjectReader::readAmount($handlingSubject);

        $payment = $paymentDataObject->getPayment();
        $lastTransid = $payment->getLastTransId();
        $additionalData = $this->novalnetRequestHelper->isSerialized($payment->getAdditionalData())
                ? $this->serializer->unserialize($payment->getAdditionalData())
                : json_decode($payment->getAdditionalData(), true);
        $parentTid = $additionalData['NnTid'];
        $additionalData['NnStatus'] = $response->getData('transaction/status');
        $noOfRefunds = empty($additionalData['NnRefundsOccured']) ? 1 : $additionalData['NnRefundsOccured'] + 1;
        $additionalData['NnRefundsOccured'] = $noOfRefunds;

        $refundTid = $response->getData('transaction/refund/tid') ? $response->getData('transaction/refund/tid') : (
            (strpos($lastTransid, '-refund') !== false) ?
            $this->novalnetRequestHelper->makeValidNumber($lastTransid) . '-refund' . $noOfRefunds
            : $lastTransid . '-refund');

        $refundAmount = $this->pricingHelper->currency($refundAmount, true, false);
        $additionalData['NnRefunded'][$refundTid]['reftid'] = $refundTid;
        $additionalData['NnRefunded'][$refundTid]['refamount'] = $refundAmount;
        $additionalData['NnRefunded'][$refundTid]['reqtid'] = $parentTid;

        $this->transactionStatusModel->loadByAttribute($response->getData('transaction/order_no'), 'order_id')
            ->setStatus($response->getData('transaction/status'))->save();
        $payment->setTransactionId($refundTid . '-refund')
                ->setLastTransId($additionalData['NnTid'])
                ->setParentTransactionId($parentTid)
                ->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))
                ->setIsTransactionClosed(true)
                ->setShouldCloseParentTransaction(!$payment->getCreditmemo()->getInvoice()->canRefund());
    }
}

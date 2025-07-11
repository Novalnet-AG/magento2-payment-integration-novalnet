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
     * @var \Novalnet\Payment\Helper\Data
     */
    private $novalnetHelper;

    /**
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     */
    public function __construct(
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel,
        \Novalnet\Payment\Helper\Data $novalnetHelper
    ) {
        $this->pricingHelper = $pricingHelper;
        $this->transactionStatusModel = $transactionStatusModel;
        $this->novalnetHelper = $novalnetHelper;
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
        $additionalData = $this->novalnetHelper->getPaymentAdditionalData($payment->getAdditionalData());
        $parentTid = $additionalData['NnTid'];
        $lastTransid = !empty($payment->getLastTransId()) ? $payment->getLastTransId() : $additionalData['NnTid'];
        if (!empty($additionalData['NnZeroAmountBooking']) && !empty($additionalData['NnZeroAmountDone'])) {
            $parentTid = $additionalData['NnZeroAmountRefTid'];
            $lastTransid = !empty($payment->getLastTransId()) ? $payment->getLastTransId() : $additionalData['NnZeroAmountRefTid'];
            $lastTransid = (strpos($lastTransid, '-zeroamount') !== false) ? $this->novalnetHelper->makeValidNumber($lastTransid) : $lastTransid;
        }

        $additionalData['NnStatus'] = $response->getData('transaction/status');
        $noOfRefunds = empty($additionalData['NnRefundsOccured']) ? 1 : $additionalData['NnRefundsOccured'] + 1;
        $additionalData['NnRefundsOccured'] = $noOfRefunds;

        $refundTid = $response->getData('transaction/refund/tid') ? $response->getData('transaction/refund/tid') : (
            (strpos($lastTransid, '-refund') !== false) ?
            $this->novalnetHelper->makeValidNumber($lastTransid) . '-refund' . $noOfRefunds
            : $lastTransid . '-refund');

        $refundAmount = $this->pricingHelper->currency($refundAmount, true, false);

        $additionalData['NnRefunded'][$refundTid] = [
            'reftid' => $refundTid,
            'refamount' => $refundAmount,
            'reqtid' => $parentTid
        ];

        $this->transactionStatusModel->loadByAttribute($response->getData('transaction/order_no'), 'order_id')
            ->setStatus($response->getData('transaction/status'))->save();
        $payment->setTransactionId($refundTid)
                ->setLastTransId($refundTid)
                ->setParentTransactionId($parentTid)
                ->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))
                ->setIsTransactionClosed(true)
                ->setShouldCloseParentTransaction(!$payment->getCreditmemo()->getInvoice()->canRefund());
    }
}

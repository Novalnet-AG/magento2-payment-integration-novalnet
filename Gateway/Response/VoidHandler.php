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

class VoidHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var \Novalnet\Payment\Model\TransactionStatus
     */
    private $transactionStatusModel;

    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    private $novalnetHelper;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    private $serializer;

    /**
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel,
        \Novalnet\Payment\Helper\Data $novalnetHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer
    ) {
        $this->dateTime = $dateTime;
        $this->transactionStatusModel = $transactionStatusModel;
        $this->novalnetHelper = $novalnetHelper;
        $this->jsonHelper = $jsonHelper;
        $this->serializer = $serializer;
    }

    /**
     * Handles transaction void
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
        $payment = $paymentDataObject->getPayment();
        $additionalData = [];
        if (!empty($payment->getAdditionalData())) {
            $additionalData = $this->novalnetHelper->isSerialized($payment->getAdditionalData())
                    ? $this->serializer->unserialize($payment->getAdditionalData())
                    : json_decode($payment->getAdditionalData(), true);
        }
        $additionalData['ApiProcess'] = 'void';
        $additionalData['ApiProcessedAt'] = $this->dateTime->date('d-m-Y H:i:s');
        $additionalData['NnStatus'] = $response->getData('transaction/status');
        $additionalData['NnComments'] = '<br><b><font color="red">' . __('Payment Failed') . '</font> - '
            . $response->getData('transaction/status') . '</b>';
        $this->transactionStatusModel->loadByAttribute($response->getData('transaction/order_no'), 'order_id')
            ->setStatus($response->getData('transaction/status'))->save();

        $payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))
            ->setIsTransactionClosed(true)
            ->setShouldCloseParentTransaction(true);
    }
}

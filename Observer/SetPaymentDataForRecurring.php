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
namespace Novalnet\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Novalnet\Payment\Model\Ui\ConfigProvider;

class SetPaymentDataForRecurring implements ObserverInterface
{
    /**
     * @var \Novalnet\Payment\Model\NNConfig
     */
    protected $novalnetConfig;

    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    protected $novalnetHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $salesOrder;

    /**
     * @var \Novalnet\Payment\Model\TransactionStatus
     */
    protected $transactionStatusModel;

    /**
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     * @param \Novalnet\Payment\Model\NNConfig $novalnetConfig
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Sales\Model\Order $salesOrder
     * @param \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel
     */
    public function __construct(
        \Novalnet\Payment\Helper\Data $novalnetHelper,
        \Novalnet\Payment\Model\NNConfig $novalnetConfig,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Sales\Model\Order $salesOrder,
        \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel
    ) {
        $this->novalnetHelper = $novalnetHelper;
        $this->novalnetConfig = $novalnetConfig;
        $this->date = $date;
        $this->salesOrder = $salesOrder;
        $this->transactionStatusModel = $transactionStatusModel;
    }

    /**
     * Save payment data for future recurring order
     *
     * @param  \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $paymentCode = $observer->getPaymentCode();
        if (!empty($paymentCode) && preg_match('/novalnet/i', $paymentCode)) {
            $profile = $observer->getProfile();
            $paymentData = $observer->getPaymentData()->getData();
            $paymentDataObject = $observer->getPaymentData();
            $additionalData = $this->novalnetHelper->jsonDecode($profile->getAdditionalData());
            $paymentType = $title = $processMode = '';
            $paymentAdditionalData = [];

            if ($paymentCode != ConfigProvider::NOVALNET_PAY) {
                $paymentType = $this->novalnetConfig->getPaymentTypeByCode($paymentCode);
                $title = $this->novalnetConfig->getPaymentTitleByCode($paymentCode);
                $processMode = ($this->novalnetConfig->isRedirectPayment($paymentCode)) ? 'redirect' : 'direct';
            } elseif (!empty($profile->getOrderId())) {
                $payment = $this->salesOrder->loadByIncrementId($profile->getOrderId())->getPayment();
                $paymentAdditionalData = $this->novalnetHelper->getPaymentAdditionalData($payment->getAdditionalData());
                $paymentType = !empty($paymentAdditionalData['NnPaymentType']) ? $paymentAdditionalData['NnPaymentType'] : '';
                $title = !empty($paymentAdditionalData['NnPaymentTitle']) ? $paymentAdditionalData['NnPaymentTitle'] : '';
                $processMode = !empty($paymentAdditionalData['NnPaymentProcessMode']) ? $paymentAdditionalData['NnPaymentProcessMode'] : '';
            }

            $testMode = !empty($paymentAdditionalData['NnTestMode']) ? $paymentAdditionalData['NnTestMode'] : 0;

            $paymentData['additional_data'][ConfigProvider::NOVALNET_PAY . '_payment_data'] = $this->novalnetHelper->jsonEncode([
                'recurring_details' => [
                    'type' => $paymentType,
                    'birth_date' => isset($additionalData['dob']) ? $additionalData['dob'] : '',
                    'token' => $profile->getToken(),
                    'test_mode' => $testMode,
                    'name' => $title,
                    'process_mode' => $processMode
                ]
            ]);

            $paymentDataObject->setData($paymentData);
        }
    }
}

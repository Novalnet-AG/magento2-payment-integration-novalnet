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

class SavePaymentDataForRecurring implements ObserverInterface
{
    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    protected $novalnetHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Novalnet\Payment\Model\TransactionStatus
     */
    protected $transactionStatusModel;

    /**
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel
     */
    public function __construct(
        \Novalnet\Payment\Helper\Data $novalnetHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel
    ) {
        $this->date = $date;
        $this->novalnetHelper = $novalnetHelper;
        $this->transactionStatusModel = $transactionStatusModel;
    }

    /**
     * Save payment data for future recurring order
     *
     * @param  \Magento\Framework\Event\Observer $observer
     * @return none
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getOrder();
        $paymentMethodCode = $observer->getPaymentCode();
        if (preg_match('/novalnet/', $paymentMethodCode)) {
            $token = $this->getToken($paymentMethodCode, $order->getIncrementId());
            $profileData = $observer->getItem();
            if (!empty($token)) {
                $profileData->setToken($token);
            }

            $methodSession = $this->novalnetHelper->getMethodSession($paymentMethodCode);
            $recurringData = [];
            if ($methodSession->getData($paymentMethodCode . '_birth_date')) {
                $recurringData['dob'] = $this->date->date(
                    'Y-m-d',
                    $methodSession->getData($paymentMethodCode . '_birth_date')
                );
            }
            $profileData->setAdditionalData($this->novalnetHelper->jsonEncode($recurringData));
            $this->novalnetHelper->getMethodSession($paymentMethodCode, true);
        }
    }

    /**
     * Get Token
     *
     * @param string $paymentMethodCode
     * @param int $orderId
     * @return string
     */
    public function getToken($paymentMethodCode, $orderId)
    {
        $methodSession = $this->novalnetHelper->getMethodSession($paymentMethodCode);
        $token = $methodSession->getData($paymentMethodCode . '_token');
        if (empty($token)) {
            $transactionStatus = $this->transactionStatusModel->getCollection()->setPageSize(1)
                ->addFieldToFilter('order_id', $orderId)
                ->getFirstItem();
            $token = $transactionStatus->getToken();
        }
        return $token;
    }
}

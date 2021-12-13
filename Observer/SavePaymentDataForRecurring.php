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

class SavePaymentDataForRecurring implements ObserverInterface
{
    /**
     * @var \Novalnet\Payment\Helper\Request
     */
    protected $novalnetRequestHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Novalnet\Payment\Model\TransactionStatus
     */
    protected $transactionStatusModel;

    /**
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel
     */
    public function __construct(
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel
    ) {
        $this->date = $date;
        $this->jsonHelper = $jsonHelper;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
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

            //Save date of birth and company for future recurring
            if (in_array($paymentMethodCode, [ConfigProvider::NOVALNET_INVOICE_GUARANTEE, ConfigProvider::NOVALNET_SEPA_GUARANTEE])) {
                $methodSession = $this->novalnetRequestHelper->getMethodSession($paymentMethodCode);
                $recurringData = [];
                if ($methodSession->getData($paymentMethodCode . '_dob')) {
                    $recurringData['dob'] = $this->date->date(
                        'Y-m-d',
                        $methodSession->getData($paymentMethodCode . '_dob')
                    );
                }
                $profileData->setAdditionalData($this->jsonHelper->jsonEncode($recurringData));
            }
            // unset session data after request formation
            $this->novalnetRequestHelper->getMethodSession($paymentMethodCode, true);
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
        $methodSession = $this->novalnetRequestHelper->getMethodSession($paymentMethodCode);
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

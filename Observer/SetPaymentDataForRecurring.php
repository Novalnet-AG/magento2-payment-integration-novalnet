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
     * @var \Novalnet\Payment\Helper\Request
     */
    protected $novalnetRequestHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Novalnet\Payment\Model\TransactionStatus
     */
    protected $transactionStatusModel;

    /**
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel
     */
    public function __construct(
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel
    ) {
        $this->date = $date;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
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
        $paymentData = $observer->getPaymentData()->getData();
        $paymentDataObject = $observer->getPaymentData();
        $profile = $observer->getProfile();
        $paymentCode = $observer->getPaymentCode();
        if (in_array($paymentCode, [ConfigProvider::NOVALNET_CC, ConfigProvider::NOVALNET_SEPA ,ConfigProvider::NOVALNET_PAYPAL])) {
            $recurringDetails = $profile->getToken();

            if (!empty($profile->getToken())) {
                $paymentData['additional_data'][$paymentCode.'_token'] = $profile->getToken();
            }
        } elseif (in_array($paymentCode, [ConfigProvider::NOVALNET_INVOICE_GUARANTEE, ConfigProvider::NOVALNET_SEPA_GUARANTEE])) {
            if (!empty($profile->getAdditionalData())) {
                $additionalData = json_decode($profile->getAdditionalData(), true);
                if (isset($additionalData['dob'])) {
                    $paymentData['additional_data'][$paymentCode.'_dob'] = $additionalData['dob'];
                }
                if (!empty($profile->getToken())) {
                    $paymentData['additional_data'][$paymentCode.'_token'] = $profile->getToken();
                }
            }
        }
        $paymentDataObject->setData($paymentData);
    }
}

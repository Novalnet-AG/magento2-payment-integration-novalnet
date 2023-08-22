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

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Novalnet\Payment\Model\Ui\ConfigProvider;

class DataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    protected $novalnetHelper;

    /**
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     */
    public function __construct(
        \Novalnet\Payment\Helper\Data $novalnetHelper
    ) {
        $this->novalnetHelper = $novalnetHelper;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $paymentMethodCode = $this->readMethodArgument($observer)->getCode();
        if ($paymentMethodCode == ConfigProvider::NOVALNET_PAY) {
            $data = $this->readDataArgument($observer)->getAdditionalData();
            $paymentData = !empty($data[ConfigProvider::NOVALNET_PAY . '_payment_data']) ? $data[ConfigProvider::NOVALNET_PAY . '_payment_data'] : '{}';
            $additionalData = ($this->novalnetHelper->isJSON($paymentData)) ? $this->novalnetHelper->jsonDecode($paymentData) : $paymentData;
            $this->novalnetHelper->getMethodSession($paymentMethodCode, true);
            $methodSession = $this->novalnetHelper->getMethodSession($paymentMethodCode);

            if (!empty($additionalData['payment_details'])) {
                foreach ($additionalData['payment_details'] as $key => $value) {
                    $methodSession->setData(
                        $paymentMethodCode . '_' . $key,
                        $value
                    );
                }
            }

            if (!empty($additionalData['booking_details'])) {
                foreach ($additionalData['booking_details'] as $key => $value) {
                    $methodSession->setData(
                        $paymentMethodCode . '_' . $key,
                        $value
                    );
                }
            }
        }
    }
}

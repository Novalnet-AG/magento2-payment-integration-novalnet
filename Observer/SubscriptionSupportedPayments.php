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
use Magento\Sales\Model\Order;

class SubscriptionSupportedPayments implements ObserverInterface
{
    /**
     * @var \Novalnet\Payment\Model\NNConfig
     */
    protected $novalnetConfig;

    /**
     * @param \Novalnet\Payment\Model\NNConfig $novalnetConfig
     */
    public function __construct(
        \Novalnet\Payment\Model\NNConfig $novalnetConfig
    ) {
        $this->novalnetConfig = $novalnetConfig;
    }

    /**
     * Set Subscription Supported Novalnet payments
     *
     * @param  \Magento\Framework\Event\Observer $observer
     * @return none
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $paymentMethods = $observer->getPaymentMethods();
        if (!empty($paymentMethods)) {
            $payments = $paymentMethods->getData();
            foreach ($payments as $paymentCode => $payment) {
                if (!empty($paymentCode) && preg_match('/novalnet/i', $paymentCode) && !$this->novalnetConfig->isSubscriptionSupported($paymentCode)) {
                    unset($payments[$paymentCode]);
                }
            }
            $paymentMethods->setData($payments);
        }
    }
}

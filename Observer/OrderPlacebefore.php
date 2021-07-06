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

class OrderPlacebefore implements ObserverInterface
{
    /**
     * @var \Novalnet\Payment\Helper\Request
     */
    protected $novalnetRequestHelper;

    /**
     * @var \Novalnet\Payment\Logger\NovalnetLogger
     */
    private $novalnetLogger;

    /**
     * @param Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
     */
    public function __construct(
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
    ) {
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->novalnetLogger = $novalnetLogger;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $paymentMethodCode = $observer->getOrder()->getPayment()->getMethod();
        if (in_array(
            $paymentMethodCode,
            [
                ConfigProvider::NOVALNET_INVOICE_GUARANTEE,
                ConfigProvider::NOVALNET_SEPA_GUARANTEE
             ]
        ) && $this->novalnetRequestHelper->isAdmin()) {
            $methodSession = $this->novalnetRequestHelper->getMethodSession($paymentMethodCode);
            $forcedPayment = $methodSession->getData($paymentMethodCode.'_force_payment');
            if ($methodSession->getData($paymentMethodCode.'_force') && $forcedPayment) {
                //set payment methode to proceed guarantee force
                $observer->getOrder()->getPayment()->setMethod($forcedPayment);
                $this->novalnetLogger->notice("Update payment method from $paymentMethodCode to $forcedPayment");
            }
        }
    }
}

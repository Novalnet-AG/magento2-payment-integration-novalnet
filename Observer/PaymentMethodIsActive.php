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
use Magento\Framework\Event\ObserverInterface;

class PaymentMethodIsActive implements ObserverInterface
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
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $methodInstance = $event->getData('method_instance');
        $quote = $event->getData('quote');
        $result = $event->getData('result');
        $paymentMethodCode = $methodInstance->getCode();

        if ($quote && $result->getData('is_available') && strpos($paymentMethodCode, 'novalnet') === 0) {
            $result->setData('is_available', $this->novalnetHelper->validateBasicParams());
        }
    }
}

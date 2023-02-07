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
namespace Novalnet\Payment\Block\Onepage\Success;

use Novalnet\Payment\Model\Ui\ConfigProvider;

class Message extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    protected $novalnetHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    protected $serializer;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Novalnet\Payment\Helper\Data $novalnetHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->novalnetHelper = $novalnetHelper;
        $this->serializer = $serializer;
        parent::__construct($context);
    }

    /**
     * Return Novalnet Cashpayment additional data
     *
     * @return mixed
     */
    public function getCpAdditionalData()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        if ($order->getId()) {
            $payment = $order->getPayment();
            $additionalData = (!empty($payment->getAdditionalData())) ? json_decode($payment->getAdditionalData(), true) : [];
            $paymentType = !empty($additionalData['NnPaymentType']) ? $additionalData['NnPaymentType'] : '';

            if ($paymentType == 'CASHPAYMENT') {
                return $additionalData;
            }
        }

        return false;
    }
}

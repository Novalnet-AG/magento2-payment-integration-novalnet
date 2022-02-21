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
     * @var \Novalnet\Payment\Helper\Request
     */
    protected $novalnetRequestHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    protected $serializer;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->serializer = $serializer;
        parent::__construct($context);
    }

    /**
     * Return Novalnet Cashpayment additional data
     *
     * @param  none
     * @return mixed
     */
    public function getCpAdditionalData()
    {
        $additionalData = false;
        $order = $this->checkoutSession->getLastRealOrder();
        if ($order->getId()) {
            $payment = $order->getPayment();
            if ($payment->getMethodInstance()->getCode() == ConfigProvider::NOVALNET_CASHPAYMENT) {
                $additionalData = json_decode($payment->getAdditionalData(), true);
            }
        }

        return $additionalData;
    }
}

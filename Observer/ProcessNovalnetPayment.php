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
use Novalnet\Payment\Model\Ui\ConfigProvider;

class ProcessNovalnetPayment implements ObserverInterface
{
    /**
     * @var \Novalnet\Payment\Model\NNConfig
     */
    protected $novalnetConfig;

    /**
     * @var \Novalnet\Payment\Helper\Request
     */
    protected $novalnetRequestHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    protected $serializer;

    /**
     * @param \Novalnet\Payment\Model\NNConfig $novalnetConfig
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     */
    public function __construct(
        \Novalnet\Payment\Model\NNConfig $novalnetConfig,
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer
    ) {
        $this->novalnetConfig = $novalnetConfig;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->serializer = $serializer;
    }

    /**
     * If it's redrected to checkout onepage/multishipping success page - do this
     *
     * @param  \Magento\Framework\Event\Observer $observer
     * @return none
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order) {
            $storeId = $order->getStoreId();
            $paymentMethodCode = $order->getPayment()->getMethod();
            if (preg_match('/novalnet/', $paymentMethodCode) && $order->getPayment()->getAdditionalData()) {
                // unset session data after request formation
                $this->novalnetRequestHelper->getMethodSession($paymentMethodCode, true);
                $additionalData = json_decode($order->getPayment()->getAdditionalData(), true);
                $transactionStatus = !empty($additionalData['NnStatus']) ? $additionalData['NnStatus'] : '';
                if (!isset($additionalData['NnRedirectURL'])) {
                    $orderStatus = 'pending';
                    if ($transactionStatus == 'ON_HOLD') {
                        $orderStatus = Order::STATE_HOLDED;
                    } elseif ($transactionStatus == 'CONFIRMED' || ($transactionStatus == 'PENDING' && in_array(
                        $paymentMethodCode,
                        [
                            ConfigProvider::NOVALNET_INVOICE,
                            ConfigProvider::NOVALNET_PREPAYMENT,
                            ConfigProvider::NOVALNET_CASHPAYMENT,
                            ConfigProvider::NOVALNET_MULTIBANCO
                        ]
                    )
                    )) {
                        $orderStatus = $this->novalnetConfig->getPaymentConfig(
                            $paymentMethodCode,
                            'order_status',
                            $storeId
                        );
                    }

                    $orderStatus = $orderStatus ? $orderStatus : Order::STATE_PROCESSING;
                    // Verifies and sets order status
                    $order->setState(Order::STATE_PROCESSING)
                          ->setStatus($orderStatus);
                    $order->save();
                }
            }
        }
    }
}

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
use Novalnet\Payment\Model\NNConfig;

class ProcessNovalnetPayment implements ObserverInterface
{
    /**
     * @var \Novalnet\Payment\Model\NNConfig
     */
    protected $novalnetConfig;

    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    protected $novalnetHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    protected $serializer;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $invoiceService;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $transaction;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $coreSession;

    /**
     * @param \Novalnet\Payment\Model\NNConfig $novalnetConfig
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\DB\Transaction $transaction
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     */
    public function __construct(
        \Novalnet\Payment\Model\NNConfig $novalnetConfig,
        \Novalnet\Payment\Helper\Data $novalnetHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Framework\Session\SessionManagerInterface $coreSession
    ) {
        $this->novalnetConfig = $novalnetConfig;
        $this->novalnetHelper = $novalnetHelper;
        $this->serializer = $serializer;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->coreSession = $coreSession;
    }

    /**
     * If it's redrected to checkout onepage/multishipping success page - do this
     *
     * @param  \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order) {
            $storeId = $order->getStoreId();
            $paymentMethodCode = $order->getPayment()->getMethod();

            if (!empty($paymentMethodCode) && preg_match('/novalnet/', $paymentMethodCode) && $order->getPayment()->getAdditionalData()) {
                $additionalData = (!empty($order->getPayment()->getAdditionalData())) ? json_decode($order->getPayment()->getAdditionalData(), true) : [];
                $transactionStatus = !empty($additionalData['NnStatus']) ? $additionalData['NnStatus'] : '';
                if (!isset($additionalData['NnRedirectURL'])) {
                    $paymentType = $additionalData['NnPaymentType'];
                    $isZeroAmountBooking = (!empty($additionalData['NnZeroAmountBooking']) && $additionalData['NnZeroAmountBooking'] == '1');
                    $orderStatus = 'pending';

                    if ($transactionStatus == 'ON_HOLD' || $isZeroAmountBooking) {
                        $orderStatus = $this->novalnetConfig->getOnholdStatus($storeId);
                    } elseif ($transactionStatus == 'CONFIRMED') {
                        $orderStatus = $this->novalnetConfig->getOrderCompletionStatus($storeId);
                        if ($paymentType == 'INVOICE') {
                            $orderStatus = Order::STATE_COMPLETE;
                        }
                    } elseif ($transactionStatus == 'PENDING' && in_array($paymentType, ['INVOICE', 'PREPAYMENT', 'CASHPAYMENT', 'MULTIBANCO'])) {
                        if ($paymentType == 'INVOICE') {
                            $orderStatus = Order::STATE_PROCESSING;
                        } elseif (in_array($paymentType, ['PREPAYMENT', 'CASHPAYMENT', 'MULTIBANCO'])) {
                            $orderStatus = 'pending';
                        }
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

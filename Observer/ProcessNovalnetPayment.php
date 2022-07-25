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
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\DB\Transaction $transaction
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     */
    public function __construct(
        \Novalnet\Payment\Model\NNConfig $novalnetConfig,
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Framework\Session\SessionManagerInterface $coreSession
    ) {
        $this->novalnetConfig = $novalnetConfig;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->serializer = $serializer;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->coreSession = $coreSession;
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

            if (!empty($paymentMethodCode) && preg_match('/novalnet/', $paymentMethodCode) && $order->getPayment()->getAdditionalData()) {

                // unset session data after request formation
                $subscription = false;
                foreach ($order->getItems() as $item) {
                    $additionalData = [];
                    if (!empty($item->getAdditionalData())) {
                        $additionalData = json_decode($item->getAdditionalData(), true);
                    }

                    if (!empty($additionalData['period_unit']) && !empty($additionalData['billing_frequency'])) {
                        $subscription = true;
                        break;
                    }
                }
                if ($subscription != true) {
                    $this->novalnetRequestHelper->getMethodSession($paymentMethodCode, true);
                }
                $additionalData = (!empty($order->getPayment()->getAdditionalData())) ? json_decode($order->getPayment()->getAdditionalData(), true) : [];
                $transactionStatus = !empty($additionalData['NnStatus']) ? $additionalData['NnStatus'] : '';
                if (!isset($additionalData['NnRedirectURL'])) {
                    $orderStatus = 'pending';
                    if ($transactionStatus == 'ON_HOLD') {
                        $orderStatus = $this->novalnetConfig->getGlobalOnholdStatus($storeId);
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

                    if (($paymentMethodCode == ConfigProvider::NOVALNET_PAYPAL) && $this->coreSession->getRecurringProcess() && $transactionStatus == 'CONFIRMED') {
                        $invoice = $this->invoiceService->prepareInvoice($order);
                        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
                        $invoice->register();
                        $invoice->save();
                        $transactionSave = $this->transaction->addObject(
                            $invoice
                        )->addObject(
                            $invoice->getOrder()
                        );
                        $transactionSave->save();
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

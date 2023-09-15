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

class ProcessCaptureAction implements ObserverInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $dbTransaction;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var \Novalnet\Payment\Model\NNConfig
     */
    protected $novalnetConfig;

    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    protected $novalnetHelper;

    /**
     * @var \Novalnet\Payment\Logger\NovalnetLogger
     */
    protected $novalnetLogger;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param \Magento\Framework\DB\TransactionFactory $dbTransaction
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Novalnet\Payment\Model\NNConfig $novalnetConfig
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     * @param \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\DB\TransactionFactory $dbTransaction,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Novalnet\Payment\Model\NNConfig $novalnetConfig,
        \Novalnet\Payment\Helper\Data $novalnetHelper,
        \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
    ) {
        $this->urlInterface = $urlInterface;
        $this->dbTransaction = $dbTransaction;
        $this->invoiceSender = $invoiceSender;
        $this->logger = $logger;
        $this->novalnetConfig = $novalnetConfig;
        $this->novalnetHelper = $novalnetHelper;
        $this->novalnetLogger = $novalnetLogger;
    }

    /**
     * Process capture Action
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return mixed
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $invoice = $observer->getEvent()->getInvoice();
            $order = $invoice->getOrder();
            $storeId = $order->getStoreId();
            $paymentMethodCode = $order->getPayment()->getMethod();

            if (!empty($paymentMethodCode) && preg_match('/novalnet/', $paymentMethodCode)) {
                $additionalData = $this->novalnetHelper->getPaymentAdditionalData($order->getPayment()->getAdditionalData());
                $paymentType = !empty($additionalData['NnPaymentType']) ? $additionalData['NnPaymentType'] : '';

                if ($paymentType == 'INVOICE') {
                    if (!empty($additionalData['NnGuarantee'])) {
                        $state = \Magento\Sales\Model\Order\Invoice::STATE_PAID;
                    } else {
                        $state = \Magento\Sales\Model\Order\Invoice::STATE_OPEN;
                    }
                    // set Invoice state as Open
                    $invoice->setState($state);
                }

                $currentUrl = (!empty($this->urlInterface->getCurrentUrl())) ? $this->urlInterface->getCurrentUrl() : '';

                if ($this->novalnetHelper->isAdmin() &&
                    !preg_match('/sales_order_create/i', $currentUrl)
                ) {
                    $captureOrderStatus = $this->novalnetConfig->getOrderCompletionStatus($storeId);
                    if ($paymentType == 'INVOICE') {
                        $captureOrderStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    } elseif (in_array($paymentType, ['PREPAYMENT', 'CASHPAYMENT', 'MULTIBANCO'])) {
                        $captureOrderStatus = 'pending';
                    }

                    // Set capture status for Novalnet payments
                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                    ->setStatus($captureOrderStatus)
                    ->save();

                    $order->addStatusHistoryComment(__('The transaction has been confirmed'), false)
                    ->save();
                }

                if ($this->novalnetHelper->isAdmin() && (preg_match('/order_create/i', $currentUrl) || preg_match('/ordercapture/i', $currentUrl))) {
                    $this->dbTransaction->create()->addObject($invoice)->addObject($order)->save();
                    $shopVersion = (string) $this->novalnetHelper->getMagentoVersion();
                    if (version_compare('2.4.0', $shopVersion) == 1) {
                        $this->invoiceSender->send($invoice);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->novalnetLogger->error($e);
        }

        return $this;
    }
}

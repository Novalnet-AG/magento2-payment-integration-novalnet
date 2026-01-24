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
namespace Novalnet\Payment\Controller\Adminhtml\Sales;

class OrderCapture extends \Magento\Sales\Controller\Adminhtml\Order
{
    /**
     * @var \Novalnet\Payment\Model\TransactionStatus
     */
    private $transactionStatusModel;

    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    private $novalnetHelper;

    /**
     * @var \Novalnet\Payment\Logger\NovalnetLogger
     */
    private $novalnetLogger;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Translate\InlineInterface $translateInline
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Sales\Api\OrderManagementInterface $orderManagement
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     * @param \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Psr\Log\LoggerInterface $logger,
        \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel,
        \Novalnet\Payment\Helper\Data $novalnetHelper,
        \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
    ) {
        $this->transactionStatusModel = $transactionStatusModel;
        $this->novalnetHelper = $novalnetHelper;
        $this->novalnetLogger = $novalnetLogger;
        parent::__construct(
            $context,
            $coreRegistry,
            $fileFactory,
            $translateInline,
            $resultPageFactory,
            $resultJsonFactory,
            $resultLayoutFactory,
            $resultRawFactory,
            $orderManagement,
            $orderRepository,
            $logger
        );
    }

    /**
     * Order confirmation process for Novalnet payments (Invoice)
     *
     * @return mixed
     */
    public function execute()
    {
        $order = $this->_initOrder();
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            if ($order) {
                $payment = $order->getPayment();
                $transactionId = preg_replace('/[^0-9]+/', '', $payment->getLastTransId());
                $additionalData = $this->novalnetHelper->getPaymentAdditionalData($payment->getAdditionalData());
                $paymentType = !empty($additionalData['NnPaymentType']) ? $additionalData['NnPaymentType'] : '';

                if ($order->canInvoice() && $paymentType == 'INVOICE') {
                    $invoice = $order->prepareInvoice();
                    $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE)
                        ->register();

                    if (!empty($additionalData['NnGuarantee'])) {
                        $state = \Magento\Sales\Model\Order\Invoice::STATE_PAID;
                    } else {
                        $state = \Magento\Sales\Model\Order\Invoice::STATE_OPEN;
                    }

                    $invoice->setState($state)
                        ->setTransactionId($transactionId)
                        ->save();

                    $transactionStatus = !empty($additionalData['NnStatus'])
                        ? $this->novalnetHelper->getStatus($additionalData['NnStatus'], $order, $paymentType) : '';
                    $this->transactionStatusModel->loadByAttribute($order->getIncrementId(), 'order_id')
                        ->setStatus($transactionStatus)->save();

                    // Invoice controller (set processing as static for order completion)
                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                        ->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
                    $order->save();

                    $this->messageManager->addSuccess(__('The invoice has been created.'));
                    $this->novalnetLogger->notice('The invoice has been created for order no: ' . $order->getIncrementId());
                } else {
                    $this->messageManager->addError(__('The order does not allow an invoice to be created.'));
                    $this->novalnetLogger->notice('The order does not allow an invoice to be created for order no: ' . $order->getIncrementId());
                }

                $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getId()]);
                return $resultRedirect;
            }
        } catch (\Exception $e) {
            $error = __('The order does not allow an invoice to be created.') . ' ' . $e->getMessage();
            $this->messageManager->addError($error);
            $this->novalnetLogger->error($error);
        }

        $resultRedirect->setPath('sales/order/');
        return $resultRedirect;
    }
}

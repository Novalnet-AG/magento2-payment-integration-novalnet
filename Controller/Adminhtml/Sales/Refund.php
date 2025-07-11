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

use Novalnet\Payment\Model\NNConfig;
use Magento\Sales\Model\Order;

class Refund extends \Magento\Sales\Controller\Adminhtml\Order
{
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private $clientFactory;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    private $pricingHelper;

    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    private $novalnetHelper;

    /**
     * @var \Novalnet\Payment\Logger\NovalnetLogger
     */
    private $novalnetLogger;

        /**
         * @var Order
         */
    private $salesOrderModel;

        /**
         * @var \Magento\Sales\Model\Order\Payment\Transaction
         */
    private $transactionModel;

    /**
     * @var mixed
     */
    private $order;
    
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
     * @param \Magento\Framework\HTTP\Client\Curl $clientFactory
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
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
        \Magento\Framework\HTTP\Client\Curl $clientFactory,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Novalnet\Payment\Helper\Data $novalnetHelper,
        \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger,
        \Magento\Sales\Model\Order\Payment\Transaction $transactionModel,
        \Magento\Sales\Model\Order $salesOrderModel
    ) {
        $this->pricingHelper = $pricingHelper;
        $this->clientFactory = $clientFactory;
        $this->novalnetHelper = $novalnetHelper;
        $this->novalnetLogger = $novalnetLogger;
        $this->salesOrderModel = $salesOrderModel;
        $this->transactionModel = $transactionModel;
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
     * Order refund process for Novalnet payments
     *
     * @return mixed
     */
    public function execute()
    {
        $refundAmount = $this->getRequest()->getParam('nn-refund-amount');
        $refundTid = $this->getRequest()->getParam('nn-refund-tid');
        $instalmentCancel = $this->getRequest()->getParam('nn-instalment-cancel');
        $cancelTid = $this->getRequest()->getParam('nn-cancel-tid');
        $order = $this->_initOrder();
        $payment = $order->getPayment();
        $storeId = $order->getStoreId();
        $resultRedirect = $this->resultRedirectFactory->create();
        $additionalData = $this->novalnetHelper->getPaymentAdditionalData($payment->getAdditionalData());

        if (!empty($additionalData['NnZeroAmountBooking']) && !empty($additionalData['NnZeroAmountDone'])) {
            $refundTid = $additionalData['NnZeroAmountRefTid'];
        }

        if ((!$refundAmount && !$instalmentCancel)) {
            $this->messageManager->addError(__('The Amount should be in future'));
            $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getId()]);
            return $resultRedirect;
        }

        if ($order) {
            if (!empty($instalmentCancel)) {
                $this->novalnetLogger->notice('Intiated instalment cancel for Novalnet ID: ' . $cancelTid);
                $url = NNConfig::NOVALNET_INSTALMENT_CANCEL;
                $requestData = [
                    'instalment' => [
                        'tid'    => str_replace("-capture", "", $cancelTid),
                        'cancel_type ' => $instalmentCancel
                    ],
                    'custom' => [
                        'shop_invoked' => 1,
                        'lang' => $this->novalnetHelper->getDefaultLanguage()
                    ]
                ];
            } elseif (!empty($refundAmount) && !empty($refundTid)) {
                $this->novalnetLogger->notice('Intiated instalment refund for Novalnet ID: ' . $refundTid);
                $url = NNConfig::NOVALNET_REFUND_URL;
                $requestData = [
                    'transaction' => [
                        'tid' => $refundTid,
                        'amount' => $refundAmount
                    ],
                    'custom' => [
                        'shop_invoked' => 1,
                        'lang' => $this->novalnetHelper->getDefaultLanguage()
                    ]
                ];
            }

            $this->clientFactory->setHeaders($this->novalnetHelper->getRequestHeaders(false, $storeId));
            $this->clientFactory->post($url, $this->novalnetHelper->jsonEncode($requestData));
            $response = new \Magento\Framework\DataObject();
            $responseBody = ($this->clientFactory->getBody()) ? $this->novalnetHelper->jsonDecode($this->clientFactory->getBody()) : [];
            $response->setData($responseBody);

            if ($response->getData('result/status') == 'SUCCESS') {

                if (!empty($instalmentCancel)) {
                    $this->order = $this->getOrder($cancelTid);
                    $refundAmount = $response->getData('transaction/refund/amount');
                    $refundAmountwithCurrency = $this->pricingHelper->currency(($refundAmount / 100), true, false);
                    $additionalData['refamount'] = $refundAmountwithCurrency;
                    $additionalData[$instalmentCancel] = 1;
                    $additionalData['InstalmentCancel'] = 1;
                    if ($instalmentCancel == 'CANCEL_ALL_CYCLES') {
                        try {
                            $this->order->setState(Order::STATE_CLOSED)->setStatus(Order::STATE_CLOSED)->save();
                        } catch (\Exception $e) {
                            $this->messageManager->addError(__('An error occurred while trying to close the order: ' . $e->getMessage()));
                            $this->novalnetLogger->error('Error closing order: ' . $e->getMessage());
                        }
                    }
                    $this->messageManager->addSuccess(__('The Cancellation executed properly'));
                    $this->novalnetLogger->notice('The Cancellation executed properly for order id ' . $order->getId());
                } else {
                    $additionalData['NnrefundedTid'] = $refundTid;
                    $refundAmount = $response->getData('transaction/refund/amount');
                    $newRefundTid = $response->getData('transaction/refund/tid')
                        ? $response->getData('transaction/refund/tid') : $refundTid;
                    $refundAmountwithCurrency = $this->pricingHelper->currency(($refundAmount / 100), true, false);
                    $additionalData['NnRefunded'][$newRefundTid]['reftid'] = $newRefundTid;
                    $additionalData['NnRefunded'][$newRefundTid]['refamount'] = $refundAmountwithCurrency;
                    $additionalData['NnRefunded'][$newRefundTid]['reqtid'] = $refundTid;
                    $additionalData['Nnrefundexc'] = 1;
                    $additionalData['NnrefAmount'] = $response->getData('transaction/refunded_amount');
                    $this->messageManager->addSuccess(__('The Refund executed properly'));
                    $this->novalnetLogger->notice('The Refund executed properly for order id ' . $order->getId());
                }
                $payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();
            } elseif ($response->getData('result/status_text')) {
                $this->messageManager->addError($response->getData('result/status_text'));
                $this->novalnetLogger->notice('The Refund not working for order id: ' . $order->getId());
            }
            $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getId()]);
            return $resultRedirect;
        }
        $resultRedirect->setPath('sales/order/');
        return $resultRedirect;
    }

    /**
     * Get order reference.
     *
     * @return mixed
     */

    private function getOrder($cancelTid)
    {
        $orderCollection = $this->transactionModel->getCollection()->addFieldToFilter('txn_id', $cancelTid);
        if (!empty($orderCollection)) {
                $order = $orderCollection->getFirstItem()->getOrder();
        }
        if (empty($order) || empty($order->getIncrementId())) {
            return false;
        }

        return $order;
    }
}

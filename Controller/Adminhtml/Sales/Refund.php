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
     * @var \Novalnet\Payment\Helper\Request
     */
    private $novalnetRequestHelper;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    private $serializer;

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
     * @param \Magento\Framework\HTTP\Client\Curl $clientFactory
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
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
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer,
        \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
    ) {
        $this->pricingHelper = $pricingHelper;
        $this->clientFactory = $clientFactory;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->jsonHelper = $jsonHelper;
        $this->serializer = $serializer;
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
     * Order refund process for Novalnet payments
     *
     * @param  none
     * @return void
     */
    public function execute()
    {
        $refundAmount = $this->getRequest()->getParam('nn-refund-amount');
        $refundTid = $this->getRequest()->getParam('nn-refund-tid');
        $refundTid = $this->novalnetRequestHelper->makeValidNumber($refundTid);
        $instalmentCancel = $this->getRequest()->getParam('nn-instalment-cancel');
        $order = $this->_initOrder();
        $storeId = $order->getStoreId();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ((!$refundAmount && !$instalmentCancel) || !$refundTid) {
            $this->messageManager->addError(__('The Amount should be in future'));
            $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getId()]);
            return $resultRedirect;
        }

        if ($order) {
            if (!empty($instalmentCancel)) {
                $this->novalnetLogger->notice('Intiated instalment cancel for Novalnet ID: ' . $refundTid);
                $url = NNConfig::NOVALNET_INSTALMENT_CANCEL;
                $requestData = [
                    'instalment' => [
                        'tid'    => $refundTid,
                    ]
                ];
            } else {
                $this->novalnetLogger->notice('Intiated instalment refund for Novalnet ID: ' . $refundTid);
                $url = NNConfig::NOVALNET_REFUND_URL;
                $requestData = [
                    'transaction' => [
                        'tid' => $refundTid,
                        'amount' => $refundAmount
                    ],
                    'custom' => ['lang' => $this->novalnetRequestHelper->getDefaultLanguage()]
                ];
            }

            $this->clientFactory->setHeaders($this->novalnetRequestHelper->getRequestHeaders(false, $storeId));
            $this->clientFactory->post($url, $this->jsonHelper->jsonEncode($requestData));
            $response = new \Magento\Framework\DataObject();
            $response->setData(json_decode($this->clientFactory->getBody(), true));
            $payment = $order->getPayment();

            if ($response->getData('result/status') == 'SUCCESS') {
                $refundKey = $this->getRequest()->getParam('nn-refund-key');
                $newRefundTid = $response->getData('transaction/refund/tid')
                    ? $response->getData('transaction/refund/tid') : $refundTid;
                $additionalData = $this->novalnetRequestHelper->isSerialized($payment->getAdditionalData())
                ? $this->serializer->unserialize($payment->getAdditionalData())
                : json_decode($payment->getAdditionalData(), true);
                if (empty($refundAmount)) {
                    $refundAmount = $response->getData('transaction/refund/amount');
                    $additionalData['InstalmentCancel'] = 1;
                }
                $additionalData['InstalmentDetails'][$refundKey]['Refund'][] = [
                    'tid' => $newRefundTid,
                    'amount' => $refundAmount / 100
                ];

                $refundAmountwithCurrency = $this->pricingHelper->currency(($refundAmount / 100), true, false);
                $additionalData['NnRefunded'][$newRefundTid]['reftid'] = $newRefundTid;
                $additionalData['NnRefunded'][$newRefundTid]['refamount'] = $refundAmountwithCurrency;
                $additionalData['NnRefunded'][$newRefundTid]['reqtid'] = $refundTid;

                $payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();
                $this->messageManager->addSuccess(__('The Refund executed properly'));
                $this->novalnetLogger->notice('The Refund executed properly for order id ' . $order->getId());
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
}

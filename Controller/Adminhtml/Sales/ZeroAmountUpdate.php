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
use Magento\Store\Model\StoreManagerInterface;

class ZeroAmountUpdate extends \Magento\Sales\Controller\Adminhtml\Order
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
     * @var NNConfig
     */
    private $novalnetConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Novalnet\Payment\Model\TransactionStatus
     */
    private $transactionStatusModel;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    private $dbTransaction;

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
     * @param NNConfig $novalnetConfig
     * @param StoreManagerInterface $storeManager
     * @param \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Magento\Framework\DB\TransactionFactory $dbTransaction
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
        NNConfig $novalnetConfig,
        StoreManagerInterface $storeManager,
        \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\DB\TransactionFactory $dbTransaction
    ) {
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
        $this->pricingHelper = $pricingHelper;
        $this->clientFactory = $clientFactory;
        $this->novalnetHelper = $novalnetHelper;
        $this->novalnetLogger = $novalnetLogger;
        $this->novalnetConfig = $novalnetConfig;
        $this->storeManager = $storeManager;
        $this->transactionStatusModel = $transactionStatusModel;
        $this->invoiceSender = $invoiceSender;
        $this->dbTransaction = $dbTransaction;
    }

    /**
     * Zero amount booking process
     *
     * @return mixed
     */
    public function execute()
    {
        $order = $this->_initOrder();
        $resultRedirect = $this->resultRedirectFactory->create();
        ($order->getId()) ? $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getId()]) : $resultRedirect->setPath('sales/order/');

        try {
            $amountToUpdate = $this->getRequest()->getParam('nn-amount-to-update');
            $storeId = $order->getStoreId();
            $payment = $order->getPayment();
            $additionalData = $this->novalnetHelper->getPaymentAdditionalData($payment->getAdditionalData());
            $billingAddress = $order->getBillingAddress();
            $billingStreet = $this->novalnetHelper->getStreet($billingAddress);
            $shippingAddress = $order->getShippingAddress();
            $paymentToken = $this->getPaymentToken($order->getIncrementId());

            $data = [];

            $data['merchant'] = [
                'signature' => $this->novalnetConfig->getGlobalConfig('signature', $storeId),
                'tariff' => $this->novalnetConfig->getGlobalConfig('tariff_id', $storeId),
            ];

            $data['customer'] = [
                'first_name' => $billingAddress->getFirstname(),
                'last_name' => $billingAddress->getLastname(),
                'email' => $billingAddress->getEmail(),
                'tel' => $billingAddress->getTelephone(),
                'customer_ip' => $this->novalnetHelper->getRequestIp(),
                'customer_no' => $order->getCustomerId(),
            ];

            $data['customer']['billing'] = [
                'street' => $billingStreet,
                'city' => $billingAddress->getCity(),
                'zip' => $billingAddress->getPostcode(),
                'country_code' => $billingAddress->getCountryId(),
                'state' => $this->novalnetHelper->getRegionNameByCode($billingAddress->getRegionCode(), $billingAddress->getCountryId())
            ];

            if (!empty($shippingAddress)) {
                if ($billingAddress->getFirstname() == $shippingAddress->getFirstname() &&
                    $billingAddress->getLastname() == $shippingAddress->getLastname() &&
                    $billingStreet == $this->novalnetHelper->getStreet($shippingAddress) &&
                    $billingAddress->getCity() == $shippingAddress->getCity() &&
                    $billingAddress->getCountryId() ==$shippingAddress->getCountryId() &&
                    $billingAddress->getPostcode() == $shippingAddress->getPostcode()
                ) {
                    $data['customer']['shipping']['same_as_billing'] = 1;
                } else {
                    $data['customer']['shipping'] = [
                        'first_name' => $shippingAddress->getFirstname(),
                        'last_name' => $shippingAddress->getLastname(),
                        'email' => $shippingAddress->getEmail(),
                        'tel' => $shippingAddress->getTelephone(),
                        'street' => $this->novalnetHelper->getStreet($shippingAddress),
                        'city' => $shippingAddress->getCity(),
                        'zip' => $shippingAddress->getPostcode(),
                        'country_code' => $shippingAddress->getCountryId(),
                        'state' => $this->novalnetHelper->getRegionNameByCode($shippingAddress->getRegionCode(), $shippingAddress->getCountryId())
                    ];

                    if (!empty($shippingAddress->getCompany())) {
                        $data['customer']['shipping']['company'] = $shippingAddress->getCompany();
                    }
                }
            }

            $data['transaction'] = [
                'payment_type' => $additionalData['NnPaymentType'],
                'amount' => $amountToUpdate,
                'currency' => $order->getBaseCurrencyCode(),
                'test_mode' => $additionalData['NnTestMode'],
                'order_no' => $order->getIncrementId(),
                'system_ip' => $this->novalnetHelper->getServerAddr(),
                'system_name' => 'Magento',
                'system_version' => $this->novalnetHelper->getMagentoVersion() . '-' . $this->novalnetHelper->getNovalnetVersion(),
                'system_url' => $this->storeManager->getStore()->getBaseUrl(),
                'payment_data' => [
                    'token' => $paymentToken
                ]
            ];

            $data['custom'] = [
                'lang' => $this->novalnetHelper->getDefaultLanguage(),
            ];

            $endPointURL = NNConfig::NOVALNET_PAYMENT_URL;
            $this->clientFactory->setHeaders($this->novalnetHelper->getRequestHeaders(false, $storeId));
            $this->clientFactory->post($endPointURL, $this->novalnetHelper->jsonEncode($data));
            $response = new \Magento\Framework\DataObject();
            $responseBody = ($this->clientFactory->getBody()) ? $this->novalnetHelper->jsonDecode($this->clientFactory->getBody()) : [];
            $response->setData($responseBody);
            
            if ($response->getData('result/status') == 'SUCCESS') {
                $zeroAmountRefTid = $response->getData('transaction/tid');
                $updatedAmount = $this->novalnetHelper->getFormattedAmount($response->getData('transaction/amount'), 'RAW');

                $updatedAmountwithCurrency = $this->pricingHelper->currency($updatedAmount, true, false);
                $additionalData['NnZeroAmountDone'] = 1;
                $additionalData['NnUpdatedZeroAmount'] = $updatedAmountwithCurrency;
                $additionalData['NnZeroAmountRefTid'] = $zeroAmountRefTid;
                $additionalData['NnZeroAmountCapture'] = 1;
                $payment->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData));

                $payment->setTransactionId($zeroAmountRefTid . '-zeroamount')
                    ->setLastTransId($zeroAmountRefTid)
                    ->capture();

                if ($response->getData('transaction/status') == 'CONFIRMED') {
                    $orderStatus = $this->novalnetConfig->getOrderCompletionStatus($storeId);
                    $order->setStatus($orderStatus)->save();
                }

                $invoice = current($order->getInvoiceCollection()->getItems());
                $this->dbTransaction->create()->addObject($payment)->addObject($invoice)->addObject($order)->save();
                $this->invoiceSender->send($invoice);

                $order->addStatusHistoryComment(__('Zero Amount has been updated successfully'), false)->save();
                $this->messageManager->addSuccess(__('Amount has been booked successfully'));
                $this->novalnetLogger->notice('Zero Amount has been updated successfully for order id: ' . $order->getId());
            } elseif ($response->getData('result/status_text')) {
                $this->messageManager->addError($response->getData('result/status_text'));
                $order->addStatusHistoryComment(__('Zero Amount update has been failed'), false)->save();
                $this->novalnetLogger->notice('Zero Amount update has been failed for order id: ' . $order->getId());
            }

            return $resultRedirect;
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            return $resultRedirect;
        }
    }

    /**
     * Get payment token for the order
     *
     * @param mixed $orderIncrementId
     * @return string|null
     */
    protected function getPaymentToken($orderIncrementId)
    {
        try {
            $tokenInfo = $this->transactionStatusModel->getCollection()->addFieldToFilter('order_id', ['eq' => $orderIncrementId])->getFirstItem();

            if (!empty($tokenInfo['token'])) {
                return $tokenInfo['token'];
            }

            return null;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}

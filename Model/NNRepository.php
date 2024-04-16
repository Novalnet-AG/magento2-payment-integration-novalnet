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
namespace Novalnet\Payment\Model;

use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;
use Novalnet\Payment\Model\NNConfig;
use Novalnet\Payment\Model\Ui\ConfigProvider;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use \Magento\Checkout\Model\Type\Onepage;
use \Magento\Customer\Api\Data\GroupInterface;

class NNRepository implements \Novalnet\Payment\Api\NNRepositoryInterface
{
    /**
     * @var array
     */
    private $mandatoryParams = [
        'event' => [
            'type',
            'checksum',
            'tid'
        ],
        'merchant' => [
            'vendor',
            'project'
        ],
        'transaction' => [
            'tid',
            'payment_type',
            'status',
        ],
        'result' => [
            'status'
        ],
    ];

    /**
     * @var mixed
     */
    private $response;

    /**
     * @var mixed
     */
    private $order;

    /**
     * @var array
     */
    private $eventData;

    /**
     * @var string
     */
    private $eventType;

    /**
     * @var int
     */
    private $eventTid;

    /**
     * @var int
     */
    private $parentTid;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $salesOrderModel;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlInterface;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $requestInterface;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction
     */
    private $transactionModel;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    private $pricingHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timeZone;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    private $inlineTranslation;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    private $orderEmailSender;

    /**
     * @var \Magento\Sales\Model\Order\Address\Renderer
     */
    private $salesOrderAddressRenderer;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    private $paymentHelper;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private $clientFactory;

    /**
     * @var \Magento\Sales\Model\Order\CreditmemoFactory
     */
    private $creditMemoFacory;

    /**
     * @var \Magento\Sales\Model\Service\CreditmemoService
     */
    private $creditmemoService;

    /**
     * @var \Novalnet\Payment\Model\Callback
     */
    private $callbackModel;

    /**
     * @var \Novalnet\Payment\Model\TransactionStatus
     */
    private $transactionStatusModel;

    /**
     * @var \Novalnet\Payment\Helper\Request
     */
    private $novalnetRequestHelper;

    /**
     * @var \Novalnet\Payment\Model\NNConfig
     */
    private $novalnetConfig;

    /**
     * @var \Magento\Quote\Model\QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var mixed
     */
    private $additionalMessage;

    /**
     * @var mixed
     */
    private $callbackMessage;

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
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader
     */
    private $creditmemoLoader;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    private $cart;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    private $checkoutHelper;

    /**
     * @var \Magento\Quote\Api\Data\EstimateAddressInterfaceFactory
     */
    private $estimatedAddressFactory;

    /**
     * @var \Magento\Quote\Api\ShippingMethodManagementInterface
     */
    private $shippingMethodManager;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    private $quoteManagement;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var \Magento\Tax\Helper\Data
     */
    private $taxHelper;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    private $countryFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    private $productFactory;

    /**
     * @var \Magento\Tax\Api\TaxCalculationInterface
     */
    private $taxCalculation;

    /**
     * @var \Laminas\Uri\Uri
     */
    private $laminasUri;

    /**
     * @var \Magento\Directory\Model\Currency
     */
    private $currencyModel;

    /**
     * @var string
     */
    private $currentTime;

    /**
     * @var mixed
     */
    private $orderNo;

    /**
     * @var mixed
     */
    private $testMode;

    /**
     * @var mixed
     */
    private $emailBody;

    /**
     * @var mixed
     */
    private $storeId;

    /**
     * @var string
     */
    private $paymentAccessKey;

    /**
     * @var mixed
     */
    private $payment;

    /**
     * @var string
     */
    private $code;

    /**
     * @var mixed
     */
    private $paymentTxnId;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $emailFromAddr;

    /**
     * @var string
     */
    private $emailFromName;

    /**
     * @var string
     */
    private $emailToName;

    /**
     * @var string
     */
    private $emailToAddr;

    /**
     * @var string
     */
    private $emailSubject;

    /**
     * @var string
     */
    private $storeFrontendName;

    /**
     * @param \Magento\Sales\Model\Order $salesOrderModel
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param \Magento\Framework\App\RequestInterface $requestInterface
     * @param \Magento\Sales\Model\Order\Payment\Transaction $transactionModel
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderEmailSender
     * @param \Magento\Sales\Model\Order\Address\Renderer $salesOrderAddressRenderer
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\HTTP\Client\Curl $clientFactory
     * @param \Magento\Sales\Model\Order\CreditmemoFactory $creditMemoFacory
     * @param \Magento\Sales\Model\Service\CreditmemoService $creditmemoService
     * @param \Novalnet\Payment\Model\Callback $callbackModel
     * @param \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     * @param \Novalnet\Payment\Model\NNConfig $novalnetConfig
     * @param \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader $creditmemoLoader
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Checkout\Helper\Data $checkoutHelper
     * @param \Magento\Quote\Api\Data\EstimateAddressInterfaceFactory $estimatedAddressFactory
     * @param \Magento\Quote\Api\ShippingMethodManagementInterface $shippingMethodManager
     * @param \Magento\Quote\Api\CartManagementInterface $quoteManagement
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Tax\Helper\Data $taxHelper
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Tax\Api\TaxCalculationInterface $taxCalculation
     * @param \Laminas\Uri\Uri $laminasUri
     * @param \Magento\Directory\Model\Currency $currencyModel
     */
    public function __construct(
        Order $salesOrderModel,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\App\RequestInterface $requestInterface,
        \Magento\Sales\Model\Order\Payment\Transaction $transactionModel,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderEmailSender,
        \Magento\Sales\Model\Order\Address\Renderer $salesOrderAddressRenderer,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\HTTP\Client\Curl $clientFactory,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditMemoFacory,
        \Magento\Sales\Model\Service\CreditmemoService $creditmemoService,
        \Novalnet\Payment\Model\Callback $callbackModel,
        \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel,
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer,
        \Novalnet\Payment\Model\NNConfig $novalnetConfig,
        \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader $creditmemoLoader,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Quote\Api\Data\EstimateAddressInterfaceFactory $estimatedAddressFactory,
        \Magento\Quote\Api\ShippingMethodManagementInterface $shippingMethodManager,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Tax\Helper\Data $taxHelper,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Tax\Api\TaxCalculationInterface $taxCalculation,
        \Laminas\Uri\Uri $laminasUri,
        \Magento\Directory\Model\Currency $currencyModel
    ) {
        $this->salesOrderModel = $salesOrderModel;
        $this->urlInterface = $urlInterface;
        $this->requestInterface = $requestInterface;
        $this->transactionModel = $transactionModel;
        $this->pricingHelper = $pricingHelper;
        $this->dateTime = $dateTime;
        $this->storeManager = $storeManager;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->timeZone = $timeZone;
        $this->scopeConfig = $scopeConfig;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->orderEmailSender = $orderEmailSender;
        $this->paymentHelper = $paymentHelper;
        $this->salesOrderAddressRenderer = $salesOrderAddressRenderer;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->clientFactory = $clientFactory;
        $this->creditMemoFacory = $creditMemoFacory;
        $this->creditmemoService = $creditmemoService;
        $this->callbackModel = $callbackModel;
        $this->transactionStatusModel = $transactionStatusModel;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->jsonHelper = $jsonHelper;
        $this->serializer = $serializer;
        $this->novalnetConfig = $novalnetConfig;
        $this->novalnetLogger = $novalnetLogger;
        $this->invoiceSender = $invoiceSender;
        $this->creditmemoLoader = $creditmemoLoader;
        $this->eventManager = $eventManager;
        $this->cart = $cart;
        $this->checkoutHelper = $checkoutHelper;
        $this->estimatedAddressFactory = $estimatedAddressFactory;
        $this->shippingMethodManager = $shippingMethodManager;
        $this->quoteManagement = $quoteManagement;
        $this->productRepository = $productRepository;
        $this->priceCurrency = $priceCurrency;
        $this->taxHelper = $taxHelper;
        $this->countryFactory = $countryFactory;
        $this->productFactory = $productFactory;
        $this->taxCalculation = $taxCalculation;
        $this->laminasUri = $laminasUri;
        $this->currencyModel = $currencyModel;
    }

    /**
     * Novalnet product activation key auto config
     *
     * @api
     * @param string $signature
     * @param string $payment_access_key
     * @return string
     */
    public function activateProductKey($signature, $payment_access_key)
    {
        $data['merchant'] = ['signature' => $signature];
        $data['custom'] = ['lang' => $this->novalnetRequestHelper->getDefaultLanguage()];
        $this->clientFactory->setHeaders(
            $this->novalnetRequestHelper->getRequestHeaders($payment_access_key)
        );
        $this->clientFactory->post(NNConfig::NOVALNET_MERCHANT_DETAIL_URL, $this->jsonHelper->jsonEncode($data));
        $response = (!empty($this->clientFactory->getBody())) ? json_decode($this->clientFactory->getBody(), true) : [];

        return $this->clientFactory->getBody();
    }

    /**
     * Novalnet Webhook URL configuration
     *
     * @api
     * @param string $signature
     * @param string $payment_access_key
     * @return string
     */
    public function configWebhookUrl($signature, $payment_access_key)
    {
        $webhook_url = $this->requestInterface->getParam('webhookurl');
        if (filter_var($webhook_url, FILTER_VALIDATE_URL) === false) {
            $data['result'] = ['status' => 'failure', 'status_text' => __('Please enter valid URL')];
            return $this->jsonHelper->jsonEncode($data);
        }
        $data['merchant'] = ['signature' => $signature];
        $data['custom'] = ['lang' => $this->novalnetRequestHelper->getDefaultLanguage()];
        $data['webhook'] = ['url' => $webhook_url];
        $this->clientFactory->setHeaders(
            $this->novalnetRequestHelper->getRequestHeaders($payment_access_key)
        );
        $this->clientFactory->post(NNConfig::NOVALNET_WEBHOOK_CONFIG_URL, $this->jsonHelper->jsonEncode($data));
        $response = (!empty($this->clientFactory->getBody())) ? json_decode($this->clientFactory->getBody(), true) : [];

        return $this->clientFactory->getBody();
    }

    /**
     * Get redirect URL
     *
     * @api
     * @param string[] $data
     * @return string
     */
    public function getRedirectURL($data)
    {
        $quoteId = $data['quote_id'];
        $this->novalnetLogger->notice('Redirect from checkout to redirect_url webapi');
        if (!$this->customerSession->isLoggedIn()) {
            $quoteMaskData = $this->quoteIdMaskFactory->create()->load($quoteId, 'masked_id');
            $quoteId = $quoteMaskData->getQuoteId();
        }

        $this->novalnetLogger->notice('quote_id retrieved ' . $quoteId);

        // Loads session quote from checkout
        $sessionQuoteId = $this->checkoutSession->getLastQuoteId();
        $orderId = $this->checkoutSession->getLastOrderId();

        $this->novalnetLogger->notice('order_id retrieved ' . $orderId);

        if ($quoteId != $sessionQuoteId) {
            $orderId = $this->salesOrderModel->getCollection()->addFieldToFilter('quote_id', $quoteId)
                ->getFirstItem()->getId();
        }

        $order = $this->salesOrderModel->load($orderId);

        $this->novalnetLogger->notice('Order loaded successfully ' . $order->getIncrementId());
        $payment = $order->getPayment();
        $additionalData = (!empty($payment->getAdditionalData())) ? json_decode($payment->getAdditionalData(), true) : [];

        if (!empty($additionalData['NnRedirectURL'])) {
            // set the order status to pending_payment before redirect to novalnet
            $order->setState(Order::STATE_PENDING_PAYMENT)
                ->setStatus(Order::STATE_PENDING_PAYMENT)
                ->save();
            $order->addStatusHistoryComment(__('Customer was redirected to Novalnet'))
                ->save();

            $this->novalnetLogger->notice('Order status and comments updated successfully');

            return $additionalData['NnRedirectURL'];
        } else {
            return $this->urlInterface->getUrl('checkout/cart');
        }
    }

    /**
     * Remove Novalnet payment token
     *
     * @api
     * @param int $transactionRowId
     * @return bool
     */
    public function removeToken($transactionRowId)
    {
        $transactionStatus = $this->transactionStatusModel->load($transactionRowId);
        if ($transactionStatus->getTokenInfo()) {
            $transactionStatus->setTokenInfo(null)->save();
            return true;
        }

        return false;
    }

    /**
     * Get Instalment payment options
     *
     * @api
     * @param string $code
     * @param float $total
     * @return string
     */
    public function getInstalmentOptions($code, $total)
    {
        $instalmentCycles = $this->novalnetConfig->getPaymentConfig($code, 'instalment_cycles');
        $instalmentCycles = (!empty($instalmentCycles)) ? explode(',', $instalmentCycles) : [];
        $storeId = $this->storeManager->getStore()->getId();
        $allCycles = [];
        $i = 1;
        foreach ($instalmentCycles as $cycle) {
            if (($total / $cycle) >= 9.99) {
                $formattedAmount = strip_tags($this->novalnetRequestHelper->getAmountWithSymbol(sprintf('%0.2f', $total / $cycle), $storeId));
                $allCycles[$i] = ['instalment_key' => $cycle . ' X ' . $formattedAmount . '(' . (__(' per month') . ')'), 'instalment_value' => $cycle];
                $i++;
            }
        }
        return $this->jsonHelper->jsonEncode($allCycles);
    }

    /**
     * Get Instalment payment cycle details
     *
     * @api
     * @param float $amount
     * @param int $period
     * @return string
     */
    public function getInstalmentCycleAmount($amount, $period)
    {
        $cycleAmount = sprintf('%0.2f', $amount / $period);
        $splitedAmount = $cycleAmount * ($period - 1);
        $lastCycle = (sprintf('%0.2f', $amount - $splitedAmount) * 100) / 100;
        $data = ['cycle_amount' => $cycleAmount, 'last_cycle' => $lastCycle, 'amount' => $amount];
        return $this->jsonHelper->jsonEncode($data);
    }

    /**
     * Novalnet payment callback
     *
     * @api
     * @return string
     */
    public function callback()
    {
        if ($this->assignGlobalParams()) {
            if ($this->eventType == 'PAYMENT') {
                if (empty($this->paymentTxnId)) {
                    $this->handleCommunicationFailure();
                } else {
                    $this->displayMessage('Novalnet Callback executed. The Transaction ID already existed');
                }
            } elseif ($this->eventType == 'TRANSACTION_CAPTURE') {
                $this->transactionCapture();
            } elseif ($this->eventType == 'TRANSACTION_CANCEL') {
                $this->transactionCancellation();
            } elseif ($this->eventType == 'TRANSACTION_REFUND') {
                $this->refundProcess();
            } elseif ($this->eventType == 'TRANSACTION_UPDATE') {
                $this->transactionUpdate();
            } elseif ($this->eventType == 'CREDIT') {
                $this->creditProcess();
            } elseif ($this->eventType == 'INSTALMENT') {
                $this->instalmentProcess();
            } elseif ($this->eventType == 'INSTALMENT_CANCEL') {
                $this->instalmentCancelProcess();
            } elseif (in_array($this->eventType, ['CHARGEBACK', 'RETURN_DEBIT', 'REVERSAL'])) {
                $this->chargebackProcess();
            } elseif (in_array($this->eventType, ['PAYMENT_REMINDER_1', 'PAYMENT_REMINDER_2'])) {
                $this->paymentReminderProcess();
            } elseif ($this->eventType == 'SUBMISSION_TO_COLLECTION_AGENCY') {
                $this->collectionProcess();
            } else {
                $this->displayMessage("The webhook notification has been received for the unhandled EVENT type($this->eventType)");
            }
        }

        return $this->additionalMessage . $this->callbackMessage;
    }

    /**
     * Assign Global params for callback process
     *
     * @return boolean
     */
    private function assignGlobalParams()
    {
        try {
            $this->eventData = (!empty($this->requestInterface->getContent())) ? json_decode($this->requestInterface->getContent(), true) : [];
        } catch (\Exception $e) {
            $this->novalnetLogger->error("Received data is not in the JSON format $e");
            $this->displayMessage("Received data is not in the JSON format $e");
        }

        // Get callback setting params (from shop admin)
        $this->testMode = $this->novalnetConfig->getMerchantScriptConfig('test_mode');
        $this->emailBody = '';

        // Check whether the IP address is authorized
        if (!$this->checkIP()) {
            return false;
        }

        if (empty($this->eventData)) {
            $this->displayMessage('No params passed over!');
            return false;
        }

        $this->response = new DataObject();
        $this->response->setData($this->eventData); // Assign response params to object data

        // Set Event data
        $this->eventType = $this->response->getData('event/type');
        $this->parentTid = !empty($this->response->getData('event/parent_tid'))
            ? $this->response->getData('event/parent_tid') : $this->response->getData('event/tid');
        $this->eventTid = $this->response->getData('event/tid');
        $this->orderNo = $this->response->getData('transaction/order_no');
        $this->order = $this->getOrder();
        if ($this->order === false) {
            return false;
        }
        $this->currentTime = $this->dateTime->date('d-m-Y H:i:s');
        $this->storeId = $this->order->getStoreId(); // Get order store id

        $this->paymentAccessKey = $this->novalnetConfig->getGlobalConfig('payment_access_key', $this->storeId);
        $this->payment = $this->order->getPayment(); // Get payment object
        $this->code = $this->payment->getMethodInstance()->getCode(); // Get payment method code
        $this->paymentTxnId = $this->payment->getLastTransId(); // Get payment last transaction id
        $this->currency = $this->order->getOrderCurrencyCode(); // Get order currency
        if (!$this->validateEventData()) {
            return false;
        }

        return true;
    }

    /**
     * Check whether the ip address is authorised
     *
     * @return boolean
     */
    private function checkIP()
    {
        $requestReceivedIp = $this->novalnetRequestHelper->getRequestIp();
        $novalnetHostIp = gethostbyname('pay-nn.de');

        if (!empty($novalnetHostIp)) {
            if (!$this->validateRequestIp($novalnetHostIp) && !$this->testMode) {
                $this->displayMessage(
                    __('Unauthorised access from the IP [ %1 ]', $requestReceivedIp)
                );

                return false;
            }
        } else {
            $this->displayMessage('Unauthorised access from the IP');
            return false;
        }

        return true;
    }

    /**
     * Validate the request ip with Novalnet host Ip
     *
     * @param string $novalnetHostIp
     * @return boolean
     */
    private function validateRequestIp($novalnetHostIp)
    {
        $serverVariables = $this->requestInterface->getServer();
        $remoteAddrHeaders = [
            'HTTP_X_FORWARDED_HOST',
            'HTTP_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($remoteAddrHeaders as $header) {
            if (property_exists($serverVariables, $header) === true) {
                if (in_array($header, ['HTTP_X_FORWARDED_HOST', 'HTTP_X_FORWARDED_FOR'])) {
                    $forwardedIps = (!empty($serverVariables[$header])) ? explode(",", $serverVariables[$header]) : [];
                    if (in_array($novalnetHostIp, $forwardedIps)) {
                        return true;
                    }
                }

                if ($serverVariables[$header] == $novalnetHostIp) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * Validate required parameter from the server request
     *
     * @return bool
     */
    private function validateEventData()
    {
        foreach ($this->mandatoryParams as $category => $parameters) {
            if (empty($this->response->getData($category))) {
                // Could be a possible manipulation in the notification data
                $this->displayMessage('Required parameter category(' . $category . ') not received');

                return false;
            } else {
                foreach ($parameters as $parameter) {
                    if (empty($this->response->getData($category . '/' . $parameter))) {
                        // Could be a possible manipulation in the notification data
                        $this->displayMessage(
                            'Required parameter(' . $parameter . ') in the category(' . $category . ') not received'
                        );

                        return false;
                    }
                }
            }
        }

        // Validate the received checksum.
        if (!$this->validateChecksum()) {
            return false;
        }

        // Validate TID's from the event data
        if (
            !empty($this->parentTid) && !preg_match('/^\d{17}$/', $this->parentTid)
        ) {
            $this->displayMessage(
                'Invalid TID[' . $this->parentTid
                . '] for Order :' . $this->response->getData('transaction/order_no')
            );

            return false;
        } elseif (!empty($this->eventTid) && !preg_match('/^\d{17}$/', $this->eventTid)) {
            $this->displayMessage(
                'Invalid TID[' . $this->eventTid
                . '] for Order :' . $this->response->getData('transaction/order_no')
            );

            return false;
        }

        return true;
    }

    /**
     * Validate checksum in response
     *
     * @return bool
     */
    private function validateChecksum()
    {
        $checksumString = $this->response->getData('event/tid') . $this->response->getData('event/type')
            . $this->response->getData('result/status');

        if (isset($this->response->getData('transaction')['amount'])) {
            $checksumString .= $this->response->getData('transaction/amount');
        }

        if ($this->response->getData('transaction/currency')) {
            $checksumString .= $this->response->getData('transaction/currency');
        }

        $accessKey = trim($this->paymentAccessKey);
        if (!empty($accessKey)) {
            $checksumString .= strrev($accessKey);
        }

        $generatedChecksum = hash('sha256', $checksumString);
        if ($generatedChecksum !== $this->response->getData('event/checksum')) {
            $this->displayMessage('While notifying some data has been changed. The hash check failed');

            return false;
        }

        return true;
    }

    /**
     * Get order reference.
     *
     * @return mixed
     */
    private function getOrder()
    {
        if ($this->orderNo) {
            $order = $this->salesOrderModel->loadByIncrementId($this->orderNo);
        }

        if (!isset($order) || empty($order->getIncrementId())) {
            $orderCollection = $this->transactionModel->getCollection()->addFieldToFilter('txn_id', $this->parentTid);
            if (!empty($orderCollection)) {
                $order = $orderCollection->getFirstItem()->getOrder();
            }
        }

        if (empty($order) || empty($order->getIncrementId())) {
            $this->displayMessage('Required (Transaction ID) not Found!');
            return false;
        }

        return $order;
    }

    /**
     * Complete the order in-case response failure from Novalnet server
     *
     * @return bool
     */
    private function handleCommunicationFailure()
    {
        if ($this->novalnetConfig->getPaymentType($this->code) != $this->response->getData('transaction/payment_type')) {
            $this->displayMessage(
                'Novalnet callback received. Payment type ( ' . $this->response->getData('transaction/payment_type')
                . ' ) is not matched with ' . $this->code . '!'
            );
            return false;
        }
        // Unhold order if it is being held
        if ($this->order->canUnhold()) {
            $this->order->unhold()->save();
        }

        // update and save the payment additional data
        $additionalData = $this->novalnetRequestHelper->buildAdditionalData($this->response, $this->payment);
        $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();

        $amount = $this->novalnetRequestHelper->getFormattedAmount(
            $this->response->getData('transaction/amount'),
            'RAW'
        );

        // Set order status based on Novalnet transaction status
        if (
            $this->response->getData('result/status') == 'SUCCESS' &&
            in_array($this->response->getData('transaction/status'), ['PENDING', 'ON_HOLD', 'CONFIRMED'])
        ) {
            if ($this->order->canInvoice() && $this->response->getData('transaction/status') == 'CONFIRMED') {
                // capture transaction
                $this->payment->setTransactionId($additionalData['NnTid'])
                    ->setLastTransId($additionalData['NnTid'])
                    ->capture(null)
                    ->setAmount($amount)
                    ->setIsTransactionClosed(false)
                    ->setShouldCloseParentTransaction(false)
                    ->save();
            } else {
                // authorize transaction
                $this->payment->authorize(true, $amount)->save();
            }

            $orderStatus = $this->getOrderStatus();
            $this->order->setState(Order::STATE_PROCESSING)
                ->addStatusToHistory($orderStatus, __('Customer successfully returned from Novalnet'))
                ->save();
            if (!empty($this->response->getData('transaction/payment_data/token'))) {
                $this->novalnetRequestHelper->savePaymentToken($this->order, $this->code, $this->response);
            }

            // Order email
            if ($this->order->getCanSendNewEmailFlag()) {
                try {
                    $this->orderEmailSender->send($this->order);
                    $invoice = current($this->order->getInvoiceCollection()->getItems());
                    if ($invoice) {
                        $this->invoiceSender->send($invoice);
                    }
                } catch (\Exception $e) {
                    $this->novalnetLogger->error($e);
                }
            }
        } else {
            // Cancel the order based on Novalnet transaction status
            $this->novalnetRequestHelper->saveCanceledOrder($this->response, $this->order);
            $this->displayMessage('Payment cancelled for the transaction ' . $this->eventTid);
        }

        $this->displayMessage('Novalnet Callback Script executed successfully on ' . $this->currentTime);
    }

    /**
     * Log callback transaction information
     *
     * @param  \Novalnet\Payment\Model\Callback $callbackModel
     * @param  float $amount
     * @param  string $orderNo
     * @return void
     */
    private function logCallbackInfo($callbackModel, $amount, $orderNo)
    {
        // Get the original/parent transaction id
        $callbackModel->setOrderId($orderNo)
            ->setCallbackAmount($amount)
            ->setReferenceTid($this->eventTid)
            ->setCallbackTid($this->parentTid)
            ->setCallbackDatetime($this->currentTime)
            ->save();
    }

    /**
     * Get payment order status for CREDIT Event
     *
     * @return void
     */
    private function getOrderStatusforCreditEvent()
    {
        if ($this->response->getData('transaction/status') == 'CONFIRMED') {
            $orderStatus = $this->novalnetConfig->getPaymentConfig($this->code, 'order_status_after_payment', $this->storeId);
        }

        $orderStatus = !empty($orderStatus) ? $orderStatus : Order::STATE_PROCESSING;
        $orderState = Order::STATE_PROCESSING;
        $this->order->setState(
            $orderState,
            true,
            __('Novalnet webhook set status (%1) for Order ID = %2', $orderState, $this->orderNo)
        );
        $this->order->addStatusToHistory(
            $orderStatus,
            __('Novalnet webhook added order status %1', $orderStatus)
        );
        $this->order->save();
    }

    /**
     * Get payment order status
     *
     * @return string
     */
    private function getOrderStatus()
    {
        $orderStatus = 'pending';
        if ($this->response->getData('transaction/status') == 'ON_HOLD') {
            $orderStatus = $this->novalnetConfig->getGlobalOnholdStatus($this->storeId);
        } elseif (
            $this->response->getData('transaction/status') == 'CONFIRMED' || $this->response->getData('transaction/status') == 'PENDING' && !$this->novalnetConfig->isRedirectPayment($this->code)
            && !in_array(
                $this->code,
                [
                    ConfigProvider::NOVALNET_INVOICE_INSTALMENT,
                    ConfigProvider::NOVALNET_SEPA_INSTALMENT,
                    ConfigProvider::NOVALNET_SEPA_GUARANTEE,
                    ConfigProvider::NOVALNET_INVOICE_GUARANTEE
                ]
            )
        ) {
            $orderStatus = $this->novalnetConfig->getPaymentConfig($this->code, 'order_status', $this->storeId);
        }

        return !empty($orderStatus) ? $orderStatus : Order::STATE_PROCESSING;
    }

    /**
     * Show callback process transaction comments
     *
     * @param  string  $text
     * @param  boolean $pullOut
     * @return void
     */
    private function displayMessage($text, $pullOut = true)
    {
        if ($pullOut === false) {
            $this->additionalMessage = $text;
        } else {
            $this->callbackMessage = $text;
        }

        $this->novalnetLogger->notice($text);
    }

    /**
     * Send callback notification E-mail
     *
     * @return bool
     */
    private function sendCallbackMail()
    {
        // Get email configuration settings
        $this->getEmailConfig();

        if ($this->emailBody && $this->emailFromAddr && $this->emailToAddr) {
            if (!$this->sendEmailMagento()) {
                $this->displayMessage('Mailing failed!' . '<br>', false);
                return false;
            }
        }
    }

    /**
     * Get email config
     *
     * @return void
     */
    private function getEmailConfig()
    {
        $this->storeFrontendName = $this->storeManager->getStore()->getFrontendName();
        $this->emailFromAddr = $this->getConfigValue('trans_email/ident_general/email');
        $this->emailFromName = $this->getConfigValue('trans_email/ident_general/name');
        $this->emailToAddr = $this->novalnetConfig->getMerchantScriptConfig('mail_to_addr');
        $this->emailToName = 'store admin'; // Adapt for your need
        $this->emailSubject = 'Novalnet Callback Script Access Report - ' . $this->storeFrontendName;
    }

    /**
     * Send callback notification E-mail (with callback template)
     *
     * @return boolean
     */
    private function sendEmailMagento()
    {
        try {
            $emailToAddrs = str_replace(' ', '', $this->emailToAddr);
            $emailToAddrs = (!empty($emailToAddrs)) ? explode(',', $emailToAddrs) : [];
            $templateVars = [
                'fromName' => $this->emailFromName,
                'fromEmail' => $this->emailFromAddr,
                'toName' => $this->emailToName,
                'toEmail' => $this->emailToAddr,
                'subject' => $this->emailSubject
            ];

            if (
                !empty($this->emailBody) &&
                is_object($this->emailBody) &&
                $this->emailBody instanceof \Magento\Framework\Phrase
            ) {
                $templateVars['body'] = $this->emailBody->render();
            } else {
                $templateVars['body'] = $this->emailBody;
            }

            $from = ['email' => $this->emailFromAddr, 'name' => $this->emailFromName];
            $this->inlineTranslation->suspend();

            $storeId = $this->storeManager->getStore()->getId();
            $templateOptions = [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $storeId
            ];
            $transport = $this->transportBuilder->setTemplateIdentifier(
                'novalnet_callback_email_template',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )->setTemplateOptions($templateOptions)
                ->setTemplateVars($templateVars)
                ->setFrom($from)
                ->addTo($emailToAddrs)
                ->getTransport();

            $transport->sendMessage();
            $this->inlineTranslation->resume();
            $this->displayMessage(__FUNCTION__ . ': Sending Email succeeded! <br>', false);
        } catch (\Exception $e) {
            $this->novalnetLogger->error("Email sending failed: $e");
            $this->displayMessage('Email sending failed: ', false);
            return false;
        }

        return true;
    }

    /**
     * Retrieves Novalnet configuration values
     *
     * @param  string $path
     * @return string
     */
    private function getConfigValue($path)
    {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Capture transaction
     *
     * @return void
     */
    private function transactionCapture()
    {
        $invoiceDuedate = $this->response->getData('transaction/due_date');
        $additionalData = [];
        if (!empty($this->payment->getAdditionalData())) {
            $additionalData = $this->novalnetRequestHelper->isSerialized($this->payment->getAdditionalData())
                ? $this->serializer->unserialize($this->payment->getAdditionalData())
                : json_decode($this->payment->getAdditionalData(), true);
        }

        $transactionStatus = !empty($additionalData['NnStatus'])
            ? $this->novalnetRequestHelper->getStatus($additionalData['NnStatus'], $this->order) : '';

        if (
            $this->order->canInvoice() &&
            in_array($transactionStatus, ['ON_HOLD', 'PENDING'])
        ) {
            if ($invoiceDuedate && $this->response->getData('result/status') == 'SUCCESS') {
                $formatDate = $this->timeZone->formatDate($invoiceDuedate, \IntlDateFormatter::LONG);
                if (isset($additionalData['NnInvoiceComments'])) {
                    $note = (!empty($additionalData['NnInvoiceComments'])) ? explode('|', $additionalData['NnInvoiceComments']) : [];
                    $additionalData['NnInvoiceComments'] = (!empty($note)) ? implode('|', $note) : '';
                    $additionalData['NnDueDate'] = $formatDate;
                }
                if ($this->code == ConfigProvider::NOVALNET_CASHPAYMENT) {
                    $additionalData['CpDueDate'] = $formatDate;
                }
                $additionalData['dueDateUpdateAt'] = $this->dateTime->date('d-m-Y H:i:s');
                $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();
            }

            $this->emailBody = $message = __(
                'The transaction has been confirmed on %1',
                $this->currentTime
            );
            $additionalData['NnStatus'] = $this->response->getData('transaction/status');
            $additionalData['NnComments'] = empty($additionalData['NnComments']) ?
                '<br>' . $message . '<br>' : $additionalData['NnComments'] . '<br>' . $message . '<br>';

            if (
                in_array(
                    $this->payment->getMethod(),
                    [ConfigProvider::NOVALNET_INVOICE_INSTALMENT, ConfigProvider::NOVALNET_SEPA_INSTALMENT]
                )
            ) {
                $additionalData = $this->getInstalmentAdditionalData($additionalData);
            }

            $transactionStatus = $this->transactionStatusModel->loadByAttribute($this->parentTid, 'tid');
            $transactionStatus->setStatus($additionalData['NnStatus'])->save();
            // Capture the Authorized transaction
            $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))
                ->capture(null)->save();

            $orderStatus = $this->getOrderStatus();
            $this->order->setState(Order::STATE_PROCESSING)
                ->addStatusToHistory($orderStatus, 'The transaction has been confirmed')
                ->save();
            $invoice = current($this->order->getInvoiceCollection()->getItems());
            if ($invoice) {
                $this->invoiceSender->send($invoice);
            }
        } else {
            // transaction already captured or transaction not been authorized.
            $message = 'Order already captured.';
        }

        $this->sendCallbackMail();
        $this->displayMessage($message);
    }

    /**
     * Get Instalment payment Additional data
     *
     * @param  array $additionalData
     * @return array $additionalData
     */
    private function getInstalmentAdditionalData($additionalData)
    {
        $additionalData['PaidInstall'] = $this->response->getData('instalment/cycles_executed');
        $additionalData['DueInstall'] = $this->response->getData('instalment/pending_cycles');
        $additionalData['NextCycle'] = $this->response->getData('instalment/next_cycle_date');
        if ($futureInstalmentDates = $this->response->getData('instalment/cycle_dates')) {
            foreach (array_keys($futureInstalmentDates) as $cycle) {
                $additionalData['InstalmentDetails'][$cycle] = [
                    'amount' => $additionalData['InstallPaidAmount'],
                    'nextCycle' => !empty($futureInstalmentDates[$cycle + 1]) ? date('Y-m-d', strtotime($futureInstalmentDates[$cycle + 1])) : '',
                    'paidDate' => ($cycle == 1) ? date('Y-m-d') : '',
                    'status' => ($cycle == 1) ? 'Paid' : 'Pending',
                    'reference' => ($cycle == 1) ? $this->response->getData('transaction/tid') : ''
                ];
            }
        }
        return $additionalData;
    }

    /**
     * Check transaction cancellation
     *
     * @return void
     */
    private function transactionCancellation()
    {
        $transactionStatus = $this->transactionStatusModel->loadByAttribute($this->parentTid, 'tid');

        $this->novalnetRequestHelper->saveCanceledOrder($this->response, $this->order, $this->response->getData('transaction/status'));
        $this->emailBody = $message = __(
            'The transaction has been canceled on %1',
            $this->currentTime
        );

        $additionalData = [];
        if (!empty($this->payment->getAdditionalData())) {
            $additionalData = $this->novalnetRequestHelper->isSerialized($this->payment->getAdditionalData())
                ? $this->serializer->unserialize($this->payment->getAdditionalData())
                : json_decode($this->payment->getAdditionalData(), true);
        }

        $additionalData['NnComments'] = empty($additionalData['NnComments'])
            ? '<br>' . $message : $additionalData['NnComments'] . '<br><br>' . $message;
        $additionalData['NnStatus'] = $this->response->getData('transaction/status');
        $transactionStatus->setStatus($additionalData['NnStatus'])->save();
        $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();
        $this->sendCallbackMail();
        $this->displayMessage($message);
    }

    /**
     * Handle payment refund/bookback process
     *
     * @return void
     */
    private function refundProcess()
    {
        $refundTid = empty($this->response->getData('transaction/refund/tid'))
            ? $this->response->getData('transaction/tid') : $this->response->getData('transaction/refund/tid');
        $refundAmount = empty($this->response->getData('transaction/refund/amount'))
            ? $this->response->getData('transaction/amount') : $this->response->getData('transaction/refund/amount');
        $this->emailBody = $message = __(
            'Refund has been initiated for the TID: %1 with the amount %2. New TID:%3',
            $this->parentTid,
            $this->novalnetRequestHelper->getFormattedAmount($refundAmount, 'RAW') . ' ' . $this->currency,
            $refundTid
        );
        $shopInvoked = !empty($this->response->getData('custom/shop_invoked'))
            ? $this->response->getData('custom/shop_invoked') : 0;

        if (!$shopInvoked) {
            $additionalData = [];
            if (!empty($this->payment->getAdditionalData())) {
                $additionalData = $this->novalnetRequestHelper->isSerialized($this->payment->getAdditionalData())
                    ? $this->serializer->unserialize($this->payment->getAdditionalData())
                    : json_decode($this->payment->getAdditionalData(), true);
            }

            $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message :
                $additionalData['NnComments'] . '<br><br>' . $message;
            $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();
        }

        if ($this->order->getState() != Order::STATE_CLOSED && $this->order->canCreditmemo() && !$shopInvoked) {
            $refundData = [];
            $grandTotal = $this->novalnetRequestHelper->getFormattedAmount($this->order->getGrandTotal());
            $totalRefunded = $this->novalnetRequestHelper->getFormattedAmount($this->order->getTotalOnlineRefunded());
            $totalRefundNow = $totalRefunded + $refundAmount;
            if ($totalRefunded >= $grandTotal || $refundAmount >= $grandTotal) {
                $adjustmentNegative = $grandTotal - $refundAmount;
                $refundData['adjustment_negative'] = $this->novalnetRequestHelper->getFormattedAmount($adjustmentNegative, 'RAW');
                $creditmemo = $this->creditMemoFacory->createByOrder($this->order, $refundData);
                $this->creditmemoService->refund($creditmemo);
            } elseif ($totalRefunded < $grandTotal && $totalRefundNow <= $grandTotal) {
                $refundData['adjustment_positive'] = ($this->novalnetRequestHelper->getFormattedAmount($refundAmount, 'RAW'));
                $itemToCredit = [];
                foreach ($this->order->getAllItems() as $item) {
                    $itemToCredit[$item->getId()] = ['qty' => 0];
                }
                $refundData['adjustment_negative'] = 0;
                $refundData['shipping_amount'] = 0;
                $refundData['items'] = $itemToCredit;
                $this->creditmemoLoader->setOrderId($this->order->getId()); //pass order id
                $this->creditmemoLoader->setCreditmemo($refundData);
                $creditmemo = $this->creditmemoLoader->load();
                $this->creditmemoService->refund($creditmemo);
            }
        }

        $this->sendCallbackMail();
        $this->displayMessage($message);
    }

    /**
     * Handle transaction update
     *
     * @return void
     */
    private function transactionUpdate()
    {
        $invoiceDuedate = $this->response->getData('transaction/due_date');
        $transactionPaymentType = $this->response->getData('transaction/payment_type');
        $transaction = $this->transactionStatusModel->loadByAttribute($this->parentTid, 'tid');
        $message = "Novalnet callback received for the unhandled transaction type($transactionPaymentType) for $this->eventType EVENT";
        $additionalData = [];
        if (!empty($this->payment->getAdditionalData())) {
            $additionalData = $this->novalnetRequestHelper->isSerialized($this->payment->getAdditionalData())
                ? $this->serializer->unserialize($this->payment->getAdditionalData())
                : json_decode($this->payment->getAdditionalData(), true);
        }
        $transactionStatus = $this->novalnetRequestHelper->getStatus($transaction->getStatus(), $this->order);

        if ($invoiceDuedate && $this->response->getData('result/status') == 'SUCCESS') {
            $formatDate = $this->timeZone->formatDate($invoiceDuedate, \IntlDateFormatter::LONG);
            $nnAmount = $this->pricingHelper->currency(
                $this->novalnetRequestHelper->getFormattedAmount(($this->response->getData('instalment/cycle_amount') ? $this->response->getData('instalment/cycle_amount')
                    : $this->response->getData('transaction/amount')), 'RAW'),
                true,
                false
            );
            $additionalData['NnAmount'] = $nnAmount;
            $this->emailBody = $message = __(
                'The transaction has been updated with amount %1 and due date with %2',
                $additionalData['NnAmount'],
                $formatDate
            );
            if (isset($additionalData['NnInvoiceComments'])) {
                $note = (!empty($additionalData['NnInvoiceComments'])) ? explode('|', $additionalData['NnInvoiceComments']) : [];
                $additionalData['NnInvoiceComments'] = (!empty($note)) ? implode('|', $note) : '';
                $additionalData['NnDueDate'] = $formatDate;
            }
            if ($this->code == ConfigProvider::NOVALNET_CASHPAYMENT) {
                $additionalData['CpDueDate'] = $formatDate;
                $this->emailBody = $message = __(
                    'The transaction has been updated with amount %1 and slip expiry date with %2',
                    $additionalData['NnAmount'],
                    $formatDate
                );
            }
            $additionalData['dueDateUpdateAt'] = $this->dateTime->date('d-m-Y H:i:s');
            $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();
        }

        if ($transactionStatus == 'PENDING') {
            if ($this->response->getData('transaction/status') == 'ON_HOLD') {
                $orderStatus = $this->novalnetConfig->getGlobalOnholdStatus($this->storeId);
                $this->emailBody = $message = __(
                    'The transaction status has been changed from pending to on hold for the TID: %1 on %2.',
                    $this->parentTid,
                    $this->currentTime
                );
                $additionalData['NnStatus'] = $this->response->getData('transaction/status');
                $additionalData['NnComments'] = empty($additionalData['NnComments']) ?
                    '<br>' . $message . '<br>' : $additionalData['NnComments'] . '<br>' . $message . '<br>';
                $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();
            } elseif ($this->response->getData('transaction/status') == 'CONFIRMED') {
                if (
                    in_array(
                        $this->payment->getMethod(),
                        [ConfigProvider::NOVALNET_INVOICE_INSTALMENT, ConfigProvider::NOVALNET_SEPA_INSTALMENT]
                    )
                ) {
                    $additionalData = $this->getInstalmentAdditionalData($additionalData);
                }
                $this->emailBody = $message = __(
                    'Transaction updated successfully for the TID: %1 with the amount %2 on %3',
                    $this->eventTid,
                    $amount = $this->novalnetRequestHelper->getFormattedAmount(
                        $this->response->getData('transaction/amount'),
                        'RAW'
                    ) . ' ' . $this->currency,
                    $this->currentTime
                );

                $additionalData['NnStatus'] = $this->response->getData('transaction/status');
                $additionalData['NnComments'] = empty($additionalData['NnComments']) ?
                    '<br>' . $message . '<br>' : $additionalData['NnComments'] . '<br>' . $message . '<br>';
                $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();
                $this->payment->setTransactionId($this->parentTid . '-capture')
                    ->setIsTransactionClosed(true)
                    ->capture(null)->save();
                $orderStatus = $this->novalnetConfig->getPaymentConfig($this->code, 'order_status', $this->storeId);
            }

            $transaction->setStatus($this->response->getData('transaction/status'))->save();
            if (!empty($orderStatus)) {
                $this->order->setState(Order::STATE_PROCESSING)
                    ->setStatus($orderStatus)
                    ->save();
            }
            $invoice = current($this->order->getInvoiceCollection()->getItems());
            if ($invoice && $this->response->getData('transaction/status') == 'CONFIRMED') {
                $this->invoiceSender->send($invoice);
            }
        } elseif (
            $transactionStatus == 'ON_HOLD' && $this->response->getData('transaction/status') == 'ON_HOLD' &&
            in_array($this->code, [ConfigProvider::NOVALNET_SEPA, ConfigProvider::NOVALNET_SEPA_GUARANTEE, ConfigProvider::NOVALNET_INVOICE_GUARANTEE, ConfigProvider::NOVALNET_PREPAYMENT])
        ) {
            $this->emailBody = $message = __(
                'Transaction updated successfully for the TID: %1 with the amount %2 on %3',
                $this->eventTid,
                $amount = $this->novalnetRequestHelper->getFormattedAmount(
                    $this->response->getData('transaction/amount'),
                    'RAW'
                ) . ' ' . $this->currency,
                $this->currentTime
            );
            $additionalData['NnComments'] = empty($additionalData['NnComments']) ?
                '<br>' . $message . '<br>' : $additionalData['NnComments'] . '<br>' . $message . '<br>';
            $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();
        }

        $this->sendCallbackMail();
        $this->displayMessage($message);
    }

    /**
     * Handle payment credit process
     *
     * @return void
     */
    private function creditProcess()
    {
        $transactionPaymentType = $this->response->getData('transaction/payment_type');
        $message = "Novalnet callback received for the unhandled transaction type($transactionPaymentType) for $this->eventType EVENT";
        $amount = $this->novalnetRequestHelper->getFormattedAmount(
            $this->response->getData('transaction/amount'),
            'RAW'
        );

        if (
            in_array(
                $transactionPaymentType,
                ['INVOICE_CREDIT', 'ONLINE_TRANSFER_CREDIT', 'CASHPAYMENT_CREDIT', 'MULTIBANCO_CREDIT']
            )
        ) {
            $additionalData = [];
            if (!empty($this->payment->getAdditionalData())) {
                $additionalData = $this->novalnetRequestHelper->isSerialized($this->payment->getAdditionalData())
                    ? $this->serializer->unserialize($this->payment->getAdditionalData())
                    : json_decode($this->payment->getAdditionalData(), true);
            }
            $updatedAmount = str_replace(',', '.', $this->currencyModel->format($additionalData['NnAmount'], ['display' => \Magento\Framework\Currency::NO_SYMBOL], false));
            $updatedAmount = $this->novalnetRequestHelper->getFormattedAmount($updatedAmount);
            // Loads callback model using the Increment ID
            $callbackInfo = $this->callbackModel->loadLogByOrderId($this->orderNo);
            $transactionStatus = $this->transactionStatusModel->loadByAttribute($this->parentTid, 'tid');
            $totalAmount = $this->response->getData('transaction/amount') + $callbackInfo->getCallbackAmount();
            // Get original order amount
            $grandTotal = $this->novalnetRequestHelper->getFormattedAmount($this->order->getGrandTotal());
            $totalAmountRefunded = $this->novalnetRequestHelper->getFormattedAmount($this->order->getTotalRefunded());
            // Log callback data for reference
            $this->logCallbackInfo($callbackInfo, $totalAmount, $this->orderNo);
            $message = __(
                'Credit has been successfully received for the TID: %1 with amount %2 on %3. Please refer PAID order details in our Novalnet Admin Portal for the TID: %4',
                $this->parentTid,
                $amount . ' ' . $this->currency,
                $this->currentTime,
                $this->eventTid
            );

            $additionalData['NnStatus'] = ($this->response->getData('transaction/status'))
                ? $this->response->getData('transaction/status') : '';
            $transactionStatus->setStatus($this->response->getData('transaction/status'))->save();
            $currentGrandTotal = 0;
            if ($totalAmountRefunded) {
                $currentGrandTotal = $grandTotal - $totalAmountRefunded;
                if ($totalAmount >= $currentGrandTotal) {
                    $additionalData['NnPaid'] = 1;
                }
            } elseif ($totalAmount >= $grandTotal) {
                $additionalData['NnPaid'] = 1;
            }
            if (
                ($totalAmount < $grandTotal) ||
                $transactionPaymentType == 'ONLINE_TRANSFER_CREDIT'
            ) {
                if ($transactionPaymentType == 'ONLINE_TRANSFER_CREDIT' && ($totalAmount > $grandTotal)) {
                    $message = $message . '<br>' . __(
                        'Credit has been successfully received for the TID: %1 with amount %2 on %3. Please refer PAID order details in our Novalnet Admin Portal for the TID: %4',
                        $this->parentTid,
                        $amount . ' ' . $this->currency,
                        $this->currentTime,
                        $this->eventTid
                    );
                }

                $this->emailBody = $message;
                $additionalData['NnComments'] = empty($additionalData['NnComments'])
                    ? '<br>' . $message
                    : $additionalData['NnComments'] . '<br><br>' . $message;
                $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();
                if ($totalAmountRefunded) {
                    if (($totalAmount >= $currentGrandTotal)) {
                        $this->getOrderStatusforCreditEvent(); // Set order status
                    }
                } elseif ($updatedAmount) {
                    if (($totalAmount >= $updatedAmount)) {
                        $this->getOrderStatusforCreditEvent(); // Set order status
                    }
                }
            } elseif ($this->order->canInvoice()) {
                $this->emailBody = $message;
                $additionalData['NnComments'] = empty($additionalData['NnComments'])
                    ? '<br>' . $message
                    : $additionalData['NnComments'] . '<br><br>' . $message;
                $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();
                // Save payment information with invoice for Novalnet successful transaction
                $this->payment->setTransactionId($this->parentTid . '-capture')
                    ->setIsTransactionClosed(true)
                    ->capture(null)->save();
                $this->order->save();
                $this->order->setPayment($this->payment)->save();
                $invoice = $this->order->getInvoiceCollection()->getFirstItem();
                if ($invoice) {
                    $this->invoiceSender->send($invoice);
                }
                $this->getOrderStatusforCreditEvent(); // Set order status
            } elseif ($this->payment->getAdditionalInformation($this->code . '_callbackSuccess') != 1) {
                $this->emailBody = $message;
                $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message
                    : $additionalData['NnComments'] . '<br><br>' . $message;
                $this->payment->setAdditionalInformation($this->code
                    . '_callbackSuccess', 1);
                $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();
                $orderStatus = $this->getOrderStatusforCreditEvent(); // Set order status
                $this->order->setPayment($this->payment)->save();

                $invoice = $this->order->getInvoiceCollection()->getFirstItem();
                $invoice->setState(\Magento\Sales\Model\Order\Invoice::STATE_PAID);
                $invoice->save();
            } else {
                $message = 'Callback Script executed already. Refer Order :' . $this->orderNo;
            }
        } else {
            $message = __(
                'Credit has been successfully received for the TID: %1 with amount %2 on %3. Please refer PAID order details in our Novalnet Admin Portal for the TID: %4',
                $this->parentTid,
                $amount . ' ' . $this->currency,
                $this->currentTime,
                $this->eventTid
            );
            $additionalData = [];
            if (!empty($this->payment->getAdditionalData())) {
                $additionalData = $this->novalnetRequestHelper->isSerialized($this->payment->getAdditionalData())
                    ? $this->serializer->unserialize($this->payment->getAdditionalData())
                    : json_decode($this->payment->getAdditionalData(), true);
            }
            $this->emailBody = $message;
            $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message
                : $additionalData['NnComments'] . '<br><br>' . $message;
            $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();
        }

        $this->sendCallbackMail();
        $this->displayMessage($message);
    }

    /**
     * Handle payment INSTALMENT process
     *
     * @return void
     */
    private function instalmentProcess()
    {
        $additionalData = [];
        if (!empty($this->payment->getAdditionalData())) {
            $additionalData = $this->novalnetRequestHelper->isSerialized($this->payment->getAdditionalData())
                ? $this->serializer->unserialize($this->payment->getAdditionalData())
                : json_decode($this->payment->getAdditionalData(), true);
        }

        $additionalData['NnTid'] = $this->response->getData('transaction/tid');
        $instalmentTransactionAmount = $this->novalnetRequestHelper->getFormattedAmount(
            $this->response->getData('instalment/cycle_amount'),
            'RAW'
        );
        $paidAmount = $additionalData['InstallPaidAmount'] + $instalmentTransactionAmount;
        $additionalData['InstallPaidAmount'] = $paidAmount;
        $additionalData['PaidInstall'] = $this->response->getData('instalment/cycles_executed');
        $additionalData['DueInstall'] = $this->response->getData('instalment/pending_cycles');
        $additionalData['NextCycle'] = $this->response->getData('instalment/next_cycle_date');
        if ($this->response->getData('instalment/prepaid')) {
            $additionalData['prepaid'] = $this->response->getData('instalment/prepaid');
        } else {
            if (isset($additionalData['prepaid'])) {
                unset($additionalData['prepaid']);
            }
        }
        $additionalData['InstallCycleAmount'] = $instalmentTransactionAmount;
        $additionalData['NnAmount'] = $this->pricingHelper->currencyByStore($instalmentTransactionAmount, $this->order->getStore()->getStoreId(), true, false);

        if ($this->response->getData('transaction/payment_type') == 'INSTALMENT_INVOICE') {
            $note = (!empty($additionalData['NnInvoiceComments'])) ? explode('|', $additionalData['NnInvoiceComments']) : [];
            if ($this->response->getData('transaction/due_date')) {
                $formatDate = $this->timeZone->formatDate(
                    $this->response->getData('transaction/due_date'),
                    \IntlDateFormatter::LONG
                );
                $additionalData['NnDueDate'] = $formatDate;
            }

            $note[5] = 'InvoiceAmount:' . $this->pricingHelper->currencyByStore(
                $instalmentTransactionAmount,
                $this->order->getStore()->getStoreId(),
                true,
                false
            );

            $note[6] = 'Payment Reference:' . $this->response->getData('transaction/tid');
            if (isset($note[7])) {
                unset($note[7]);
            }
            $additionalData['NnInvoiceComments'] = (!empty($note)) ? implode('|', $note) : '';
        }

        $additionalData['InstalmentDetails'][$this->response->getData('instalment/cycles_executed')] = [
            'amount' => $instalmentTransactionAmount,
            'nextCycle' => $this->response->getData('instalment/next_cycle_date'),
            'paidDate' => date('Y-m-d'),
            'status' => 'Paid',
            'reference' => $this->response->getData('transaction/tid')
        ];
        $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();

        // Send instalment email to end customer
        if (!$this->novalnetConfig->getGlobalConfig('instalment_mail', $this->storeId)) {
            $this->sendInstalmentmail();
        }

        $this->displayMessage('Novalnet Callbackscript received. Instalment payment executed properly');
    }

    /**
     * Handle payment INSTALMENT process
     *
     * @return void
     */
    private function instalmentCancelProcess()
    {
        $this->emailBody = $message = __(
            'Instalment has been cancelled for the TID %1 on %2',
            $this->parentTid,
            $this->currentTime
        );

        $additionalData = (!empty($this->payment->getAdditionalData())) ? ($this->novalnetRequestHelper->isSerialized($this->payment->getAdditionalData())
            ? $this->serializer->unserialize($this->payment->getAdditionalData())
            : json_decode($this->payment->getAdditionalData(), true)) : [];

        $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message :
            $additionalData['NnComments'] . '<br><br>' . $message;
        $additionalData['InstalmentCancel'] = 1;
        $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();
        $this->order->setState(Order::STATE_CLOSED)->setStatus(Order::STATE_CLOSED)->save();

        $this->sendCallbackMail();
        $this->displayMessage($message);
    }

    /**
     * Send Instalment mail
     *
     * @return mixed
     */
    private function sendInstalmentmail()
    {
        $this->getEmailConfig();
        $from = ['email' => $this->emailFromAddr, 'name' => $this->emailFromName];
        $additionalData = $this->order->getPayment()->getAdditionalData();
        if (!empty($additionalData)) {
            $additionalData = $this->novalnetRequestHelper->isSerialized($additionalData)
                ? $this->serializer->unserialize($additionalData)
                : json_decode($additionalData, true);
        }

        $templateVars = [
            'order' => $this->order,
            'orderNo' => $this->orderNo,
            'storeName' => $this->storeFrontendName,
            'order_id' => $this->order->getId(),
            'customer_name' => $this->order->getCustomerName(),
            'cycleAmount' => $this->novalnetRequestHelper->getFormattedAmount($this->response->getData('instalment/cycle_amount'), 'RAW'),
            'currency' => $this->currency,
            'formattedShippingAddress' => !empty($this->order->getShippingAddress()) ? $this->salesOrderAddressRenderer->format($this->order->getShippingAddress(), 'html') : '',
            'formattedBillingAddress' => $this->salesOrderAddressRenderer->format($this->order->getBillingAddress(), 'html'),
            'store' => $this->order->getStore(),
            'payment_html' => $this->paymentHelper->getInfoBlockHtml(
                $this->order->getPayment(),
                $this->order->getStore()->getStoreId()
            ),
            'sepaPayment' => ((!isset($additionalData['prepaid']) || empty($additionalData['prepaid'])) && ($this->response->getData('transaction/payment_type') == 'INSTALMENT_DIRECT_DEBIT_SEPA')) ? 1 : ""
        ];

        try {
            $this->inlineTranslation->suspend();
            $templateOptions = [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $this->order->getStore()->getStoreId()
            ];

            $transport = $this->transportBuilder->setTemplateIdentifier(
                'novalnet_callback_instalment_email_template',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )->setTemplateOptions($templateOptions)
                ->setTemplateVars($templateVars)
                ->setFrom($from)
                ->addTo($this->order->getCustomerEmail())
                ->getTransport();

            $transport->sendMessage();
            $this->inlineTranslation->resume();
            $this->displayMessage(__FUNCTION__ . ': Sending Email succeeded!' . '<br>', false);
        } catch (\Exception $e) {
            $this->novalnetLogger->error("Email sending failed: $e");
            $this->displayMessage('Email sending failed: ', false);
            return false;
        }
    }

    /**
     * Handle payment CHARGEBACK/RETURN_DEBIT/REVERSAL process
     *
     * @return void
     */
    private function chargebackProcess()
    {
        // Update callback comments for Chargebacks
        $this->emailBody = $message = __(
            'Chargeback executed successfully for the TID: %1 amount: %2 on %3. The subsequent TID: %4',
            $this->parentTid,
            $this->novalnetRequestHelper->getFormattedAmount(
                $this->response->getData('transaction/amount'),
                'RAW'
            ) . ' ' . $this->currency,
            $this->currentTime,
            $this->response->getData('transaction/tid')
        );

        $additionalData = [];
        if (!empty($this->payment->getAdditionalData())) {
            $additionalData = $this->novalnetRequestHelper->isSerialized($this->payment->getAdditionalData())
                ? $this->serializer->unserialize($this->payment->getAdditionalData())
                : json_decode($this->payment->getAdditionalData(), true);
        }

        $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message :
            $additionalData['NnComments'] . '<br><br>' . $message;
        $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();

        $this->sendCallbackMail();
        $this->displayMessage($message);
    }

    /**
     * Handle payment reminder process
     *
     * @return void
     */
    private function paymentReminderProcess()
    {
        $reminderCount = explode('_', $this->response->getData('event/type'));
        $reminderCount = end($reminderCount);
        $this->emailBody = $message = __(
            'Payment Reminder %1 has been sent to the customer.',
            $reminderCount
        );

        $additionalData = [];
        if (!empty($this->payment->getAdditionalData())) {
            $additionalData = $this->novalnetRequestHelper->isSerialized($this->payment->getAdditionalData())
                ? $this->serializer->unserialize($this->payment->getAdditionalData())
                : json_decode($this->payment->getAdditionalData(), true);
        }

        $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message :
            $additionalData['NnComments'] . '<br><br>' . $message;
        $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();

        $this->sendCallbackMail();
        $this->displayMessage($message);
    }

    /**
     * Handle collection process
     *
     * @return void
     */
    private function collectionProcess()
    {
        $this->emailBody = $message = __(
            'The transaction has been submitted to the collection agency. Collection Reference: %1',
            $this->response->getData('collection/reference')
        );

        $additionalData = [];
        if (!empty($this->payment->getAdditionalData())) {
            $additionalData = $this->novalnetRequestHelper->isSerialized($this->payment->getAdditionalData())
                ? $this->serializer->unserialize($this->payment->getAdditionalData())
                : json_decode($this->payment->getAdditionalData(), true);
        }

        $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message :
            $additionalData['NnComments'] . '<br><br>' . $message;
        $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();

        $this->sendCallbackMail();
        $this->displayMessage($message);
    }

    /**
     * Get Cart Contents
     *
     * @api
     * @return string
     */
    public function getCart()
    {
        $quote = $this->cart->getQuote();

        try {
            $result = $this->getCartItems($quote);
            return $this->jsonHelper->jsonEncode($result);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }
    }

    /**
     * Get express checkout request params for Product page
     *
     * @api
     * @param string[] $data
     * @return string
     */
    public function getProductPageParams($data)
    {
        $product = $this->loadProductById($data['product_id']);

        if (!$product || !$product->getId()) {
            return [];
        }

        $quote = $this->cart->getQuote();
        $updateRequired = true;
        if ($quote->getItemsCount()) {
            $items = $quote->getAllVisibleItems();
            foreach ($items as $item) {
                if ($item->getProduct()->getId() == $product->getId()) {
                    $updateRequired = false;
                }
            }
        }

        $params = $this->getCartItems($quote, 'product_page');
        $amount = $params['total']['amount'];
        $items = $params['displayItems'];

        if ($updateRequired) {
            $shouldInclTax = $this->shouldCartPriceInclTax($quote->getStore());
            $price = $this->getProductDataPrice(
                $product,
                $product->getFinalPrice(),
                $shouldInclTax,
                $quote->getCustomerId(),
                $quote->getStore()->getStoreId()
            );

            $productTotal = $this->novalnetRequestHelper->getFormattedAmount($price);
            $productTotal = ($productTotal) ? $productTotal : 0;
            $amount += $productTotal;

            $productLabel = $product->getName();
            $formattedPrice = $this->priceCurrency->format($price, false);
            $productLabel .= sprintf(' (%s x %s)', '1', $formattedPrice);

            $items[] = [
                'label' => $productLabel,
                'type' => 'SUBTOTAL',
                'amount' => (string) $productTotal
            ];
        }

        $result = [
            'total' => [
                'amount' => (string) $amount
            ],
            'displayItems' => $items,
            'isEnabled' => $params['isEnabled'],
            'is_pending' => $params['is_pending'],
            'sheetConfig' => $params['sheetConfig'],
            'isVirtual' => ($quote->getItemsCount()) ? $quote->getIsVirtual() : null
        ];

        return $this->jsonHelper->jsonEncode($result);
    }

    /**
     * Add to Cart
     *
     * @api
     * @param string $data
     * @return string
     */
    public function addToCart($data)
    {
        try {
            $this->laminasUri->setQuery($data);
            $productInfo = $this->laminasUri->getQueryAsArray();
            $productId = $productInfo['product'];
            $relatedProduct = $productInfo['related_product'];

            if (isset($productInfo['qty'])) {
                $filter = new \Magento\Framework\Filter\LocalizedToNormalized(
                    ['locale' => $this->novalnetRequestHelper->getDefaultLanguage()]
                );
                $productInfo['qty'] = $filter->filter($productInfo['qty']);
            }

            $quote = $this->cart->getQuote();

            $storeId = $this->storeManager->getStore()->getId();
            $product = $this->productRepository->getById($productId, false, $storeId);
            $isNewItem = true;

            foreach ($quote->getAllItems() as $item) {
                if ($item->getProductId() == $productId) {
                    // update existing item in cart
                    $item = $this->cart->updateItem($item->getId(), $productInfo);
                    if ($item->getHasError()) {
                        throw new LocalizedException(__($item->getMessage()));
                    }

                    $isNewItem = false;
                    break;
                }
            }

            if ($isNewItem) {
                // add new item to cart
                $item = $this->cart->addProduct($product, $productInfo);
                if ($item->getHasError()) {
                    throw new LocalizedException(__($item->getMessage()));
                }

                if (!empty($relatedProduct)) {
                    $this->cart->addProductsByIds(explode(',', $relatedProduct));
                }
            }

            $this->cart->save();
            $quote->setTotalsCollectedFlag(false)->collectTotals()->save();

            return $this->jsonHelper->jsonEncode([]);

        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }
    }

    /**
     * Estimate Shipping by Address
     *
     * @api
     * @param string[] $address
     * @return string
     */
    public function estimateShippingMethod($address)
    {
        try {
            $quote = $this->cart->getQuote();
            $needConversion = ($this->storeManager->getStore()->getCurrentCurrencyCode() == $this->storeManager->getStore()->getBaseCurrencyCode()) ? false : true;
            $methods = [];

            if (!$quote->isVirtual()) {
                $shippingAddress = $quote->getShippingAddress()
                    ->addData($this->getFormattedAddress($address))
                    ->save();

                $estimatedAddress = $this->estimatedAddressFactory->create()
                    ->setCountryId($shippingAddress->getCountryId())
                    ->setPostcode($shippingAddress->getPostcode())
                    ->setRegion((string) $shippingAddress->getRegion())
                    ->setRegionId($shippingAddress->getRegionId());

                $availableShippingMethods = $this->shippingMethodManager->estimateByAddress($quote->getId(), $estimatedAddress);
                $shouldInclTax = $this->shouldCartPriceInclTax($quote->getStore());

                if ($availableShippingMethods) {
                    foreach ($availableShippingMethods as $rate) {
                        if ($rate->getErrorMessage()) {
                            continue;
                        }

                        $methodRate = $shouldInclTax ? $rate->getPriceInclTax() : $rate->getPriceExclTax();
                        $methodRate = ($methodRate && $needConversion) ? $methodRate / $quote->getBaseToQuoteRate() : $methodRate;
                        array_push($methods, [
                            'label' => $rate->getCarrierTitle() . ' - ' . $rate->getMethodTitle(),
                            'amount' => (string) ($methodRate > 0) ? $this->novalnetRequestHelper->getFormattedAmount($methodRate) : $methodRate,
                            'identifier' => $rate->getCarrierCode() . '_' . $rate->getMethodCode()
                        ]);
                    }
                }

                if ($methods) {
                    $shippingAddress->setShippingMethod($methods[0]['identifier'])->setCollectShippingRates(true);
                }
            }

            $quote->collectTotals();
            $result = $this->getCartItems($quote);
            $result['methods'] = $methods;
            return $this->jsonHelper->jsonEncode($result);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }
    }

    /**
     * Apply shipping method and calculate totals
     *
     * @api
     * @param string[] $shippingMethod
     * @return string
     */
    public function applyShippingMethod($shippingMethod)
    {
        $quote = $this->cart->getQuote();
        try {
            if (!$quote->isVirtual()) {
                $shippingAddress = $quote->getShippingAddress();
                $quote->setShippingAddress($shippingAddress);
                $quote->getShippingAddress()->setCollectShippingRates(true)->setShippingMethod($shippingMethod['identifier']);
                $quote->setTotalsCollectedFlag(false)->collectTotals()->save();
            }

            return $this->jsonHelper->jsonEncode(
                $this->getCartItems($quote)
            );
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }
    }

    /**
     * Get Cart items
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param string $page
     * @return array
     */
    public function getCartItems($quote, $page = 'mini_cart_page')
    {
        $currency = $quote->getBaseCurrencyCode();
        if (empty($currency)) {
            $currency = $quote->getStore()->getBaseCurrencyCode();
        }
        $discount = $quote->getBaseSubtotal() - $quote->getBaseSubtotalWithDiscount();
        $needConversion = ($this->storeManager->getStore()->getCurrentCurrencyCode() == $this->storeManager->getStore()->getBaseCurrencyCode()) ? false : true;
        $taxAmount = $quote->getTotals()['tax']->getValue();
        $shouldInclTax = $this->shouldCartPriceInclTax($quote->getStore());
        $displayItems = [];
        $items = $quote->getAllVisibleItems();
        $getQuoteFormat = $quote->getIsVirtual();
        foreach ($items as $item) {

            if ($item->getParentItem()) {
                continue;
            }

            $rowTotal = $shouldInclTax ? $item->getBaseRowTotalInclTax() : $item->getBaseRowTotal();
            $rowTotal = $this->novalnetRequestHelper->getFormattedAmount($rowTotal);
            $rowTotal = ($rowTotal) ? $rowTotal : 0;

            $price = $shouldInclTax ? $item->getBasePriceInclTax() : $item->getBasePrice();

            $label = $item->getName();
            if ($item->getQty() > 0) {
                $formattedPrice = $this->priceCurrency->format($price, false, PriceCurrencyInterface::DEFAULT_PRECISION, null, $currency);
                $label .= sprintf(' (%s x %s)', $item->getQty(), $formattedPrice);
            }

            $displayItems[] = [
                'label' => $label,
                'type' => 'SUBTOTAL',
                'amount' => (string) $rowTotal
            ];
        }

        if ($discount) {
            $displayItems[] = [
                'label' => __('Discount'),
                'type' => 'SUBTOTAL',
                'amount' => (string) '-' . $this->novalnetRequestHelper->getFormattedAmount($discount)
            ];
        }

        if ($taxAmount) {
            $taxAmount = $needConversion ? $this->deltaRounding($taxAmount / $quote->getBaseToQuoteRate()) : $taxAmount;
            $displayItems[] = [
                'label' => __('Tax'),
                'type' => 'SUBTOTAL',
                'amount' => (string) $this->novalnetRequestHelper->getFormattedAmount($taxAmount)
            ];
        }

        if ($quote->getItemsCount() && !$quote->getIsVirtual()) {
            $shippingAmount = $quote->getShippingAmount();
            if ($shippingAmount && $needConversion) {
                $shippingAmount = $shippingAmount / $quote->getBaseToQuoteRate();
            }

            if ($shippingAmount !== null) {
                $displayItems[] = [
                    'label' => (!empty($quote->getShippingDescription())) ? $quote->getShippingDescription() : __('Shipping'),
                    'type' => 'SUBTOTAL',
                    'amount' => (string) ($shippingAmount > 0) ? $this->novalnetRequestHelper->getFormattedAmount($shippingAmount) : $shippingAmount
                ];
            }
        }

        $baseGrandTotal = $this->novalnetRequestHelper->getFormattedAmount($quote->getBaseGrandTotal());
        $baseGrandTotal = ($baseGrandTotal) ? $baseGrandTotal : 0;
        return [
            'currency' => (!empty($currency)) ? strtoupper($currency) : '',
            'total' => [
                'amount' => (string) $baseGrandTotal
            ],
            'displayItems' => $displayItems,
            'isVirtual' => $getQuoteFormat,
            'isEnabled' => $this->novalnetRequestHelper->isPageEnabledForExpressCheckout($page),
            'is_pending' => $this->novalnetRequestHelper->isAmountPendingForExpressCheckout(),
            'sheetConfig' => $this->novalnetRequestHelper->paymentSheetConfigurations()
        ];
    }

    /**
     * Place Order
     *
     * @api
     * @param string[] $paymentData
     * @param string[] $billingAddress
     * @param string[] $shippingAddress
     * @param string[] $shippingMethod
     * @param bool $isPaymentPage
     * @return string
     */
    public function placeOrder($paymentData, $billingAddress, $shippingAddress = [], $shippingMethod = [], $isPaymentPage = false)
    {
        try {
            $quote = $this->cart->getQuote();
            $quote->reserveOrderId()->save();
            $walletToken = $paymentData['token'];
            $paymentMethodCode = $paymentData['methodCode'];

            if (empty($billingAddress['email']) && !empty($shippingAddress['email'])) {
                $billingAddress['email'] = $shippingAddress['email'];
            }

            if (empty($billingAddress['phoneNumber']) && !empty($shippingAddress['phoneNumber'])) {
                $billingAddress['phoneNumber'] = $shippingAddress['phoneNumber'];
            }

            // set billing address
            $quote->getBillingAddress()->addData($this->getFormattedAddress($billingAddress));

            if (!$isPaymentPage && !$quote->isVirtual()) {

                if (empty($shippingAddress['email']) && !empty($billingAddress['email'])) {
                    $shippingAddress['email'] = $billingAddress['email'];
                }

                if (empty($shippingAddress['phoneNumber']) && !empty($billingAddress['phoneNumber'])) {
                    $shippingAddress['phoneNumber'] = $billingAddress['phoneNumber'];
                }

                $shippingIdentifier = $shippingMethod['identifier'];

                // set shipping address and shipping method
                $quote->getShippingAddress()
                    ->addData($this->getFormattedAddress($shippingAddress))
                    ->setShippingMethod($shippingIdentifier)
                    ->setCollectShippingRates(true);
            }

            $quote->setTotalsCollectedFlag(false)->collectTotals();
            $this->storeManager->setCurrentStore($quote->getStoreId());

            if (!$this->customerSession->isLoggedIn()) {
                $quote->setCheckoutMethod(Onepage::METHOD_GUEST)
                    ->setCustomerId(null)
                    ->setCustomerEmail($billingAddress['email'])
                    ->setCustomerIsGuest(true)
                    ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);

                //set customer name for guest user (account information)
                if ($quote->getCustomerFirstname() === null && $quote->getCustomerLastname() === null) {
                    $quote->setCustomerFirstname($quote->getBillingAddress()->getFirstname())
                        ->setCustomerLastname($quote->getBillingAddress()->getLastname());
                    if ($quote->getBillingAddress()->getMiddlename() === null) {
                        $quote->setCustomerMiddlename($quote->getBillingAddress()->getMiddlename());
                    }
                }
            } else {
                $quote->setCheckoutMethod(Onepage::METHOD_CUSTOMER);
            }

            $transactionData = [
                'method' => $paymentMethodCode,
                'additional_data' => [
                    $paymentMethodCode . '_wallet_token' => $walletToken
                ]
            ];

            if (!empty($paymentData['doRedirect'])) {
                $transactionData['additional_data'][$paymentMethodCode . '_do_redirect'] = $paymentData['doRedirect'];
            }

            //set payment additional data
            $quote->getPayment()->importData($transactionData);

            $quote->collectTotals()->save();

            $this->eventManager->dispatch(
                'checkout_submit_before',
                ['quote' => $quote]
            );

            //submit current quote to place order
            $order = $this->quoteManagement->submit($quote);

            $payment = $order->getPayment();
            $additionalData = (!empty($payment->getAdditionalData())) ? $this->jsonHelper->jsonDecode($payment->getAdditionalData(), true) : [];

            if ($paymentMethodCode == ConfigProvider::NOVALNET_GOOGLEPAY) {
                $additionalData['NnGpaysuccesstext'] = " (" . $paymentData['cardBrand'] . " **** " . $paymentData['lastFour'] . ")";
            } elseif ($paymentMethodCode == ConfigProvider::NOVALNET_APPLEPAY) {
                $additionalData['NnApplepaysuccesstext'] = " (" . $paymentData['cardBrand'] . " **** " . $paymentData['lastFour'] . ")";
            }

            $payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();

            if (!$order || !$order->getId()) {
                throw new LocalizedException(
                    __('A server error stopped your order from being placed. Please try to place your order again.')
                );
            } else {
                $this->eventManager->dispatch(
                    'checkout_type_onepage_save_order_after',
                    ['order' => $order, 'quote' => $quote]
                );

                $this->checkoutSession->setLastQuoteId($quote->getId())
                    ->setLastSuccessQuoteId($quote->getId())
                    ->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus());
            }

            $this->eventManager->dispatch(
                'checkout_submit_all_after',
                [
                    'order' => $order,
                    'quote' => $quote
                ]
            );

            $redirectURL = $this->urlInterface->getUrl('checkout/onepage/success');
            if (!empty($additionalData['NnRedirectURL'])) {
                $order->setState(Order::STATE_PENDING_PAYMENT)
                    ->setStatus(Order::STATE_PENDING_PAYMENT)
                    ->save();
                $order->addStatusHistoryComment(__('Customer was redirected to Novalnet'))
                    ->save();

                $this->novalnetLogger->notice('Order status and comments updated successfully');
                $redirectURL = $additionalData['NnRedirectURL'];
            }

            return $this->jsonHelper->jsonEncode([
                'redirectUrl' => $redirectURL
            ]);

        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }
    }

    /**
     * Format Tax Amount to Magento Rounding
     *
     * @param float $amount
     * @return double
     */
    public function deltaRounding($amount)
    {
        $temp = 0;
        $deltaValue = 0.000001;
        $sum = $amount + $deltaValue;
        $rounded = $this->priceCurrency->round($sum, 2);
        $temp = $rounded - $sum;

        return $this->priceCurrency->round($amount - $temp, 2);
    }

    /**
     * Get Quote
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        $quote = $this->checkoutHelper->getCheckout()->getQuote();
        if (!$quote->getId()) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $quote = $objectManager->create(\Magento\Checkout\Model\Session::class)->getQuote();
        }

        return $quote;
    }

    /**
     * Should Cart Price Include Tax
     *
     * @param null|int|string|Store $store
     * @return bool
     */
    public function shouldCartPriceInclTax($store = null)
    {
        if ($this->taxHelper->displayCartBothPrices($store)) {
            return true;
        } elseif ($this->taxHelper->displayCartPriceInclTax($store)) {
            return true;
        }

        return false;
    }

    /**
     * Get Formatted shipping Address
     *
     * @param array $info
     * @return array
     */
    public function getFormattedAddress($info)
    {
        $regionName = (!empty($info['administrativeArea'])) ? $info['administrativeArea'] : $info['locality'];
        $countryName = (!empty($info['countryCode'])) ? $info['countryCode'] : '';
        $regionId = $this->getRegionIdByName($regionName, $countryName);

        return [
            'firstname' => !empty($info['firstName']) ? $info['firstName'] : '',
            'lastname' => !empty($info['lastName']) ? $info['lastName'] : '',
            'company' => '',
            'email' => !empty($info['email']) ? $info['email'] : '',
            'street' => !empty($info['addressLines']) ? $info['addressLines'] : 'Unspecified Street',
            'city' => !empty($info['locality']) ? $info['locality'] : '',
            'region_id' => $regionId,
            'region' => $regionName,
            'postcode' => !empty($info['postalCode']) ? $info['postalCode'] : '',
            'country_id' => $countryName,
            'telephone' => !empty($info['phoneNumber']) ? $info['phoneNumber'] : 'Unspecified Telephone',
            'fax' => ''
        ];
    }

    /**
     * Get regions from country code
     *
     * @param mixed $countryCode
     * @return mixed
     */
    public function getRegionsForCountry($countryCode)
    {
        $values = [];

        $country = $this->countryFactory->create()->loadByCode($countryCode);

        if (empty($country)) {
            return $values;
        }

        $regions = $country->getRegions();

        foreach ($regions as $region) {
            if ($region) {
                $values['byCode'][strtolower(trim($region->getCode()))] = $region->getId();
                $values['byName'][strtolower(trim($region->getName()))] = $region->getId();
            }
        }

        return $values;
    }

    /**
     * Get Region id with region name
     *
     * @param mixed $regionName
     * @param mixed $regionCountry
     * @return string
     */
    public function getRegionIdByName($regionName, $regionCountry)
    {
        $regions = $this->getRegionsForCountry($regionCountry);

        $regionName = (!empty($regionName)) ? strtolower(trim($regionName)) : '';

        if (isset($regions['byName'][$regionName])) {
            return $regions['byName'][$regionName];
        } elseif (isset($regions['byCode'][$regionName])) {
            return $regions['byCode'][$regionName];
        }

        return '';
    }

    /**
     * Load product model with product id
     *
     * @param mixed $productId
     * @return mixed
     */
    public function loadProductById($productId)
    {
        $model = $this->productFactory->create();
        return $model->load($productId);
    }

    /**
     * Get Product Price with(without) Taxes
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param float|null $price
     * @param bool $inclTax
     * @param int|null $customerId
     * @param int|null $storeId
     *
     * @return float
     */
    public function getProductDataPrice($product, $price = null, $inclTax = false, $customerId = null, $storeId = null)
    {
        if (!($taxAttribute = $product->getCustomAttribute('tax_class_id'))) {
            return $price;
        }

        if (!$price) {
            $price = $product->getPrice();
        }

        $productRateId = $taxAttribute->getValue();
        $rate = $this->taxCalculation->getCalculatedRate($productRateId, $customerId, $storeId);
        if (
            (int) $this->scopeConfig->getValue(
                'tax/calculation/price_includes_tax',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            ) === 1
        ) {
            $priceExclTax = $price / (1 + ($rate / 100));
        } else {
            $priceExclTax = $price;
        }

        $priceInclTax = $priceExclTax + ($priceExclTax * ($rate / 100));
        $productPrice = $inclTax ? $priceInclTax : $priceExclTax;

        return $this->priceCurrency->round($productPrice, PriceCurrencyInterface::DEFAULT_PRECISION);
    }
}
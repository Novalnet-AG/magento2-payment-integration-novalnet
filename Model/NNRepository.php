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

class NNRepository implements \Novalnet\Payment\Api\NNRepositoryInterface
{
    /**
     * Mandatory Parameters.
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
     * Callback Request parameters.
     * @var array
     */
    private $response;

    /**
     * Order reference values.
     * @var
     */
    private $order;

    /**
     * Recived Event Data.
     * @var array
     */
    private $eventData;

    /**
     * Recived Event type.
     * @var string
     */
    private $eventType;

    /**
     * Recived Event TID.
     * @var int
     */
    private $eventTid;

    /**
     * Recived Event parent TID.
     * @var int
     */
    private $parentTid;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $salesOrderModel;

    /**
     * @var \Magento\Sales\Model\OrderNotifier
     */
    private $orderNotifier;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlInterface;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;

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
    private $timezone;

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
     * Additional Callback message
     * @var
     */
    private $additionalMessage;

    /**
     * Callback message
     * @var
     */
    private $callbackMessage;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize $serializer
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
     * @var \Zend\Uri\Uri
     */
    private $zendUri;

    /**
     * @param \Magento\Sales\Model\Order $salesOrderModel
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param \Magento\Framework\App\RequestInterface $requestInterface
     * @param \Magento\Sales\Model\Order\Payment\Transaction $transactionModel
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderEmailSender
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
     * @param \Zend\Uri\Uri $zendUri
     */
    public function __construct(
        Order $salesOrderModel,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Sales\Model\OrderNotifier $orderNotifier,
        \Magento\Framework\App\RequestInterface $requestInterface,
        \Magento\Sales\Model\Order\Payment\Transaction $transactionModel,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
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
        \Zend\Uri\Uri $zendUri
    ) {
        $this->salesOrderModel = $salesOrderModel;
        $this->urlInterface = $urlInterface;
        $this->orderNotifier = $orderNotifier;
        $this->requestInterface = $requestInterface;
        $this->transactionModel = $transactionModel;
        $this->pricingHelper = $pricingHelper;
        $this->dateTime = $dateTime;
        $this->storeManager = $storeManager;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->timeZone = $timezone;
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
        $this->zendUri = $zendUri;
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
        $response = json_decode($this->clientFactory->getBody(), true);

        return $this->clientFactory->getBody();
    }

    /**
     * Novalnet Webhook Url Configuration
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
        $response = json_decode($this->clientFactory->getBody(), true);

        return $this->clientFactory->getBody();
    }

    /**
     * Get redirect URL
     *
     * @api
     * @param mixed $quoteId
     * @return mixed
     */
    public function getRedirectURL($quoteId)
    {
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
        $additionalData = json_decode($payment->getAdditionalData(), true);

        if (!empty($additionalData['NnRedirectURL'])) {
            $order->setState(Order::STATE_HOLDED)
                ->setStatus(Order::STATE_HOLDED)
                ->save();
            $order->addStatusHistoryComment(__('Customer was redirected to Novalnet'))
                ->save();

            $this->novalnetLogger->notice('Order status and comments updated successfully');

            return $additionalData['NnRedirectURL'];
        } else {
            return false;
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
     * Get Instalment option details
     *
     * @api
     * @param string $code
     * @param float $total
     * @return mixed
     */
    public function getInstalmentOptions($code, $total)
    {
        $instalmentCycles = $this->novalnetConfig->getPaymentConfig($code, 'instalment_cycles');
        $instalmentCycles = explode(',', $instalmentCycles);
        $storeId = $this->storeManager->getStore()->getId();
        $allCycles = [];
        $i =1;
        foreach ($instalmentCycles as $cycle) {
            if (($total/$cycle) >= 9.99) {
                $formattedAmount = strip_tags($this->novalnetRequestHelper->getAmountWithSymbol(sprintf('%0.2f', $total / $cycle), $storeId));
                $allCycles[$i] = ['instalment_key' => $cycle.' X ' . $formattedAmount . '(' .(__(' per month'). ')'), 'instalment_value' => $cycle];
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
     * @return mixed
     */
    public function getInstalmentCycleAmount($amount, $period)
    {
        $cycleAmount   = sprintf('%0.2f', $amount / $period);
        $splitedAmount = $cycleAmount * ( $period - 1 );
        $lastCycle =  (sprintf('%0.2f', $amount - $splitedAmount) * 100)/100;
        $data = ['cycle_amount' => $cycleAmount, 'last_cycle' => $lastCycle, 'amount' => $amount];
        return $this->jsonHelper->jsonEncode($data);
    }

    /**
     * Novalnet payment webhook events
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
            } else {
                $this->displayMessage("The webhook notification has been received for the unhandled EVENT type($this->eventType)");
            }
        }

        return $this->additionalMessage . $this->callbackMessage;
    }
    
    /**
     * Add to Cart
     *
     * @param string $data
     * @return string
     * @throws CouldNotSaveException
     */
    public function addToCart($data)
    {
        $shipping_id = null;
        $this->zendUri->setQuery($data);
        $productInfo = $this->zendUri->getQueryAsArray();
        $productId = $productInfo['product'];
        $relatedProduct = $productInfo['related_product'];

        if (isset($productInfo['qty'])) {
            $filter = new \Zend_Filter_LocalizedToNormalized(
                ['locale' => $this->novalnetRequestHelper->getDefaultLanguage()]
            );
            $productInfo['qty'] = $filter->filter($productInfo['qty']);
        }

        $quote = $this->cart->getQuote();

        try {
            $storeId = $this->storeManager->getStore()->getId();
            $product = $this->productRepository->getById($productId, false, $storeId);
            $isUpdateRequired = false;
            
            foreach ($quote->getAllItems() as $item) {
                if ($item->getProductId() == $productId) {
                    $item = $this->cart->updateItem($item->getId(), $productInfo);
                    if ($item->getHasError()) {
                        throw new LocalizedException(__($item->getMessage()));
                    }

                    $isUpdateRequired = true;
                    break;
                }
            }

            if (!$isUpdateRequired) {
                $item = $this->cart->addProduct($product, $productInfo);
                if ($item->getHasError()) {
                    throw new LocalizedException(__($item->getMessage()));
                }

                if (!empty($relatedProduct)) {
                    $this->cart->addProductsByIds(explode(',', $relatedProduct));
                }
            }

            $this->cart->save();

            if ($shipping_id) {
                if (!$quote->isVirtual()) {
                    $quote->getShippingAddress()->setShippingMethod($shipping_id)
                             ->setCollectShippingRates(true)
                             ->collectShippingRates();
                }
            }

            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals();
            $quote->save();

            $result = $this->getCartItems($quote);
            return $this->jsonHelper->jsonEncode($result);
            
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }
    }

    /**
     * Place Order
     *
     * @param mixed $data
     * @param bool $paymentPage
     * @return string
     * @throws CouldNotSaveException
     */
    public function placeOrder($data, $paymentPage = false)
    {
        $applepayToken = $data['transaction']['token'];
        $emailFromApplePaySheet =  $data['wallet']['shipping']['emailAddress'];
        $quote = $this->cart->getQuote();
        try {
            $quote->reserveOrderId()->save();

            $billingData = $data['wallet']['billing'];
            $billingData['phoneNumber'] = $data['wallet']['shipping']['phoneNumber'];
            $billingData['emailAddress'] = $emailFromApplePaySheet;
            $billingAddress = $this->getFormattedAddress($billingData);

            //Add biling address to quote
            $quote->getBillingAddress()
                  ->addData($billingAddress);

            if (!$paymentPage && !$quote->isVirtual()) {
                $shippingData = $data['wallet']['shipping'];
                $shippingAddress = $this->getFormattedAddress($shippingData);
                //Add shipping address to quote
                $shipping = $quote->getShippingAddress()
                      ->addData($shippingAddress);

                $shippingMethodCode = $data['wallet']['chosen_shipping_method']['identifier'];
                //set shipping method to quote
                $shipping->setShippingMethod($shippingMethodCode)
                         ->setCollectShippingRates(true);
            }

            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals();
            $this->storeManager->setCurrentStore($quote->getStoreId());

            if (!$this->customerSession->isLoggedIn()) {
                $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST)
                      ->setCustomerId(null)
                      ->setCustomerEmail($emailFromApplePaySheet)
                      ->setCustomerIsGuest(true)
                      ->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
                //set customer name for guest user (aacount information)
                if ($quote->getCustomerFirstname() === null && $quote->getCustomerLastname() === null) {
                    $quote->setCustomerFirstname($quote->getBillingAddress()->getFirstname());
                    $quote->setCustomerLastname($quote->getBillingAddress()->getLastname());
                    if ($quote->getBillingAddress()->getMiddlename() === null) {
                        $quote->setCustomerMiddlename($quote->getBillingAddress()->getMiddlename());
                    }
                }
            } else {
                $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER);
            }
            //set payment additional data
            $quote->getPayment()->importData([
                'method' => ConfigProvider::NOVALNET_APPLEPAY,
                'additional_data' => [
                     ConfigProvider::NOVALNET_APPLEPAY . '_wallet_token' => $applepayToken
                 ]
              ]);
            $quote->collectTotals()->save();

            $this->eventManager->dispatch(
                'checkout_submit_before',
                ['quote' => $quote]
            );
            //submit current quote to place order
            $orderdata = $this->quoteManagement->submit($quote);

            if (!$orderdata || !$orderdata->getId()) {
                throw new LocalizedException(
                    __('A server error stopped your order from being placed. Please try to place your order again.')
                );
            } else {
                $this->eventManager->dispatch(
                    'checkout_type_onepage_save_order_after',
                    ['order' => $orderdata, 'quote' => $quote]
                );

                $this->checkoutSession
                    ->setLastQuoteId($quote->getId())
                    ->setLastSuccessQuoteId($quote->getId())
                    ->setLastOrderId($orderdata->getId())
                    ->setLastRealOrderId($orderdata->getIncrementId())
                    ->setLastOrderStatus($orderdata->getStatus());
            }

            $this->eventManager->dispatch(
                'checkout_submit_all_after',
                [
                    'order' => $orderdata,
                    'quote' => $quote
                ]
            );

            return $this->jsonHelper->jsonEncode([
                    'successUrl' => $this->urlInterface->getUrl('checkout/onepage/success')
                ]);

        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }
    }

    /**
     * Get Cart Contents
     *
     * @param  none
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
     * Estimate shipping methods
     *
     * @param mixed $address
     * @return string
     */
    public function estimateShippingMethod($address)
    {
        try {
            $quote = $this->cart->getQuote();
            $needConversion = ($this->storeManager->getStore()->getCurrentCurrencyCode() == $this->storeManager->getStore()->getBaseCurrencyCode()) ? false : true;
            $rates = [];
            $methods = [];

            if (!$quote->isVirtual()) {
                $shippingAddress = $this->getFormattedAddress($address);
                $quote->getShippingAddress()
                      ->addData($shippingAddress)
                      ->save();
                $quoteShippingAddress = $quote->getShippingAddress();
                $getShippingMethods = $this->shippingMethodManager->getList($quote->getId());
                $count = count($getShippingMethods);
                if ($count <= 0) {
                    $estimatedAddress = $this->estimatedAddressFactory->create();
                    $estimatedAddress->setCountryId($quoteShippingAddress->getCountryId());
                    $estimatedAddress->setPostcode($quoteShippingAddress->getPostcode());
                    $estimatedAddress->setRegion((string)$quoteShippingAddress->getRegion());
                    $estimatedAddress->setRegionId($quoteShippingAddress->getRegionId());
                    $getShippingMethods = $this->shippingMethodManager->estimateByAddress($quote->getId(), $estimatedAddress);
                }

                $shouldInclTax = $this->shouldCartPriceInclTax($quote->getStore());
                if ($getShippingMethods) {
                    foreach ($getShippingMethods as $rate) {
                        if ($rate->getErrorMessage()) {
                            continue;
                        }

                        $methodRate = $shouldInclTax ? $rate->getPriceInclTax() : $rate->getPriceExclTax();
                        if ($methodRate && $needConversion) {
                            $methodRate = $methodRate / $quote->getBaseToQuoteRate();
                        }
                        $methods[] = [
                            'label' => implode(' - ', [$rate->getCarrierTitle(), $rate->getMethodTitle()]),
                            'amount' => (string) $this->novalnetRequestHelper->getFormattedAmount($methodRate),
                            'identifier' => $rate->getCarrierCode() . '_' . $rate->getMethodCode()
                        ];
                    }
                }

                if ($methods) {
                    $quoteShippingAddress->setShippingMethod($methods[0]['identifier'])->setCollectShippingRates(true);
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
     * Get Applepay request params for Product page
     *
     * @param mixed $productId
     * @return string
     */
    public function getProductPageParams($productId)
    {
        $product = $this->loadProductById($productId);

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
            $amount += $productTotal;

            $productLabel = $product->getName();
            $formattedPrice = $this->priceCurrency->format($price, false);
            $productLabel .= sprintf(' (%s x %s)', '1', $formattedPrice);

            $items[] = [
                'label' => $productLabel,
                'amount' => $productTotal
            ];
        }

        $result = [
            'total' => [
                'amount' => $amount
            ],
            'displayItems' => $items,
            'isEnabled' => $params['isEnabled'],
            'is_pending' => $params['is_pending'],
            'sheetConfig' => $params['sheetConfig'],
            'isVirtual'  => ($quote->getItemsCount()) ? $quote->getIsVirtual() : null
        ];

        return $this->jsonHelper->jsonEncode($result);
    }

    /**
     * Apply shipping method and calculate totals
     *
     * @api
     * @param mixed $shippingMethod
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

            return $this->jsonHelper->jsonEncode($this->getCartItems($quote));
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }
    }

    /**
     * Assign Global params for callback process
     *
     * @param  none
     * @return boolean
     */
    private function assignGlobalParams()
    {
        try {
            $this->eventData = json_decode($this->requestInterface->getContent(), true);
        } catch (Exception $e) {
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
        $this->eventTid  = $this->response->getData('event/tid');
        $this->orderNo = $this->response->getData('transaction/order_no');
        $this->order = $this->getOrder();
        if ($this->order === false) {
            return false;
        }
        $this->currentTime = $this->dateTime->date('d-m-Y H:i:s');
        $this->lineBreak = PHP_EOL;
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
     * @param  none
     * @return boolean
     */
    private function checkIP()
    {
        // Authenticating the server request based on IP.
        $requestReceivedIp = $this->novalnetRequestHelper->getRequestIp();
        $novalnetHostIp = gethostbyname('pay-nn.de');

        if (!empty($novalnetHostIp) && !empty($requestReceivedIp)) {
            if ($novalnetHostIp !== $requestReceivedIp && !$this->testMode) {
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
     * Validate required parameter from the server request
     *
     * @return void
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
                    if (empty($this->response->getData($category .'/'. $parameter))) {
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
        if (!preg_match('/^\d{17}$/', $this->parentTid)
        ) {
            $this->displayMessage(
                'Invalid TID[' . $this->parentTid
                . '] for Order :' . $this->response->getData('transaction/order_no')
            );

            return false;
        } elseif (!preg_match('/^\d{17}$/', $this->eventTid)) {
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
        $checksumString  = $this->response->getData('event/tid') . $this->response->getData('event/type')
            . $this->response->getData('result/status');

        if ($this->response->getData('transaction/amount')) {
            $checksumString .= $this->response->getData('transaction/amount');
        }

        if ($this->response->getData('transaction/currency')) {
            $checksumString .= $this->response->getData('transaction/currency');
        }

        $accessKey = trim($this->paymentAccessKey);
        if (!empty($this->paymentAccessKey)) {
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
     * @return array
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
     * @param  none
     * @return boolean
     */
    private function handleCommunicationFailure()
    {
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
        if ($this->response->getData('result/status') == 'SUCCESS' &&
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
     * @return none
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
     * @return string
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
            $orderStatus = Order::STATE_HOLDED;
        } elseif ($this->response->getData('transaction/status') == 'CONFIRMED' || $this->response->getData('transaction/status') == 'PENDING' && !$this->novalnetConfig->isRedirectPayment($this->code)
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
     * @return none
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
     * @return boolean
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
     * @param  none
     * @return none
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
     * @param  none
     * @return boolean
     */
    private function sendEmailMagento()
    {
        try {
            $emailToAddrs = str_replace(' ', '', $this->emailToAddr);
            $emailToAddrs = explode(',', $emailToAddrs);
            $templateVars = [
                'fromName' => $this->emailFromName,
                'fromEmail' => $this->emailFromAddr,
                'toName' => $this->emailToName,
                'toEmail' => $this->emailToAddr,
                'subject' => $this->emailSubject,
                'body' => $this->emailBody
            ];

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
     * capture transaction
     *
     * @param  none
     * @return none
     */
    private function transactionCapture()
    {
        $invoiceDuedate = $this->response->getData('transaction/due_date');
        $additionalData = $this->novalnetRequestHelper->isSerialized($this->payment->getAdditionalData())
                ? $this->serializer->unserialize($this->payment->getAdditionalData())
                : json_decode($this->payment->getAdditionalData(), true);
        $transactionStatus = !empty($additionalData['NnStatus'])
            ? $this->novalnetRequestHelper->getStatus($additionalData['NnStatus'], $this->order) : '';

        if ($this->order->canInvoice() &&
            in_array($transactionStatus, ['ON_HOLD', 'PENDING'])
        ) {
            if ($invoiceDuedate && $this->response->getData('result/status') == 'SUCCESS') {
                $formatDate = $this->timeZone->formatDate($invoiceDuedate, \IntlDateFormatter::LONG);
                if (isset($additionalData['NnInvoiceComments'])) {
                    $note = explode('|', $additionalData['NnInvoiceComments']);
                    $additionalData['NnInvoiceComments'] = implode('|', $note);
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
                '<br>'. $message . '<br>' : $additionalData['NnComments'] . '<br>' . $message . '<br>';

            if (in_array(
                $this->payment->getMethod(),
                [ConfigProvider::NOVALNET_INVOICE_INSTALMENT, ConfigProvider::NOVALNET_SEPA_INSTALMENT]
            )) {
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
     * get Instalment payment Additional data
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
                $additionalData['InstalmentDetails'][$cycle] = ['amount' => $additionalData['InstallPaidAmount'],
                    'nextCycle' => !empty($futureInstalmentDates[$cycle + 1]) ? date('Y-m-d', strtotime($futureInstalmentDates[$cycle + 1])) : '',
                    'paidDate' => ($cycle == 1) ? date('Y-m-d') : '',
                    'status' => ($cycle == 1) ? 'Paid' : 'Pending',
                    'reference' => ($cycle == 1) ? $this->response->getData('transaction/tid') : ''];
            }
        }
        return $additionalData;
    }

    /**
     * Check transaction cancellation
     *
     * @param  none
     * @return none
     */
    private function transactionCancellation()
    {
        $transactionStatus = $this->transactionStatusModel->loadByAttribute($this->parentTid, 'tid');

        $this->novalnetRequestHelper->saveCanceledOrder($this->response, $this->order, $this->response->getData('transaction/status'));
        $this->emailBody = $message = __(
            'The transaction has been canceled on %1',
            $this->currentTime
        );

        $additionalData = $this->novalnetRequestHelper->isSerialized($this->payment->getAdditionalData())
            ? $this->serializer->unserialize($this->payment->getAdditionalData())
            : json_decode($this->payment->getAdditionalData(), true);
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
     * @param  none
     * @return none
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
            $additionalData = $this->novalnetRequestHelper->isSerialized($this->payment->getAdditionalData())
                    ? $this->serializer->unserialize($this->payment->getAdditionalData())
                    : json_decode($this->payment->getAdditionalData(), true);
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
                foreach ($this->order->getAllItems() as $item) {
                    $itemToCredit[$item->getId()] = ['qty'=> 0];
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
     * @param  none
     * @return none
     */
    private function transactionUpdate()
    {
        $invoiceDuedate = $this->response->getData('transaction/due_date');
        $transactionPaymentType = $this->response->getData('transaction/payment_type');
        $transaction = $this->transactionStatusModel->loadByAttribute($this->parentTid, 'tid');
        $message = "Novalnet callback received for the unhandled transaction type($transactionPaymentType) for $this->eventType EVENT";
        $additionalData = $this->novalnetRequestHelper->isSerialized($this->payment->getAdditionalData())
                ? $this->serializer->unserialize($this->payment->getAdditionalData())
                : json_decode($this->payment->getAdditionalData(), true);
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
            $this->emailBody = $message =  __(
                'The transaction has been updated with amount %1 and due date with %2',
                $additionalData['NnAmount'],
                $formatDate
            );
            if (isset($additionalData['NnInvoiceComments'])) {
                $note = explode('|', $additionalData['NnInvoiceComments']);
                $additionalData['NnInvoiceComments'] = implode('|', $note);
                $additionalData['NnDueDate'] = $formatDate;
            }
            if ($this->code == ConfigProvider::NOVALNET_CASHPAYMENT) {
                $additionalData['CpDueDate'] = $formatDate;
                $this->emailBody = $message =  __(
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
                $orderStatus = Order::STATE_HOLDED;
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
                if (in_array(
                    $this->payment->getMethod(),
                    [ConfigProvider::NOVALNET_INVOICE_INSTALMENT, ConfigProvider::NOVALNET_SEPA_INSTALMENT]
                )) {
                    $additionalData = $this->getInstalmentAdditionalData($additionalData);
                }
                $this->emailBody = $message = __(
                    'Transaction updated successfully for the TID: %1 with the amount %2 on %3',
                    $this->eventTid,
                    $amount = $this->novalnetRequestHelper->getFormattedAmount(
                        $this->response->getData('transaction/amount'),
                        'RAW'
                    ).' '.$this->currency,
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
        } elseif ($transactionStatus == 'ON_HOLD' && $this->response->getData('transaction/status') == 'ON_HOLD' &&
            in_array($this->code, [ConfigProvider::NOVALNET_SEPA, ConfigProvider::NOVALNET_SEPA_GUARANTEE, ConfigProvider::NOVALNET_INVOICE_GUARANTEE, ConfigProvider::NOVALNET_PREPAYMENT])) {
                $this->emailBody = $message = __(
                    'Transaction updated successfully for the TID: %1 with the amount %2 on %3',
                    $this->eventTid,
                    $amount = $this->novalnetRequestHelper->getFormattedAmount(
                        $this->response->getData('transaction/amount'),
                        'RAW'
                    ).' '.$this->currency,
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
     * @param  none
     * @return none
     */
    private function creditProcess()
    {
        $transactionPaymentType = $this->response->getData('transaction/payment_type');
        $message = "Novalnet callback received for the unhandled transaction type($transactionPaymentType) for $this->eventType EVENT";
        $amount = $this->novalnetRequestHelper->getFormattedAmount(
            $this->response->getData('transaction/amount'),
            'RAW'
        );

        if (in_array(
            $transactionPaymentType,
            ['INVOICE_CREDIT', 'ONLINE_TRANSFER_CREDIT', 'CASHPAYMENT_CREDIT', 'MULTIBANCO_CREDIT']
        )) {
            $additionalData = $this->novalnetRequestHelper->isSerialized($this->payment->getAdditionalData())
                ? $this->serializer->unserialize($this->payment->getAdditionalData())
                : json_decode($this->payment->getAdditionalData(), true);
            // Loads callback model using the Increment ID
            $callbackInfo = $this->callbackModel->loadLogByOrderId($this->orderNo);
            $transactionStatus = $this->transactionStatusModel->loadByAttribute($this->parentTid, 'tid');
            $totalAmount = $this->response->getData('transaction/amount') + $callbackInfo->getCallbackAmount();
            // Get original order amount
            $grandTotal = $this->novalnetRequestHelper->getFormattedAmount($this->order->getGrandTotal());
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
            if ($totalAmount >= $grandTotal) {
                $additionalData['NnPaid'] = 1;
            }
            if (($totalAmount < $grandTotal) ||
                $transactionPaymentType == 'ONLINE_TRANSFER_CREDIT'
            ) {
                if ($transactionPaymentType == 'ONLINE_TRANSFER_CREDIT' && ($totalAmount > $grandTotal)) {
                    $message = $message  . '<br>' . __(
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
            $additionalData = $this->novalnetRequestHelper->isSerialized($this->payment->getAdditionalData())
                ? $this->serializer->unserialize($this->payment->getAdditionalData())
                : json_decode($this->payment->getAdditionalData(), true);
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
     * @param  none
     * @return none
     */
    private function instalmentProcess()
    {
        $additionalData = $this->novalnetRequestHelper->isSerialized($this->payment->getAdditionalData())
                ? $this->serializer->unserialize($this->payment->getAdditionalData())
                : json_decode($this->payment->getAdditionalData(), true);
        $additionalData['NnTid'] = $this->response->getData('transaction/tid');
        $instalmentTransactionAmount = $this->novalnetRequestHelper->getFormattedAmount(
            $this->response->getData('transaction/amount'),
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
        if ($this->response->getData('transaction/payment_type') == 'INSTALMENT_INVOICE') {
            $note = explode('|', $additionalData['NnInvoiceComments']);
            if ($this->response->getData('transaction/due_date')) {
                $formatDate = $this->timeZone->formatDate(
                    $this->response->getData('transaction/due_date'),
                    \IntlDateFormatter::LONG
                );
                $additionalData['NnDueDate'] = $formatDate;
            }

            $note[5] = 'InvoiceAmount:' . $this->pricingHelper->currency(
                $instalmentTransactionAmount,
                true,
                false
            );
            $note[7] = 'Payment Reference:' . $this->response->getData('transaction/tid');
            $additionalData['NnInvoiceComments'] = implode('|', $note);
        }

        $additionalData['InstalmentDetails'][$this->response->getData('instalment/cycles_executed')] = [
            'amount' => $instalmentTransactionAmount,
            'nextCycle' => $this->response->getData('instalment/next_cycle_date'),
            'paidDate' => date('Y-m-d'),
            'status' => 'Paid',
            'reference' => $this->response->getData('transaction/tid')
        ];
        $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();
        $this->sendInstalmentmail();
        // Send instalment email to end customer
        $this->orderNotifier->notify($this->order);
        $this->displayMessage('Novalnet Callbackscript received. Instalment payment executed properly');
    }

    /**
     * Handle payment INSTALMENT process
     *
     * @param  none
     * @return none
     */
    private function instalmentCancelProcess()
    {
        $refundTid = empty($this->response->getData('transaction/refund/tid'))
            ? $this->response->getData('transaction/tid') : $this->response->getData('transaction/refund/tid');
        $refundAmount = empty($this->response->getData('transaction/refund/amount'))
            ? $this->response->getData('transaction/amount') : $this->response->getData('transaction/refund/amount');
        $this->emailBody = $message = __(
            'The Refund executed successfully for the TID: %1 amount: %2 on %3. The subsequent TID: %4',
            $this->parentTid,
            $this->novalnetRequestHelper->getFormattedAmount($refundAmount, 'RAW') . ' ' . $this->currency,
            $this->currentTime,
            $refundTid
        );

        $additionalData = $this->novalnetRequestHelper->isSerialized($this->payment->getAdditionalData())
                ? $this->serializer->unserialize($this->payment->getAdditionalData())
                : json_decode($this->payment->getAdditionalData(), true);
        $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message :
            $additionalData['NnComments'] . '<br><br>' . $message;
        $additionalData['InstalmentCancel'] = 1;
        $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();

        $this->sendCallbackMail();
        $this->displayMessage($message);
    }

    /**
     * Send Instalment mail
     *
     * @param  none
     * @return none
     */
    private function sendInstalmentmail()
    {
        $this->getEmailConfig();
        $from = ['email' => $this->emailFromAddr, 'name' => $this->emailFromName];
        $mailSubject = __('Instalment confirmation %1 Order no: %2', $this->storeFrontendName, $this->orderNo);
        $additionalData = $this->order->getPayment()->getAdditionalData();
        $additionalData = $this->novalnetRequestHelper->isSerialized($additionalData)
                ? $this->serializer->unserialize($additionalData)
                : json_decode($additionalData, true);

        $templateVars = [
            'subject' => $mailSubject,
            'order'      => $this->order,
            'orderNo'    => $this->orderNo,
            'formattedShippingAddress' => $this->salesOrderAddressRenderer->format($this->order->getShippingAddress(), 'html'),
            'formattedBillingAddress' => $this->salesOrderAddressRenderer->format($this->order->getBillingAddress(), 'html'),
            'store' => $this->order->getStore(),
            'payment_html' => $this->paymentHelper->getInfoBlockHtml(
                $this->order->getPayment(),
                $this->order->getStore()->getStoreId()
            ),
            'sepaPayment' => ((!isset($additionalData['prepaid']) || empty($additionalData['prepaid'])) && ($this->response->getData('transaction/payment_type') == 'INSTALMENT_DIRECT_DEBIT_SEPA'))
                ? __('The instalment amount for this cycle %1 %2 will be debited from your account in one - three business days.', $this->novalnetRequestHelper->getFormattedAmount($this->response->getData('transaction/amount'), 'RAW'), $this->currency) : ""
        ];

        try {
            $this->inlineTranslation->suspend();
            $storeId = $this->storeManager->getStore()->getId();
            $templateOptions = [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $storeId
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
            $this->displayMessage(__FUNCTION__ . ': Sending Email succeeded!'.'<br>', false);
        } catch (\Exception $e) {
            $this->novalnetLogger->error("Email sending failed: $e");
            $this->displayMessage('Email sending failed: ', false);
            return false;
        }
    }

    /**
     * Handle payment CHARGEBACK/RETURN_DEBIT/REVERSAL process
     *
     * @param  none
     * @return none
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

        $additionalData = $this->novalnetRequestHelper->isSerialized($this->payment->getAdditionalData())
                ? $this->serializer->unserialize($this->payment->getAdditionalData())
                : json_decode($this->payment->getAdditionalData(), true);
        $additionalData['NnComments'] = empty($additionalData['NnComments']) ? '<br>' . $message :
            $additionalData['NnComments'] . '<br><br>' . $message;
        $this->payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();

        $this->sendCallbackMail();
        $this->displayMessage($message);
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
            $price = $shouldInclTax ? $item->getBasePriceInclTax() : $item->getBasePrice();

            $label = $item->getName();
            if ($item->getQty() > 0) {
                $formattedPrice = $this->priceCurrency->format($price, false, PriceCurrencyInterface::DEFAULT_PRECISION, null, $currency);
                $label .= sprintf(' (%s x %s)', $item->getQty(), $formattedPrice);
            }

            $displayItems[] = [
                'label' => $label,
                'amount' => (string) $this->novalnetRequestHelper->getFormattedAmount($rowTotal)
            ];
        }

        if ($discount) {
            $displayItems[] = [
                'label' => __('Discount'),
                'amount' => (string) '-' . $this->novalnetRequestHelper->getFormattedAmount($discount)
            ];
        }

        if ($taxAmount) {
            $taxAmount = $needConversion ? $this->deltaRounding($taxAmount / $quote->getBaseToQuoteRate()) : $taxAmount;
            $displayItems[] = [
                'label' => __('Tax'),
                'amount' => (string) $this->novalnetRequestHelper->getFormattedAmount($taxAmount)
            ];
        }

        if ($quote->getItemsCount() && !$quote->getIsVirtual()) {
            $shippingAmount = $quote->getShippingAmount();
            if ($shippingAmount && $needConversion) {
                $shippingAmount = $shippingAmount / $quote->getBaseToQuoteRate();
            }

            $displayItems[] = [
                'label' => (!empty($quote->getShippingDescription())) ? $quote->getShippingDescription() : __('Shipping'),
                'amount' => (string) $this->novalnetRequestHelper->getFormattedAmount($shippingAmount)
            ];
        }

        return [
            'currency' => strtoupper($currency),
            'total' => [
                'amount' => $this->novalnetRequestHelper->getFormattedAmount($quote->getBaseGrandTotal())
            ],
            'displayItems' => $displayItems,
            'isVirtual' => $getQuoteFormat,
            'isEnabled' => $this->novalnetRequestHelper->isPageEnabledForApplePay($page),
            'is_pending' => $this->novalnetRequestHelper->isApplePayAmountPending(),
            'sheetConfig' => $this->novalnetRequestHelper->paymentSheetConfigurations()
        ];
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
     * @param none
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
     * @param  null|int|string|Store $store
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
        $countryName = (!empty($info['countryCode'])) ? $info['countryCode'] : $info['country'];
        $regionId = $this->getRegionIdByName($regionName, $countryName);
        return [
            'firstname' => !empty($info['givenName']) ? $info['givenName'] : '',
            'lastname' => !empty($info['familyName']) ? $info['familyName'] : '',
            'company' => '',
            'email' => !empty($info['emailAddress']) ? $info['emailAddress'] : '',
            'street' => !empty($info['addressLines'][0]) ? $info['addressLines'][0] : 'Unspecified Street',
            'city' => !empty($info['locality']) ? $info['locality'] : '',
            'region_id' => $regionId,
            'region' => $regionName,
            'postcode' => $info['postalCode'],
            'country_id' => $info['countryCode'],
            'telephone' => !empty($info['phoneNumber']) ? $info['phoneNumber'] : 'Unspecified Telephone',
            'fax' => ''
        ];
    }

    /**
     * get regions from country code
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
            $values['byCode'][strtolower(trim($region->getCode()))] = $region->getId();
            $values['byName'][strtolower(trim($region->getName()))] = $region->getId();
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

        $regionName =strtolower(trim($regionName));

        if (isset($regions['byName'][$regionName])) {
            return $regions['byName'][$regionName];
        } elseif (isset($regions['byCode'][$regionName])) {
            return $regions['byCode'][$regionName];
        }

        return null;
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
     * @param \Magento\Catalog\Model\Product $product
     * @param float|null $price
     * @param bool $inclTax
     * @param int $customerId
     * @param int $storeId
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
        if ((int) $this->scopeConfig->getValue(
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

        return round($inclTax ? $priceInclTax : $priceExclTax, PriceCurrencyInterface::DEFAULT_PRECISION);
    }
}

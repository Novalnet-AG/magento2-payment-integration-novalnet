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
namespace Novalnet\Payment\Helper;

use Magento\Sales\Model\Order;
use Novalnet\Payment\Model\Ui\ConfigProvider;
use Novalnet\Payment\Model\NNConfig;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Novalnet payment request helper
 *
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $pricingHelper;

    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    protected $productMetadata;

    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $requestInterface;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $resolverInterface;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    protected $serializer;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $adminCheckoutSession;

    /**
     * @var \Magento\Checkout\Model\SessionFactory
     */
    protected $checkoutSessionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $dateTime;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderEmailSender;

    /**
     * @var \Novalnet\Payment\Model\TransactionStatus
     */
    protected $transactionStatusModel;

    /**
     * @var NNConfig
     */
    protected $novalnetConfig;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\ServerAddress
     */
    protected $serverAddress;

    /**
     * @var \Novalnet\Payment\Logger\NovalnetLogger
     */
    protected $novalnetLogger;

    /**
     * @var array
     */
    protected $onholdStatus = [ '91', '98', '99', '85'];

    /**
     * @var array
     */
    protected $pendingStatus = [ '75', '86', '90'];

    /**
     * @var \Magento\Directory\Helper\Data
     */
    protected $directoryHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $coreSession;

    /**
     * @var \Magento\Directory\Model\Region $regionModel
     */
    protected $regionModel;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Framework\Serialize\JsonValidator
     */
    protected $jsonValidator;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonSerializer;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param \Magento\Framework\App\ProductMetadata $productMetadata
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\App\RequestInterface $requestInterface
     * @param \Magento\Framework\Locale\ResolverInterface $resolverInterface
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Backend\Model\Session\Quote $adminCheckoutSession
     * @param \Magento\Checkout\Model\SessionFactory $checkoutSessionFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderEmailSender
     * @param \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel
     * @param NNConfig $novalnetConfig
     * @param \Magento\Framework\HTTP\PhpEnvironment\ServerAddress $serverAddress
     * @param \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     * @param \Magento\Directory\Model\Region $regionModel
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Serialize\JsonValidator $jsonValidator
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Framework\App\ProductMetadata $productMetadata,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\App\RequestInterface $requestInterface,
        \Magento\Framework\Locale\ResolverInterface $resolverInterface,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Backend\Model\Session\Quote $adminCheckoutSession,
        \Magento\Checkout\Model\SessionFactory $checkoutSessionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderEmailSender,
        \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel,
        NNConfig $novalnetConfig,
        \Magento\Framework\HTTP\PhpEnvironment\ServerAddress $serverAddress,
        \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Directory\Model\Region $regionModel,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Serialize\JsonValidator $jsonValidator,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
    ) {
        parent::__construct($context);
        $this->appState = $appState;
        $this->pricingHelper = $pricingHelper;
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
        $this->requestInterface = $requestInterface;
        $this->resolverInterface = $resolverInterface;
        $this->serializer = $serializer;
        $this->countryFactory = $countryFactory;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->adminCheckoutSession = $adminCheckoutSession;
        $this->checkoutSessionFactory = $checkoutSessionFactory;
        $this->dateTime = $dateTime;
        $this->invoiceSender = $invoiceSender;
        $this->orderEmailSender = $orderEmailSender;
        $this->transactionStatusModel = $transactionStatusModel;
        $this->novalnetConfig = $novalnetConfig;
        $this->serverAddress = $serverAddress;
        $this->novalnetLogger = $novalnetLogger;
        $this->directoryHelper = $directoryHelper;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->coreSession = $coreSession;
        $this->regionModel = $regionModel;
        $this->priceCurrency = $priceCurrency;
        $this->jsonValidator = $jsonValidator;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Get request headers
     *
     * @param string|bool $payment_access_key
     * @param int|null $storeId
     * @return array
     */
    public function getRequestHeaders($payment_access_key = false, $storeId = null)
    {
        if (!$payment_access_key) {
            $payment_access_key = $this->novalnetConfig->getGlobalConfig('payment_access_key', $storeId);
        }

        $encoded_data = base64_encode($payment_access_key);
        return [
            'Content-Type' => 'application/json',
            'Charset' => 'utf-8',
            'Accept' => 'application/json',
            'X-NN-Access-Key' => $encoded_data,
        ];
    }

    /**
     * Build addtional data for redirect payment
     *
     * @param mixed $response
     * @return array
     */
    public function buildRedirectAdditionalData($response)
    {
        if ($response->getData('transaction/txn_secret')) {
            $additionalData['NnRedirectURL'] = $response->getData('result/redirect_url');
            $additionalData['NnTxnSecret'] = $response->getData('transaction/txn_secret');
            $additionalData['NnPaymentTitle'] = $this->getMethodSession(ConfigProvider::NOVALNET_PAY)->getData(ConfigProvider::NOVALNET_PAY . '_name');
            $additionalData['NnPaymentType'] = $this->getMethodSession(ConfigProvider::NOVALNET_PAY)->getData(ConfigProvider::NOVALNET_PAY . '_type');
            $additionalData['NnPaymentProcessMode'] = $this->getMethodSession(ConfigProvider::NOVALNET_PAY)->getData(ConfigProvider::NOVALNET_PAY . '_process_mode');
            if ($this->getMethodSession(ConfigProvider::NOVALNET_PAY)->getData(ConfigProvider::NOVALNET_PAY . '_payment_action') == NNConfig::ACTION_ZERO_AMOUNT) {
                $additionalData['NnZeroAmountBooking'] = 1;
            }

            return $additionalData;
        }

        return [];
    }

    /**
     * Build addtional data for payment
     *
     * @param mixed $response
     * @param mixed $payment
     * @return array
     */
    public function buildAdditionalData($response, $payment)
    {
        $additionalData = $this->getPaymentAdditionalData($payment->getAdditionalData());

        if (isset($additionalData['NnRedirectURL'])) {
            unset($additionalData['NnRedirectURL']);
        }

        $dataToStore = ['transaction/test_mode', 'transaction/status', 'transaction/tid', 'custom/lang', 'transaction/due_date', 'transaction/partner_payment_reference', 'transaction/payment_data/cc_3d', 'transaction/checkout_js', 'transaction/checkout_token'];

        foreach ($dataToStore as $responseData) {
            if ($response->getData($responseData)) {
                $responseDataFormatted = preg_replace('/(?:.*)\/(.*)/', ucwords('$1'), $responseData);
                $responseDataFormatted = str_replace('_', '', ucwords($responseDataFormatted, '_'));
                $additionalData['Nn' . $responseDataFormatted] = $response->getData($responseData);
            }
        }

        $invoiceAmount = $this->pricingHelper->currency(
            $this->getFormattedAmount(($response->getData('instalment/cycle_amount') ? $response->getData('instalment/cycle_amount')
            : $response->getData('transaction/amount')), 'RAW'),
            true,
            false
        );
        $additionalData['NnAmount'] = $invoiceAmount;
        if (empty($additionalData['NnPaymentType'])) {
            $additionalData['NnPaymentType'] = $response->getData('transaction/payment_type');
        }

        if (empty($additionalData['NnPaymentTitle'])) {
            $additionalData['NnPaymentTitle'] = $this->getMethodSession(ConfigProvider::NOVALNET_PAY)->getData(ConfigProvider::NOVALNET_PAY . '_name');
        }

        if (empty($additionalData['NnPaymentProcessMode'])) {
            $additionalData['NnPaymentProcessMode'] = $this->getMethodSession(ConfigProvider::NOVALNET_PAY)->getData(ConfigProvider::NOVALNET_PAY . '_process_mode');
        }

        if (!empty($additionalData['NnDueDate'])) {
            $additionalData['NnDueDate'] = $this->dateTime->formatDate(
                $additionalData['NnDueDate'],
                \IntlDateFormatter::LONG
            );
        }

        if ($response->getData('transaction/bank_details')) {
            $additionalData['NnInvoiceComments'] = $this->getInvoiceComments($response);
        }

        if ($response->getData('transaction/payment_type') == 'CASHPAYMENT') {
            $additionalData['CpDueDate'] = $this->dateTime->formatDate(
                $response->getData('transaction/due_date'),
                \IntlDateFormatter::LONG
            );

            $cashPaymentStores = [];
            foreach ($response->getData('transaction/nearest_stores') as $key => $cashPaymentStore) {
                $cashPaymentStores[] = [
                    'title' => $cashPaymentStore['store_name'],
                    'street' => $cashPaymentStore['street'],
                    'city' => $cashPaymentStore['city'],
                    'zipcode' => $cashPaymentStore['zip'],
                    'country' => $this->countryFactory->create()->loadByCode($cashPaymentStore['country_code'])
                        ->getName()
                ];
            }

            $additionalData['CashpaymentStores'] = $cashPaymentStores;
        }

        if ($response->getData('instalment')) {
            $instalmentCycleAmount = $this->getFormattedAmount(
                $response->getData('instalment/cycle_amount'),
                'RAW'
            );
            $additionalData['InstallPaidAmount'] = $instalmentCycleAmount;
            $additionalData['PaidInstall'] = $response->getData('instalment/cycles_executed');
            $additionalData['DueInstall'] = $response->getData('instalment/pending_cycles');
            $additionalData['NextCycle'] = $response->getData('instalment/next_cycle_date');
            $additionalData['InstallCycleAmount'] = $instalmentCycleAmount;

            if ($futureInstalmentDates = $response->getData('instalment/cycle_dates')) {
                foreach (array_keys($futureInstalmentDates) as $cycle) {
                    $additionalData['InstalmentDetails'][$cycle] = [
                        'amount' => $instalmentCycleAmount,
                        'nextCycle' => !empty($futureInstalmentDates[$cycle + 1]) ? date('Y-m-d', strtotime($futureInstalmentDates[$cycle + 1])) : '',
                        'paidDate' => ($cycle == 1) ? date('Y-m-d') : '',
                        'status' => ($cycle == 1) ? 'Paid' : 'Pending',
                        'reference' => ($cycle == 1) ? $response->getData('transaction/tid') : ''
                    ];
                }
            }

        }

        if ($this->getMethodSession(ConfigProvider::NOVALNET_PAY)->getData(ConfigProvider::NOVALNET_PAY . '_payment_action') == NNConfig::ACTION_ZERO_AMOUNT) {
            $additionalData['NnZeroAmountBooking'] = 1;
        }

        $order = $payment->getOrder();
        $this->saveTransactionDetails($response, ConfigProvider::NOVALNET_PAY);

        return $additionalData;
    }

    /**
     * Retrieves Novalnet Invoice Comments
     *
     * @param mixed $response
     * @return string
     */
    public function getInvoiceComments($response)
    {
        $invoicePaymentsNote = 'Account holder: ' . $response->getData('transaction/bank_details/account_holder');
        $invoicePaymentsNote .= '|IBAN: ' . $response->getData('transaction/bank_details/iban');
        $invoicePaymentsNote .= '|BIC: ' . $response->getData('transaction/bank_details/bic');
        $invoicePaymentsNote .= '|Bank: ' . $response->getData('transaction/bank_details/bank_name')
        . ' ' . $response->getData('transaction/bank_details/bank_place');

        $invoicePaymentsNote .= '|Payment References description:';
        if ($response->getData('instalment/cycle_amount')) {
            $invoicePaymentsNote .= '|Payment Reference:' . $response->getData('transaction/tid');
        } else {
            $invoicePaymentsNote .= '|Payment reference 1:' . $response->getData('transaction/tid');
            $invoicePaymentsNote .= '|Payment reference 2:' . $response->getData('transaction/invoice_ref');
        }

        return $invoicePaymentsNote;
    }

    /**
     * Saves transaction details into Novalnet table
     *
     * @param mixed $response
     * @param string $paymentMethodCode
     * @return void
     */
    public function saveTransactionDetails($response, $paymentMethodCode)
    {
        $this->transactionStatusModel->setOrderId($response->getData('transaction/order_no'))
           ->setTid($response->getData('transaction/tid'))
           ->setStatus($response->getData('transaction/status'))
           ->setCustomerId($this->getCustomerId())
           ->setPaymentMethod($paymentMethodCode)
           ->save();
    }

    /**
     * Check payment hash for redirect payments
     *
     * @param array $response
     * @param array $additionalData
     * @return bool
     */
    public function checkPaymentHash($response, $additionalData)
    {
        $accessKey = (!empty($this->novalnetConfig->getGlobalConfig('payment_access_key'))) ? trim($this->novalnetConfig->getGlobalConfig('payment_access_key')) : '';
        $checksumString = $response['tid'] . $additionalData['NnTxnSecret'] . $response['status']
            . strrev($accessKey);
        $generatedChecksum = (!empty($checksumString)) ? hash('sha256', $checksumString) : '';

        if ($generatedChecksum !== $response['checksum']) {
            return false;
        }

        return true;
    }

    /**
     * Check return data for redirect payments and update order accordingly
     *
     * @param mixed $response
     * @param mixed $order
     * @param mixed $payment
     * @return bool
     */
    public function checkReturnedData($response, $order, $payment)
    {
        if ($response->getData('transaction/status') == 'FAILURE') {
            $this->saveCanceledOrder($response, $order);
            return false;
        }
        $storeId = $order->getStoreId();
        // Retrieves additional payment data for the order
        $additionalData = $this->buildAdditionalData($response, $payment);
        $amount = $this->getFormattedAmount($response->getData('transaction/amount'), 'RAW');
        $payment->setAdditionalData($this->jsonEncode($additionalData))->save();
        $setOrderStatus = $this->novalnetConfig->getOnholdStatus($storeId);

        if ($response->getData('transaction/status') == 'CONFIRMED' && empty($additionalData['NnZeroAmountBooking'])) {
            // capture transaction
            $payment->setTransactionId($additionalData['NnTid'])
                ->setLastTransId($additionalData['NnTid'])
                ->capture(null)
                ->setAmount($amount)
                ->setIsTransactionClosed(false)
                ->setShouldCloseParentTransaction(false)
                ->save();

            $setOrderStatus = $this->novalnetConfig->getOrderCompletionStatus($storeId);
        } else {
            // authorize transaction
            if (!empty($additionalData['NnZeroAmountBooking'])) {
                $payment->authorize(true, $order->getBaseGrandTotal())->save();
            } else {
                $payment->authorize(true, $amount)->save();
            }

            if ($response->getData('transaction/status') == 'PENDING') {
                    $setOrderStatus = 'pending';
            }
        }

        $order->setState(Order::STATE_PROCESSING)
            ->setStatus($setOrderStatus)
            ->save();

        $order->addStatusHistoryComment(__('Customer successfully returned from Novalnet'), false)
            ->save();

        // Order Confirmation and Invoice email
        if ($order->getCanSendNewEmailFlag()) {
            try {
                $this->orderEmailSender->send($order);
                $invoice = current($order->getInvoiceCollection()->getItems());
                if ($invoice) {
                    $this->invoiceSender->send($invoice);
                }
            } catch (\Exception $e) {
                $this->_logger->critical($e);
                $this->novalnetLogger->error($e);
            }
        }

        $this->saveTokenForSubscription($order, $response);

        return true;
    }

    /**
     * Save token for subscription
     *
     * @param mixed $order
     * @param mixed $response
     * @return void
     */
    public function saveTokenForSubscription($order, $response)
    {
        if (!empty($order->getItems())) {
            foreach ($order->getItems() as $item) {
                $additionalData = $this->jsonDecode($item->getAdditionalData());
                if (!empty($additionalData['period_unit']) && !empty($additionalData['billing_frequency'])) {
                    $token = !empty($response->getData('transaction/payment_data/token')) ? $response->getData('transaction/payment_data/token') : '';
                    if (!empty($token)) {
                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $subscriptionModel = $objectManager->create(\Novalnet\Subscription\Model\SubscriptionDetails::class);
                        $subscriptionModel = $subscriptionModel->getCollection()->addFieldToFilter('order_id', $order->getIncrementId())->getFirstItem();
                        $subscriptionModel->setToken($token)->save();
                    }
                    break;
                }
            }
        }
    }

    /**
     * Save canceled payment transaction
     *
     * @param mixed $response
     * @param mixed $order
     * @param string|bool $statusText
     * @return void
     */
    public function saveCanceledOrder($response, $order, $statusText = false)
    {
        $payment = $order->getPayment();
        // Get payment transaction status message
        $statusMessage = ($statusText)
            ? $statusText : $response->getData('result/status_text');

        $additionalData = $this->getPaymentAdditionalData($payment->getAdditionalData());

        $nnAmount = $this->pricingHelper->currency(
            $this->getFormattedAmount(($response->getData('instalment/cycle_amount') ? $response->getData('instalment/cycle_amount')
            : $response->getData('transaction/amount')), 'RAW'),
            true,
            false
        );

        $additionalData['NnTid'] = $response->getData('transaction/tid');
        $additionalData['NnStatus'] = $response->getData('transaction/status');
        $additionalData['NnTestMode'] = $response->getData('transaction/test_mode');
        $additionalData['NnAmount'] = $nnAmount;
        $additionalData['NnComments'] = '<b><font color="red">' . __('Payment Failed') . '</font> - '
            . $statusMessage . '</b>';
        $payment->setLastTransId($additionalData['NnTid'])
            ->setAdditionalData($this->jsonEncode($additionalData))
            ->save();

        // UnHold and Cancel the order with the cancel text
        if ($order->canUnhold()) {
            $order->unhold();
        }

        $order->registerCancellation($statusMessage)->save();
        $this->cancelSubscriptionItems($order);
    }

    /**
     * Cancel subscription items
     *
     * @param mixed $order
     * @return void
     */
    public function cancelSubscriptionItems($order)
    {
        if (!empty($order->getItems())) {
            foreach ($order->getItems() as $item) {
                $additionalData = $this->jsonDecode($item->getAdditionalData());
                if (!empty($additionalData['period_unit']) && !empty($additionalData['billing_frequency'])) {
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $subscriptionModel = $objectManager->create(\Novalnet\Subscription\Model\SubscriptionItems::class);
                    $subscriptionitems = $subscriptionModel->getCollection()->addFieldToFilter('order_id', $order->getIncrementId());
                    if (!empty($subscriptionitems)) {
                        foreach ($subscriptionitems as $subscriptionitem) {
                            $subscriptionitem->setState('CANCELED')->save();
                        }
                    }
                    break;
                }
            }
        }
    }

    /**
     * Restore cart items
     *
     * @param  string $orderId
     * @return void
     */
    public function restoreQuote($orderId)
    {
        $this->checkoutSession->restoreQuote();
        $this->checkoutSession->setLastRealOrderId($orderId);
    }

    /**
     * Retrieve Magento version
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Retrieve Novalnet version
     *
     * @return int
     */
    public function getNovalnetVersion()
    {
        return $this->moduleList->getOne('Novalnet_Payment')['setup_version'];
    }

    /**
     * Get shop default language
     *
     * @return string
     */
    public function getDefaultLanguage()
    {
        $defaultLocale = (!empty($this->resolverInterface->getDefaultLocale())) ? explode('_', $this->resolverInterface->getDefaultLocale()) : [];
        return (is_array($defaultLocale) && !empty($defaultLocale)) ? $defaultLocale[0] : 'en';
    }

    /**
     * Retrieves customer session model
     *
     * @return mixed
     */
    public function getCustomerSession()
    {
        return $this->customerSession;
    }

    /**
     * Retrieve customer id from current session
     *
     * @return int|string
     */
    public function getCustomerId()
    {
        if ($this->coreSession->getRecurringProcess()) {
            return $this->coreSession->getCustomerId();
        }

        if ($this->customerSession->isLoggedIn()) {
            return $this->customerSession->getCustomer()->getId();
        } elseif ($this->isAdmin()) {
            $adminSession = $this->getAdminCheckoutSession();
            return $adminSession->getCustomerId() ? $adminSession->getCustomerId() : 'guest';
        } else {
            return 'guest';
        }
    }

    /**
     * Check if the payment area is admin
     *
     * @return boolean
     */
    public function isAdmin()
    {
        return (bool)($this->appState->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE);
    }

    /**
     * Retrieves admin checkout session model
     *
     * @return mixed
     */
    public function getAdminCheckoutSession()
    {
        return $this->adminCheckoutSession;
    }

   /**
    * Get IP address from request
    *
    * @return mixed
    */
    public function getRequestIp()
    {
        $serverVariables = $this->requestInterface->getServer();
        $remoteAddrHeaders = ['HTTP_X_FORWARDED_HOST', 'HTTP_CLIENT_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];

        foreach ($remoteAddrHeaders as $header) {
            if (property_exists($serverVariables, $header) === true) {
                if (in_array($header, ['HTTP_X_FORWARDED_HOST', 'HTTP_X_FORWARDED_FOR'])) {
                    $forwardedIps = (!empty($serverVariables[$header])) ? explode(",", $serverVariables[$header]) : [];
                    $serverVariables[$header] = trim(end($forwardedIps));
                }

                return $serverVariables[$header];
            }
        }
    }

    /**
     * Get Server IP address
     *
     * @return mixed
     */
    public function getServerAddr()
    {
        return $this->serverAddress->getServerAddress();
    }

    /**
     * Get the formated amount in cents/euro
     *
     * @param mixed $amount
     * @param string $type
     * @return mixed
     */
    public function getFormattedAmount($amount, $type = 'CENT')
    {
        if (!empty($amount)) {
            return ($type == 'RAW') ? number_format($amount / 100, 2, '.', '') : round($amount, 2) * 100;
        }

        return null;
    }

    /**
     * Get payment method session
     *
     * @param  string $paymentMethodCode
     * @param  bool $unset
     * @return mixed
     */
    public function getMethodSession($paymentMethodCode, $unset = false)
    {
        $checkoutSession = $this->checkoutSessionFactory->create();
        if (!$checkoutSession->hasData($paymentMethodCode) || $unset) {
            $checkoutSession->setData($paymentMethodCode, new \Magento\Framework\DataObject([]));
        }

        return $checkoutSession->getData($paymentMethodCode);
    }

    /**
     * Replace strings from the tid passed
     *
     * @param mixed $tid
     * @return mixed
     */
    public function makeValidNumber($tid)
    {
        return preg_replace('/[^0-9]+/', '', $tid);
    }

    /**
     * Check the value is numeric
     *
     * @param  mixed $value
     * @return bool
     */
    public function checkIsNumeric($value)
    {
        if (!empty($value)) {
            return (bool) preg_match('/^\d+$/', $value);
        }

        return false;
    }

    /**
     * Check whether string is serialized
     *
     * @param  mixed $data
     * @return boolean
     */
    public function isSerialized($data)
    {
        if (!empty($data)) {
            $data = trim($data);
            if ($data == 'N;') {
                return true;
            }

            $lastChar = substr($data, -1);
            if (!is_string($data) || strlen($data) < 4 || $data[1] !== ':'
                || ($lastChar !== ';' && $lastChar !== '}')) {
                return false;
            }

            $token = $data[0];
            switch ($token) {
                case 's':
                    if (substr($data, -2, 1) !== '"') {
                        return false;
                    }

                    // no break
                case 'a':
                case 'O':
                    return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
                case 'b':
                case 'i':
                case 'd':
                    return (bool) preg_match("/^{$token}:[0-9.E-]+;$/", $data);
            }
        }

        return false;
    }

    /**
     * Get proper Status Text
     *
     * @param mixed $status
     * @param mixed $order
     * @param string $paymentType
     * @return string
     */
    public function getStatus($status, $order, $paymentType)
    {
        if ($this->checkIsNumeric($status) == true) {
            if (in_array($status, $this->onholdStatus)) {
                $status = 'ON_HOLD';
            } elseif (in_array($status, $this->pendingStatus)) {
                 $status = 'PENDING';
            } elseif ($status == '100') {
                if (in_array(
                    $paymentType,
                    [
                        'INVOICE',
                        'PREPAYMENT',
                        'CASHPAYMENT'
                    ]
                )) {
                    $invoice_id = '';
                    $invoice = [];
                    foreach ($order->getInvoiceCollection() as $invoice) {
                        $invoice_id = $invoice->getIncrementId();
                        $invoice = $invoice->getData();
                    }
                    if (!empty($invoice_id) && (in_array($paymentType, ['PREPAYMENT', 'CASHPAYMENT'])
                        || ($paymentType == 'INVOICE' && $invoice['state'] == 2))) {
                        $status = 'CONFIRMED';
                    } else {
                        $status = 'PENDING';
                    }
                } else {
                    $status = 'CONFIRMED';
                }
            } elseif ($status == '103') {
                $status = 'DEACTIVATED';
            } else {
                $status = 'FAILURE';
            }
        }

        return $status;
    }

    /**
     * Get Country Code
     *
     * @param int $store
     * @return string
     */
    public function getCountryCode($store = null)
    {
        return $this->directoryHelper->getDefaultCountry($store);
    }

    /**
     * Validate Novalnet basic params
     *
     * @return bool
     */
    public function validateBasicParams()
    {
        return ($this->novalnetConfig->getGlobalConfig('signature') &&
            $this->novalnetConfig->getGlobalConfig('payment_access_key') &&
            $this->checkIsNumeric($this->novalnetConfig->getGlobalConfig('tariff_id')));
    }

    /**
     * Get region name by code
     *
     * @param string $regionCode
     * @param string $countryCode
     * @return string
     */
    public function getRegionNameByCode($regionCode, $countryCode)
    {
        try {
            $regionName = $this->regionModel->loadByCode($regionCode, $countryCode)->getName();

            if (!empty($regionName)) {
                return $regionName;
            }

            return $regionCode;
        } catch (\Exception $e) {
            return $regionCode;
        }
    }

    /**
     * Get Street from address
     *
     * @param object $address
     * @return string
     */
    public function getStreet($address)
    {
        if (method_exists($address, 'getStreetFull')) {
            $street = $address->getStreetFull();
        } else {
            if ($address->getStreetLine1()) {
                $street = implode(' ', [$address->getStreetLine1(), $address->getStreetLine2()]);
            } else {
                $street = (!empty($address->getStreet())) ? implode(' ', $address->getStreet()) : '';
            }
        }

        return $street;
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
     * Get Formatted shipping Address
     *
     * @param array $info
     * @return array
     */
    public function getFormattedAddress($info)
    {
        $regionName = (!empty($info['administrativeArea'])) ? $info['administrativeArea'] : $info['locality'];
        $countryName = (!empty($info['countryCode'])) ? $info['countryCode'] : '';

        return [
            'firstname' => !empty($info['firstName']) ? $info['firstName'] : '',
            'lastname' => !empty($info['lastName']) ? $info['lastName'] : '',
            'company' => '',
            'email' => !empty($info['email']) ? $info['email'] : '',
            'street' => !empty($info['addressLines']) ? $info['addressLines'] : 'Unspecified Street',
            'city' => !empty($info['locality']) ? $info['locality'] : '',
            'region_id' => $this->getRegionIdByName($regionName, $countryName),
            'region' => $regionName,
            'postcode' => !empty($info['postalCode']) ? $info['postalCode'] : '',
            'country_id' => $countryName,
            'telephone' => !empty($info['phoneNumber']) ? $info['phoneNumber'] : 'Unspecified Telephone',
            'fax' => ''
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
     * Save payment token
     *
     * @param mixed $order
     * @param string $paymentMethodCode
     * @param mixed $response
     * @return void
     */
    public function savePaymentToken($order, $paymentMethodCode, $response)
    {
        $transactionStatusCollection = $this->transactionStatusModel->getCollection()
            ->setPageSize(2)
            ->setOrder('id', 'DESC')
            ->addFieldToFilter('token', ['null' => false])
            ->addFieldToFilter('customer_id', $order->getCustomerId())
            ->addFieldToFilter('payment_method', ['like' => $paymentMethodCode . '%']);
        // Override the old token if two tokens already exist
        if ($transactionStatusCollection->count() >= 2) {
            $this->transactionStatusModel->setEntityId($transactionStatusCollection->getFirstItem()->getId());
        }

        $responseTokens = ['transaction/payment_data/card_brand', 'transaction/payment_data/card_holder', 'transaction/payment_data/card_expiry_month', 'transaction/payment_data/card_expiry_year', 'transaction/payment_data/card_number', 'transaction/payment_data/iban', 'transaction/payment_data/account_holder', 'transaction/payment_data/paypal_account', 'transaction/payment_data/paypal_transaction_id'];

        foreach ($responseTokens as $responseToken) {
            if ($response->getData($responseToken)) {
                $responseTokenFormatted = preg_replace('/(?:transaction\/payment_data)\/(.*)/', ucwords('$1'), $responseToken);
                $responseTokenFormatted = str_replace('_', '', ucwords($responseTokenFormatted, '_'));
                $tokenInfo['Nn' . $responseTokenFormatted] = $response->getData($responseToken);
            }
        }

        $this->transactionStatusModel->setToken($response->getData('transaction/payment_data/token'));
        if (!empty($tokenInfo)) {
            $this->transactionStatusModel->setTokenInfo($this->jsonEncode($tokenInfo));
        }
        $this->transactionStatusModel->save();
    }

    /**
     * To check the data is valid JSON
     *
     * @param mixed $data
     * @return bool
     */
    public function isJson($data)
    {
        return !empty($data) ? $this->jsonValidator->isValid($data) : false;
    }

    /**
     * To decode JSON to Array
     *
     * @param string $data
     * @return string|int|float|bool|array|null
     */
    public function jsonDecode($data)
    {
        return !empty($data) ? $this->jsonSerializer->unserialize($data) : [];
    }

    /**
     * To encode array to JSON
     *
     * @param string|int|float|bool|array|null $data
     * @return string|bool
     */
    public function jsonEncode($data)
    {
        return !empty($data) ? $this->jsonSerializer->serialize($data) : '{}';
    }

    /**
     * Validates and returns payment additional data array
     *
     * @param mixed $data
     * @return array
     */
    public function getPaymentAdditionalData($data)
    {
        $additionalData = [];
        if (!empty($data)) {
            $additionalData = ($this->isSerialized($data)) ? $this->serializer->unserialize($data) : $this->jsonDecode($data);
        }

        return $additionalData;
    }
}

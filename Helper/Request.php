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

/**
 * Novalnet payment request helper
 *
 */
class Request extends \Magento\Framework\App\Helper\AbstractHelper
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
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

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
     * @var Magento\Framework\Stdlib\DateTime\TimezoneInterface
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
     * @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @var \Novalnet\Payment\Model\TransactionStatus
     */
    protected $transactionStatusModel;

    /**
     * @var \Novalnet\Payment\Model\NNConfig
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
     * @array OnHold statuses
     */
    protected $onholdStatus = [ '91', '98', '99', '85'];

    /**
     * @array Pending Statuses
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
     * @var \Magento\Tax\Helper\Data
     */
    private $taxHelper;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param \Magento\Framework\App\ProductMetadata $productMetadata
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\App\RequestInterface $requestInterface
     * @param \Magento\Framework\Locale\ResolverInterface $resolverInterface
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Backend\Model\Session\Quote $adminCheckoutSession
     * @param \Magento\Checkout\Model\SessionFactory $checkoutSessionFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderEmailSender
     * @param \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder
     * @param \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel
     * @param \Novalnet\Payment\Model\NNConfig $novalnetConfig
     * @param \Magento\Framework\HTTP\PhpEnvironment\ServerAddress $serverAddress
     * @param \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Tax\Helper\Data $taxHelper
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
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Backend\Model\Session\Quote $adminCheckoutSession,
        \Magento\Checkout\Model\SessionFactory $checkoutSessionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderEmailSender,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel,
        \Novalnet\Payment\Model\NNConfig $novalnetConfig,
        \Magento\Framework\HTTP\PhpEnvironment\ServerAddress $serverAddress,
        \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Tax\Helper\Data $taxHelper
    ) {
        parent::__construct($context);
        $this->appState = $appState;
        $this->pricingHelper = $pricingHelper;
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
        $this->requestInterface = $requestInterface;
        $this->resolverInterface = $resolverInterface;
        $this->serializer = $serializer;
        $this->jsonHelper = $jsonHelper;
        $this->countryFactory = $countryFactory;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->adminCheckoutSession = $adminCheckoutSession;
        $this->checkoutSessionFactory = $checkoutSessionFactory;
        $this->dateTime = $dateTime;
        $this->invoiceSender = $invoiceSender;
        $this->orderEmailSender = $orderEmailSender;
        $this->transactionBuilder = $transactionBuilder;
        $this->transactionStatusModel = $transactionStatusModel;
        $this->novalnetConfig = $novalnetConfig;
        $this->serverAddress = $serverAddress;
        $this->novalnetLogger = $novalnetLogger;
        $this->directoryHelper = $directoryHelper;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->taxHelper = $taxHelper;
    }

    /**
     * @param string|null $payment_access_key
     * @return string
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
     * build addtional data for redirect payment
     *
     * @param  array $response
     * @return array
     */
    public function buildRedirectAdditionalData($response)
    {
        if ($response->getData('transaction/txn_secret')) {
            $additionalData['NnRedirectURL'] = $response->getData('result/redirect_url');
            $additionalData['NnTxnSecret'] = $response->getData('transaction/txn_secret');
            return $additionalData;
        }
    }

    /**
     * build addtional data for payment
     *
     * @param  array $response
     * @param  DataObject $payment
     * @return array
     */
    public function buildAdditionalData($response, $payment)
    {
        if ($payment->getAdditionalData()) {
            $additionalData = $this->isSerialized($payment->getAdditionalData())
                ? $this->serializer->unserialize($payment->getAdditionalData())
                : json_decode($payment->getAdditionalData(), true);

            if (isset($additionalData['NnRedirectURL'])) {
                unset($additionalData['NnRedirectURL']);
            }
        }

        $paymentMethodCode = $this->novalnetConfig->getPaymentCodeByType($response->getData('transaction/payment_type'));

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

        if (!empty($additionalData['NnDueDate'])) {
            $additionalData['NnDueDate'] = $this->dateTime->formatDate(
                $additionalData['NnDueDate'],
                \IntlDateFormatter::LONG
            );
        }

        if ($response->getData('transaction/bank_details')) {
            $additionalData['NnInvoiceComments'] = $this->getInvoiceComments($response);
        }

        if ($paymentMethodCode == ConfigProvider::NOVALNET_CASHPAYMENT) {
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

        $order = $payment->getOrder();
        $this->saveTransactionDetails($response, $paymentMethodCode);

        return $additionalData;
    }

    /**
     * Retrieves Novalnet Invoice Comments
     *
     * @param  DataObject $response
     * @return array
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
            $invoicePaymentsNote .= '|Payment reference 1:' . $response->getData('transaction/invoice_ref');
            $invoicePaymentsNote .= '|Payment reference 2:' . $response->getData('transaction/tid');
        }

        return $invoicePaymentsNote;
    }

    /**
     * Saves transaction details into Novalnet table
     *
     * @param  array $response
     * @param  String $paymentMethodCode
     * @return none
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
     * check payment hash for redirect payments
     *
     * @param  array $response
     * @param  array $additionalData
     * @return bool
     */
    public function checkPaymentHash($response, $additionalData)
    {
        $accessKey = trim($this->novalnetConfig->getGlobalConfig('payment_access_key'));
        $checksumString = $response['tid'] . $additionalData['NnTxnSecret'] . $response['status']
            . strrev($accessKey);
        $generatedChecksum = hash('sha256', $checksumString);

        if ($generatedChecksum !== $response['checksum']) {
            return false;
        }

        return true;
    }

    /**
     * check return data for redirect payments and update order accordingly
     *
     * @param  array $response
     * @param  DataObject $order
     * @param  DataObject $payment
     * @return bool
     */
    public function checkReturnedData($response, $order, $payment)
    {
        if ($response->getData('transaction/status') == 'FAILURE') {
            $this->saveCanceledOrder($response, $order);
            return false;
        }
        $storeId = $order->getStoreId();
        $paymentMethodCode = $payment->getMethodInstance()->getCode();
        // Retrieves additional payment data for the order
        $additionalData = $this->buildAdditionalData($response, $payment);
        $amount = $this->getFormattedAmount($response->getData('transaction/amount'), 'RAW');
        $payment->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();
        $setOrderStatus = Order::STATE_HOLDED;

        if ($response->getData('transaction/status') == 'CONFIRMED') {
            // capture transaction
            $payment->setTransactionId($additionalData['NnTid'])
                ->setLastTransId($additionalData['NnTid'])
                ->capture(null)
                ->setAmount($amount)
                ->setIsTransactionClosed(false)
                ->setShouldCloseParentTransaction(false)
                ->save();
            $setOrderStatus = $this->novalnetConfig->getPaymentConfig(
                $paymentMethodCode,
                'order_status',
                $storeId
            );
        } else {
            // authorize transaction
            $payment->authorize(true, $amount)->save();
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

        return true;
    }

    /**
     * Save canceled payment transaction
     *
     * @param  array $response
     * @param  DataObject $order
     * @param  String|null $statusText
     * @return none
     */
    public function saveCanceledOrder($response, $order, $statusText = false)
    {
        $payment = $order->getPayment();
        // Get payment transaction status message
        $statusMessage = ($statusText)
            ? $statusText : $response->getData('result/status_text');
        $additionalData = $payment->getAdditionalData() ? ($this->isSerialized($payment->getAdditionalData())
                ? $this->serializer->unserialize($payment->getAdditionalData())
                : json_decode($payment->getAdditionalData(), true)) : [];
        $additionalData['NnTid'] = $response->getData('transaction/tid');
        $additionalData['NnStatus'] = $response->getData('transaction/status');
        $additionalData['NnTestMode'] = $response->getData('transaction/test_mode');
        $additionalData['NnComments'] = '<b><font color="red">' . __('Payment Failed') . '</font> - '
            . $statusMessage . '</b>';
        $payment->setLastTransId($additionalData['NnTid'])
            ->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))
            ->save();

        // UnHold and Cancel the order with the cancel text
        if ($order->canUnhold()) {
            $order->unhold();
        }

        $order->registerCancellation($statusMessage)->save();
    }

    /**
     * Save payment token
     *
     * @param  DataObject $order
     * @param  string $paymentMethodCode
     * @param  array $response
     * @return none
     */
    public function savePaymentToken($order, $paymentMethodCode, $response)
    {
        if ($this->novalnetConfig->isOneClickPayment($paymentMethodCode)) {
            if (strpos($paymentMethodCode, ConfigProvider::NOVALNET_SEPA) !== false) {
                $paymentMethodCode = ConfigProvider::NOVALNET_SEPA;
            }
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
                $this->transactionStatusModel->setTokenInfo($this->jsonHelper->jsonEncode($tokenInfo));
            }
            $this->transactionStatusModel->save();
        }
    }

    /**
     * Restore cart items
     *
     * @param  $orderId
     * @return none
     */
    public function restoreQuote($orderId)
    {
        $this->checkoutSession->restoreQuote();
        $this->checkoutSession->setLastRealOrderId($orderId);
    }

    /**
     * Retrieve Magento version
     *
     * @param  none
     * @return int
     */
    public function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Retrieve Novalnet version
     *
     * @param  none
     * @return int
     */
    public function getNovalnetVersion()
    {
        return $this->moduleList->getOne('Novalnet_Payment')['setup_version'];
    }

    /**
     * Get shop default language
     *
     * @param  none
     * @return string
     */
    public function getDefaultLanguage()
    {
        $defaultlocale = explode('_', $this->resolverInterface->getDefaultLocale());
        return (is_array($defaultlocale) && !empty($defaultlocale)) ? $defaultlocale[0] : 'en';
    }

    /**
     * Retrieves customer session model
     *
     * @param  none
     * @return boolean
     */
    public function getCustomerSession()
    {
        return $this->customerSession;
    }

    /**
     * Retrieve customer id from current session
     *
     * @param  none
     * @return int|string
     */
    public function getCustomerId()
    {
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
     * @param  none
     * @return boolean
     */
    public function isAdmin()
    {
        return (bool)($this->appState->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE);
    }

    /**
     * Retrieves admin checkout session model
     *
     * @param  none
     * @return boolean
     */
    public function getAdminCheckoutSession()
    {
        return $this->adminCheckoutSession;
    }

    /**
     * Retrieves Order Amount
     *
     * @param  none
     * @return int
     */
    public function getAmount()
    {
        $quote = (!$this->isAdmin())
            ? $this->checkoutSession->getQuote()
            : $this->adminCheckoutSession->getQuote();
        return $this->getFormattedAmount($quote->getGrandTotal());
    }

    /**
     * Retrieves Retrieves Billing Address from checkout session model
     *
     * @param  none
     * @return array
     */
    public function getBillingAddress()
    {
        $quote = (!$this->isAdmin())
            ? $this->checkoutSession->getQuote()
            : $this->adminCheckoutSession->getQuote();
        $billingAddress = $quote->getBillingAddress();
        $firstName = ($billingAddress->getFirstname())
            ? $billingAddress->getFirstname()
            : (($quote->getCustomerFirstname())
            ?  $quote->getCustomerFirstname()
            : "");
        $lastName = ($billingAddress->getLastname())
            ? $billingAddress->getLastname()
            : (($quote->getCustomerLastname())
            ?  $quote->getCustomerLastname()
            : "");
        $billing = ['firstname' => $firstName,
            'lastname' => $lastName,
            'street' => $billingAddress->getStreet(),
            'city' => $billingAddress->getCity(),
            'country_id' => $billingAddress->getCountryId(),
            'email' => $billingAddress->getEmail(),
            'postcode' => $billingAddress->getPostcode(),
        ];
        return $billing;
    }

    /**
     * Retrieves Shipping Address from checkout session model
     *
     * @param  none
     * @return array
     */
    public function getShippingAddress()
    {
        $quote = (!$this->isAdmin())
            ? $this->checkoutSession->getQuote()
            : $this->adminCheckoutSession->getQuote();
        $shippingAddress = $quote->getShippingAddress();
        $firstName = ($shippingAddress->getFirstname())
            ? $shippingAddress->getFirstname()
            : (($quote->getCustomerFirstname())
            ?  $quote->getCustomerFirstname()
            : "");
        $lastName = ($shippingAddress->getLastname())
            ? $shippingAddress->getLastname()
            : (($quote->getCustomerLastname())
            ?  $quote->getCustomerLastname()
            : "");
        $shipping = ['firstname' => $firstName,
            'lastname' => $lastName,
            'street' => $shippingAddress->getStreet(),
            'city' => $shippingAddress->getCity(),
            'country_id' => $shippingAddress->getCountryId(),
            'email' => $shippingAddress->getEmail(),
            'postcode' => $shippingAddress->getPostcode(),
            'same_as_billing' => $shippingAddress->getSameAsBilling(),
        ];
        return $shipping;
    }

    /**
     * Retrieves account holder name from the billing address
     *
     * @param  none
     * @return string
     */
    public function getAccountHolder()
    {
        $quote = (!$this->isAdmin())
            ? $this->checkoutSession->getQuote()
            : $this->adminCheckoutSession->getQuote();
        $billingAddress = $quote->getBillingAddress();

        if ($billingAddress->getFirstname() && $billingAddress->getLastname()) {
            return $billingAddress->getFirstname() .' '. $billingAddress->getLastname();
        } elseif ($quote->getCustomerFirstname() && $quote->getCustomerLastname()) {
            return $quote->getCustomerFirstname() .' '. $quote->getCustomerLastname();
        } else {
            return '';
        }
    }

    /**
     * Retrieves Company from the billing address
     *
     * @param  none
     * @return string
     */
    public function getCustomerCompany()
    {
        $billingaddress = (!$this->isAdmin())
            ? $this->checkoutSession->getQuote()->getBillingAddress()
            : $this->adminCheckoutSession->getQuote()->getBillingAddress();

        return $billingaddress->getCompany();
    }

    /**
     * Get IP address from request
     *
     * @param  none
     * @return int
     */
    public function getRequestIp()
    {
        $serverVariables = $this->requestInterface->getServer();
        $remoteAddrHeaders = ['HTTP_X_FORWARDED_HOST', 'HTTP_CLIENT_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];

        foreach ($remoteAddrHeaders as $header) {
            if (property_exists($serverVariables, $header) === true) {
                if (in_array($header, ['HTTP_X_FORWARDED_HOST', 'HTTP_X_FORWARDED_FOR'])) {
                    $forwardedIps = explode(",", $serverVariables[$header]);
                    $serverVariables[$header] = trim(end($forwardedIps));
                }

                return $serverVariables[$header];
            }
        }
    }

    /**
     * Get Server IP address
     *
     * @param  none
     * @return int
     */
    public function getServerAddr()
    {
        return $this->serverAddress->getServerAddress();
    }

    /**
     * Get the formated amount in cents/euro
     *
     * @param  float  $amount
     * @param  string $type
     * @return int
     */
    public function getFormattedAmount($amount, $type = 'CENT')
    {
        return ($type == 'RAW') ? number_format($amount / 100, 2, '.', '') : round($amount, 2) * 100;
    }

    /**
     * Get the Amount with symbol
     *
     * @param  float  $amount
     * @param  int  $storeId
     * @return string
     */
    public function getAmountWithSymbol($amount, $storeId)
    {
        return $this->pricingHelper->currencyByStore($amount, $storeId);
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
     * @return integer
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
        return (bool) preg_match('/^\d+$/', $value);
    }

    /**
     * Check whether string is serialized
     *
     * @param  mixed $data
     * @return boolean
     */
    public function isSerialized($data)
    {
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

        return false;
    }

    /**
     * Get proper Status Text
     *
     * @param  mixed $status
     * @param  DataObject $order
     * @return string
     */
    public function getStatus($status, $order)
    {
        if ($this->checkIsNumeric($status) == true) {
            if (in_array($status, $this->onholdStatus)) {
                $status = 'ON_HOLD';
            } elseif (in_array($status, $this->pendingStatus)) {
                 $status = 'PENDING';
            } elseif ($status == '100') {
                $payment = $order->getPayment();
                $paymentCode = $payment->getMethodInstance()->getCode();
                if (in_array(
                    $paymentCode,
                    [
                        ConfigProvider::NOVALNET_INVOICE,
                        ConfigProvider::NOVALNET_PREPAYMENT,
                        ConfigProvider::NOVALNET_CASHPAYMENT
                    ]
                )) {
                    $invoice_id = '';
                    $invoice = [];
                    foreach ($order->getInvoiceCollection() as $invoice) {
                        $invoice_id = $invoice->getIncrementId();
                        $invoice = $invoice->getData();
                    }
                    if (!empty($invoice_id) && (in_array($paymentCode, [ConfigProvider::NOVALNET_PREPAYMENT, ConfigProvider::NOVALNET_CASHPAYMENT])
                        || ($paymentCode == ConfigProvider::NOVALNET_INVOICE && $invoice['state'] == 2))) {
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
     * Validate Customer Company param
     *
     * @param  string $paymentMethod
     * @return boolean
     */
    public function validateCompany($paymentMethod)
    {
        $company = $this->getCustomerCompany();
        if (!empty($company) && $this->novalnetConfig->getPaymentConfig($paymentMethod, 'allow_b2b_customer')) {
            if (preg_match('/^(?:\d+|(?:)\.?|[^a-zA-Z0-9]+|[a-zA-Z]{1})$|^(herr|frau|jahr|mr|miss|mrs|others|andere|anrede|salutation|null|none|keine|company|firma|no|na|n\/a|test|private|privat)$/i', $company)) {
                return true;
            } else {
                return false;
            }
        }
        return true;
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
     * Get Payment Enabled pages for applepay
     * @param string $page
     *
     * @return bool
     */
    public function isPageEnabledForApplePay($page)
    {
        $isApplePayEnabled = $this->novalnetConfig->getPaymentConfig(ConfigProvider::NOVALNET_APPLEPAY, 'active');
        $enabled_pages = explode(',', $this->novalnetConfig->getPaymentConfig(ConfigProvider::NOVALNET_APPLEPAY, 'enabled_pages'));
        return $isApplePayEnabled && in_array($page, $enabled_pages) && $this->canUseForGuestCheckout();
    }

    /**
     * can payment used for guest checkout
     * @param none
     *
     * @return bool
     */
    public function canUseForGuestCheckout()
    {
        if ($this->getCustomerSession()->isLoggedIn()) {
            return true;
        };

        return $this->scopeConfig->getValue(
            'checkout/options/guest_checkout',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );
    }

    /**
     * Get Applepay sheet Configurations
     *
     * @param  none
     * @return array
     */
    public function paymentSheetConfigurations()
    {
        return [
            'btnStyle' => $this->novalnetConfig->getPaymentConfig(ConfigProvider::NOVALNET_APPLEPAY, 'button_style'),
            'btnTheme' => $this->novalnetConfig->getPaymentConfig(ConfigProvider::NOVALNET_APPLEPAY, 'button_theme'),
            'btnHeight' => $this->novalnetConfig->getPaymentConfig(ConfigProvider::NOVALNET_APPLEPAY, 'button_height'),
            'btnRadius' => $this->novalnetConfig->getPaymentConfig(ConfigProvider::NOVALNET_APPLEPAY, 'button_corner_radius'),
            'countryCode' => $this->getCountryCode(),
            'currencyCode' => $this->storeManager->getStore()->getBaseCurrencyCode(),
            'langCode' => $this->getDefaultLanguage(),
            'sellerName' => $this->novalnetConfig->getPaymentConfig(ConfigProvider::NOVALNET_APPLEPAY, 'seller_name'),
            'clientKey' => $this->novalnetConfig->getGlobalConfig('client_key')
        ];
    }

    /**
     * Get tax calculation settings and set apple pay amount type
     *
     * @param  none
     * @return bool
     */
    public function isApplePayAmountPending()
    {
        return ($this->taxHelper->getTaxBasedOn() == 'billing') ? true : false;
    }
}

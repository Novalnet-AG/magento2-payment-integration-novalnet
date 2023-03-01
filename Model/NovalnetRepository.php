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
use Novalnet\Payment\Model\NNConfig;
use Novalnet\Payment\Model\Ui\ConfigProvider;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\CouldNotSaveException;
use \Magento\Checkout\Model\Type\Onepage;
use \Magento\Customer\Api\Data\GroupInterface;

class NovalnetRepository implements \Novalnet\Payment\Api\NovalnetRepositoryInterface
{
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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private $clientFactory;

    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    private $novalnetHelper;

    /**
     * @var NNConfig
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
     * @var \Novalnet\Payment\Logger\NovalnetLogger
     */
    private $novalnetLogger;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    private $cart;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    private $quoteManagement;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    private $orderCollection;

    /**
     * @var \Magento\Framework\View\Design\Theme\ThemeProviderInterface
     */
    private $themeProvider;

    /**
     * @param \Magento\Sales\Model\Order $salesOrderModel
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param \Magento\Framework\App\RequestInterface $requestInterface
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\HTTP\Client\Curl $clientFactory
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     * @param NNConfig $novalnetConfig
     * @param \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Quote\Api\CartManagementInterface $quoteManagement
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection
     * @param \Magento\Framework\View\Design\Theme\ThemeProviderInterface $themeProvider
     */
    public function __construct(
        Order $salesOrderModel,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\App\RequestInterface $requestInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\HTTP\Client\Curl $clientFactory,
        \Novalnet\Payment\Helper\Data $novalnetHelper,
        NNConfig $novalnetConfig,
        \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection,
        \Magento\Framework\View\Design\Theme\ThemeProviderInterface $themeProvider
    ) {
        $this->salesOrderModel = $salesOrderModel;
        $this->urlInterface = $urlInterface;
        $this->requestInterface = $requestInterface;
        $this->storeManager = $storeManager;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->clientFactory = $clientFactory;
        $this->novalnetHelper = $novalnetHelper;
        $this->novalnetConfig = $novalnetConfig;
        $this->novalnetLogger = $novalnetLogger;
        $this->eventManager = $eventManager;
        $this->cart = $cart;
        $this->quoteManagement = $quoteManagement;
        $this->quoteFactory = $quoteFactory;
        $this->orderCollection = $orderCollection;
        $this->themeProvider = $themeProvider;
    }

    /**
     * To get v3 payment form link
     *
     * @api
     * @param string[] $data
     * @return string
     */
    public function getPayByLink($data)
    {
        $quoteId = !empty($data['quote_id']) ? $data['quote_id'] : '';
        if (!$this->customerSession->isLoggedIn()) {
            $quoteMaskData = $this->quoteIdMaskFactory->create()->load($quoteId, 'masked_id');
            $quoteId = $quoteMaskData->getQuoteId();
        }

        return $this->buildPayBylinkRequest($quoteId);
    }

    /**
     * To check the data is valid JSON
     *
     * @param mixed $quoteId
     * @param bool $isAdmin
     * @return string
     */
    public function buildPayBylinkRequest($quoteId, $isAdmin = false)
    {
        if (empty($quoteId)) {
            return $this->novalnetHelper->jsonEncode([
                'result' => [
                    'status' => 'FAILURE',
                    'message' => 'Quote Id is empty!'
                ]
            ]);
        }

        $quote = $this->quoteFactory->create()->load($quoteId);
        $quote->reserveOrderId();
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
        $billingStreet = $this->novalnetHelper->getStreet($billingAddress);
        $storeId = $quote->getStoreId();
        $data = [];

        $data['merchant'] = [
            'signature' => $this->novalnetConfig->getGlobalConfig('signature', $storeId),
            'tariff'    => $this->novalnetConfig->getGlobalConfig('tariff_id', $storeId),
        ];

        if (!empty($billingAddress)) {
            $data['customer'] = [
                'first_name'  => $billingAddress->getFirstname(),
                'last_name'   => $billingAddress->getLastname(),
                'email'       => $billingAddress->getEmail(),
                'tel'         => $billingAddress->getTelephone(),
                'customer_ip' => $this->novalnetHelper->getRequestIp(),
                'customer_no' => $this->novalnetHelper->getCustomerId(),
            ];

            $data['customer']['billing'] = [
                'street'       => $billingStreet,
                'city'         => $billingAddress->getCity(),
                'zip'          => $billingAddress->getPostcode(),
                'country_code' => $billingAddress->getCountryId(),
                'state'        => $this->novalnetHelper->getRegionNameByCode($billingAddress->getRegionCode(), $billingAddress->getCountryId())
            ];

            if (!empty($billingAddress->getCompany())) {
                $data['customer']['billing']['company'] = $billingAddress->getCompany();
            }

        }

        if (!empty($shippingAddress)) {
            $shippingStreet = $this->novalnetHelper->getStreet($shippingAddress);
            if ($billingStreet ==  $shippingStreet &&
                $billingAddress->getCity() == $shippingAddress->getCity() &&
                $billingAddress->getCountryId() ==$shippingAddress->getCountryId() &&
                $billingAddress->getPostcode() == $shippingAddress->getPostcode()
            ) {
                $data['customer']['shipping']['same_as_billing'] = 1;
            } else {
                $data['customer']['shipping'] = [
                    'first_name'   => $shippingAddress->getFirstname(),
                    'last_name'    => $shippingAddress->getLastname(),
                    'email'        => $shippingAddress->getEmail(),
                    'tel'          => $shippingAddress->getTelephone(),
                    'street'       => $shippingStreet,
                    'city'         => $shippingAddress->getCity(),
                    'zip'          => $shippingAddress->getPostcode(),
                    'country_code' => $shippingAddress->getCountryId(),
                    'state'        => $this->novalnetHelper->getRegionNameByCode($shippingAddress->getRegionCode(), $shippingAddress->getCountryId())
                ];
                if (!empty($shippingAddress->getCompany())) {
                    $data['customer']['shipping']['company'] = $shippingAddress->getCompany();
                }
            }
        }

        $theme = $this->themeProvider->getThemeById($this->novalnetConfig->getThemeId($storeId));
        $themeName = str_replace(' ', '_', $theme->getData('theme_title'));

        $data['transaction'] = [
            'amount'           => $this->novalnetHelper->getFormattedAmount($quote->getBaseGrandTotal()),
            'currency'         => $quote->getBaseCurrencyCode(),
            'system_ip'        => $this->novalnetHelper->getServerAddr(),
            'system_name'      => 'Magento',
            'system_url'       => $this->storeManager->getStore()->getBaseUrl(),
            'system_version'   => $this->novalnetHelper->getMagentoVersion() . '-NN' .
                                    $this->novalnetHelper->getNovalnetVersion() . '-NNT' . $themeName
        ];

        $data['hosted_page'] = [
            'hide_blocks'      => ['ADDRESS_FORM', 'SHOP_INFO', 'LANGUAGE_MENU', 'TARIFF','HEADER'],
            'skip_pages'       => ['CONFIRMATION_PAGE', 'SUCCESS_PAGE', 'PAYMENT_PAGE'],
            'form_version' => NNConfig::NOVALNET_FORM_VERSION,
            'type' => 'PAYMENTFORM'
        ];

        if ($isAdmin) {
            $data['transaction']['system_version'] = $this->novalnetHelper->getMagentoVersion() . '-NN' .
                                    $this->novalnetHelper->getNovalnetVersion() . '-NNT' . NNConfig::MAGENTO_BACKEND_THEME;
            $data['hosted_page']['display_payments_mode'] = ['DIRECT'];
        }

        $data['custom'] = [
            'lang' => $this->novalnetHelper->getDefaultLanguage(),
        ];

        $this->clientFactory->setHeaders(
            $this->novalnetHelper->getRequestHeaders()
        );

        $this->clientFactory->post(NNConfig::NOVALNET_SEAMLESS_PAYMENT_URL, $this->novalnetHelper->jsonEncode($data));

        return $this->clientFactory->getBody();
    }

    /**
     * To get current customer last Novalnet transaction
     *
     * @return string|null
     */
    public function getCustomerLastOrderPaymentMethod()
    {
        $paymentType = null;
        if ($customerId = $this->novalnetHelper->getCustomerSession()->getCustomer()->getId()) {
            $orderCollection = $this->orderCollection->addAttributeToFilter('customer_id', $customerId)->setOrder('created_at', 'desc');
            if (!empty($orderCollection)) {
                foreach ($orderCollection as $order) {
                    if ($order->getPayment()->getMethod() == 'novalnetPay') {
                        $additionalData = $this->novalnetHelper->getPaymentAdditionalData($order->getPayment()->getAdditionalData());
                        $paymentType = !empty($additionalData['NnPaymentType']) ? $additionalData['NnPaymentType'] : null;
                        break;
                    }
                }
            }
        }

        return $paymentType;
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
        $data['custom'] = ['lang' => $this->novalnetHelper->getDefaultLanguage()];
        $this->clientFactory->setHeaders(
            $this->novalnetHelper->getRequestHeaders($payment_access_key)
        );
        $this->clientFactory->post(NNConfig::NOVALNET_MERCHANT_DETAIL_URL, $this->novalnetHelper->jsonEncode($data));
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
            return $this->novalnetHelper->jsonEncode($data);
        }
        $data['merchant'] = ['signature' => $signature];
        $data['custom'] = ['lang' => $this->novalnetHelper->getDefaultLanguage()];
        $data['webhook'] = ['url' => $webhook_url];
        $this->clientFactory->setHeaders(
            $this->novalnetHelper->getRequestHeaders($payment_access_key)
        );
        $this->clientFactory->post(NNConfig::NOVALNET_WEBHOOK_CONFIG_URL, $this->novalnetHelper->jsonEncode($data));
        $response = (!empty($this->clientFactory->getBody())) ? $this->novalnetHelper->jsonDecode($this->clientFactory->getBody()) : [];

        return $this->clientFactory->getBody();
    }

    /**
     * Returns URL to redirect after place order
     *
     * @api
     * @param string[] $data
     * @return string
     */
    public function getRedirectURL($data)
    {
        $quoteId = !empty($data['quote_id']) ? $data['quote_id'] : '';
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
        $additionalData = $this->novalnetHelper->getPaymentAdditionalData($payment->getAdditionalData());
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
     * Place Order
     *
     * @api
     * @param string[] $data
     * @param bool $paymentPage
     * @return string
     */
    public function placeOrder($data, $paymentPage = false)
    {
        $data = (!empty($data['result'])) ? $this->novalnetHelper->jsonDecode($data['result']) : [];
        try {
            $quote = $this->cart->getQuote();
            $quote->reserveOrderId()->save();
            $billingAddress = $data['booking_details']['order']['billing']['contact'];

            if (empty($billingAddress['email']) && !empty($data['booking_details']['order']['shipping']['contact']['email'])) {
                $billingAddress['email'] = $data['booking_details']['order']['shipping']['contact']['email'];
            }

            if (empty($billingAddress['phoneNumber']) && !empty($data['booking_details']['order']['shipping']['contact']['phoneNumber'])) {
                $billingAddress['phoneNumber'] = $data['booking_details']['order']['shipping']['contact']['phoneNumber'];
            }

            // set billing address
            $quote->getBillingAddress()->addData($this->novalnetHelper->getFormattedAddress($billingAddress));

            if (!$paymentPage && !$quote->isVirtual()) {
                $shippingAddress = $data['booking_details']['order']['shipping']['contact'];

                if (empty($shippingAddress['email']) && !empty($billingAddress['email'])) {
                    $shippingAddress['email'] = $billingAddress['email'];
                }

                if (empty($shippingAddress['phoneNumber']) && !empty($billingAddress['phoneNumber'])) {
                    $shippingAddress['phoneNumber'] = $billingAddress['phoneNumber'];
                }

                $shippingIdentifier = $data['booking_details']['order']['shipping']['method']['identifier'];

                // set shipping address and shipping method
                $quote->getShippingAddress()
                    ->addData($this->novalnetHelper->getFormattedAddress($shippingAddress))
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

            //set payment additional data
            $quote->getPayment()->importData([
                'method' => ConfigProvider::NOVALNET_PAY,
                'additional_data' => [
                    ConfigProvider::NOVALNET_PAY . '_payment_data' => $this->novalnetHelper->jsonEncode($data)
                ]
            ]);

            $quote->collectTotals()->save();

            $this->eventManager->dispatch(
                'checkout_submit_before',
                ['quote' => $quote]
            );

            //submit current quote to place order
            $order = $this->quoteManagement->submit($quote);

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

            $payment = $order->getPayment();
            $additionalData = $this->novalnetHelper->getPaymentAdditionalData($payment->getAdditionalData());
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

            return $this->novalnetHelper->jsonEncode([
                    'redirectUrl' => $redirectURL
                ]);

        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }
    }
}

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
namespace Novalnet\Payment\Model\Ui;

use Novalnet\Payment\Model\NNConfig;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    public const NOVALNET_CC = 'novalnetCc';
    public const NOVALNET_SEPA = 'novalnetSepa';
    public const NOVALNET_INVOICE = 'novalnetInvoice';
    public const NOVALNET_PREPAYMENT = 'novalnetPrepayment';
    public const NOVALNET_INVOICE_GUARANTEE = 'novalnetInvoiceGuarantee';
    public const NOVALNET_SEPA_GUARANTEE = 'novalnetSepaGuarantee';
    public const NOVALNET_CASHPAYMENT = 'novalnetCashpayment';
    public const NOVALNET_PAYPAL = 'novalnetPaypal';
    public const NOVALNET_BANKTRANSFER = 'novalnetBanktransfer';
    public const NOVALNET_ONLINEBANKTRANSFER = 'novalnetOnlineBanktransfer';
    public const NOVALNET_IDEAL = 'novalnetIdeal';
    public const NOVALNET_APPLEPAY = 'novalnetApplepay';
    public const NOVALNET_GOOGLEPAY = 'novalnetGooglepay';
    public const NOVALNET_EPS = 'novalnetEps';
    public const NOVALNET_GIROPAY = 'novalnetGiropay';
    public const NOVALNET_PRZELEWY = 'novalnetPrzelewy';
    public const NOVALNET_POSTFINANCE = 'novalnetPostFinance';
    public const NOVALNET_POSTFINANCE_CARD = 'novalnetPostFinanceCard';
    public const NOVALNET_INVOICE_INSTALMENT = 'novalnetInvoiceInstalment';
    public const NOVALNET_SEPA_INSTALMENT = 'novalnetSepaInstalment';
    public const NOVALNET_MULTIBANCO = 'novalnetMultibanco';
    public const NOVALNET_BANCONTACT = 'novalnetBancontact';
    public const NOVALNET_ALIPAY = 'novalnetAlipay';
    public const NOVALNET_TRUSTLY = 'novalnetTrustly';
    public const NOVALNET_WECHATPAY = 'novalnetWechatpay';
    public const NOVALNET_BLIK = 'novalnetBlik';

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepo;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\ServerAddress
     */
    protected $serverAddress;

    /**
     * @var \Novalnet\Payment\Model\TransactionStatus
     */
    protected $transactionStatusModel;

    /**
     * @var \Novalnet\Payment\Helper\Request
     */
    protected $novalnetRequestHelper;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    protected $serializer;

    /**
     * @var NNConfig
     */
    protected $novalnetConfig;

    /**
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\HTTP\PhpEnvironment\ServerAddress $serverAddress
     * @param \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     * @param NNConfig $novalnetConfig
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\HTTP\PhpEnvironment\ServerAddress $serverAddress,
        \Novalnet\Payment\Model\TransactionStatus $transactionStatusModel,
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer,
        NNConfig $novalnetConfig
    ) {
        $this->filesystem = $filesystem;
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->assetRepo = $assetRepo;
        $this->storeManager = $storeManager;
        $this->serverAddress = $serverAddress;
        $this->transactionStatusModel = $transactionStatusModel;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->novalnetConfig = $novalnetConfig;
        $this->jsonHelper = $jsonHelper;
        $this->serializer = $serializer;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $novalnetCcStyles = $this->getCcStyleConfig();

        return [
            'payment' => [
                self::NOVALNET_CC => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_CC),
                    'icon' => $this->getCcAvailableTypes(self::NOVALNET_CC),
                    'cardLogoUrl' => $this->getCardLogoUrl(),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_CC),
                    'storePayment' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_CC, 'shop_type'),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_CC, 'instructions'),
                    'storedPayments' => $this->getOneClickToken(self::NOVALNET_CC),
                    'tokenId' => $this->getOneClickToken(self::NOVALNET_CC, true),
                    'iframeParams' => $this->getCcIframeParams(),
                    'inlineForm' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_CC, 'inline_form'),
                    'enforce_3d' => ($this->novalnetConfig->getPaymentConfig(self::NOVALNET_CC, 'enforce_3d')) ? $this->novalnetConfig->getPaymentConfig(self::NOVALNET_CC, 'enforce_3d') : 0,
                    'labelStyle' => isset($novalnetCcStyles['standard_style_label']) ? $novalnetCcStyles['standard_style_label'] : '',
                    'inputStyle' => isset($novalnetCcStyles['standard_style_input']) ? $novalnetCcStyles['standard_style_input'] : '',
                    'styleText' => isset($novalnetCcStyles['standard_style_css']) ? $novalnetCcStyles['standard_style_css'] : '',
                    'currencyCode' => $this->storeManager->getStore()->getBaseCurrencyCode(),
                    'isZeroAmountBooking' => ($this->novalnetConfig->getPaymentConfig(self::NOVALNET_CC, 'payment_action') == NNConfig::ACTION_ZERO_AMOUNT_BOOKING) ? true : false
                ],
                self::NOVALNET_APPLEPAY => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_APPLEPAY),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_APPLEPAY),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_APPLEPAY, 'instructions'),
                    'sellerName' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_APPLEPAY, 'seller_name'),
                    'btnType' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_APPLEPAY, 'button_style'),
                    'btnTheme' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_APPLEPAY, 'button_theme'),
                    'btnHeight' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_APPLEPAY, 'button_height'),
                    'btnRadius' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_APPLEPAY, 'button_corner_radius'),
                    'langCode' => $this->novalnetRequestHelper->getLanguageCodeForPaymentSheet(),
                    'countryCode' => $this->novalnetRequestHelper->getCountryCode(),
                    'currencyCode' => $this->storeManager->getStore()->getBaseCurrencyCode(),
                    'clientKey' => $this->novalnetConfig->getGlobalConfig('client_key'),
                    'is_pending' => $this->novalnetRequestHelper->isAmountPendingForExpressCheckout(),
                    'guest_page' => $this->novalnetRequestHelper->isPageEnabledForExpressCheckout('guest_checkout_page')
                ],
                self::NOVALNET_GOOGLEPAY => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_GOOGLEPAY),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_GOOGLEPAY),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_GOOGLEPAY, 'instructions'),
                    'is_pending' => $this->novalnetRequestHelper->isAmountPendingForExpressCheckout(),
                    'guest_page' => $this->novalnetRequestHelper->isPageEnabledForExpressCheckout('guest_checkout_page'),
                    'sellerName' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_GOOGLEPAY, 'seller_name'),
                    'btnType' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_GOOGLEPAY, 'button_type'),
                    'btnHeight' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_GOOGLEPAY, 'button_height'),
                    'btnRadius' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_GOOGLEPAY, 'button_corner_radius'),
                    'langCode' => $this->novalnetRequestHelper->getLanguageCodeForPaymentSheet(),
                    'countryCode' => $this->novalnetRequestHelper->getCountryCode(),
                    'currencyCode' => $this->storeManager->getStore()->getBaseCurrencyCode(),
                    'clientKey' => $this->novalnetConfig->getGlobalConfig('client_key'),
                    'partnerId' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_GOOGLEPAY, 'merchant_id'),
                    'enforce3d' => ($this->novalnetConfig->getPaymentConfig(self::NOVALNET_GOOGLEPAY, 'enforce_3d')) ? $this->novalnetConfig->getPaymentConfig(self::NOVALNET_GOOGLEPAY, 'enforce_3d') : 0
                ],
                self::NOVALNET_SEPA => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_SEPA),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_SEPA),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_SEPA, 'instructions'),
                    'storePayment' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_SEPA, 'shop_type'),
                    'storedPayments' => $this->getOneClickToken(self::NOVALNET_SEPA),
                    'tokenId' => $this->getOneClickToken(self::NOVALNET_SEPA, true),
                    'isZeroAmountBooking' => ($this->novalnetConfig->getPaymentConfig(self::NOVALNET_SEPA, 'payment_action') == NNConfig::ACTION_ZERO_AMOUNT_BOOKING) ? true : false
                ],
                self::NOVALNET_INVOICE => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_INVOICE),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_INVOICE),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_INVOICE, 'instructions')
                ],
                self::NOVALNET_PREPAYMENT => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_PREPAYMENT),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_PREPAYMENT),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_PREPAYMENT, 'instructions'),
                ],
                self::NOVALNET_INVOICE_GUARANTEE => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_INVOICE_GUARANTEE),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_INVOICE_GUARANTEE),
                    'allow_b2b_customer' => $this->novalnetConfig->getPaymentConfig(
                        self::NOVALNET_INVOICE_GUARANTEE,
                        'allow_b2b_customer'
                    ),
                    'payment_guarantee_force' => $this->novalnetConfig->getPaymentConfig(
                        self::NOVALNET_INVOICE_GUARANTEE,
                        'payment_guarantee_force'
                    ),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(
                        self::NOVALNET_INVOICE_GUARANTEE,
                        'instructions'
                    ),
                ],
                self::NOVALNET_SEPA_GUARANTEE => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_SEPA_GUARANTEE),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_SEPA_GUARANTEE),
                    'allow_b2b_customer' => $this->novalnetConfig->getPaymentConfig(
                        self::NOVALNET_SEPA_GUARANTEE,
                        'allow_b2b_customer'
                    ),
                    'payment_guarantee_force' => $this->novalnetConfig->getPaymentConfig(
                        self::NOVALNET_SEPA_GUARANTEE,
                        'payment_guarantee_force'
                    ),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(
                        self::NOVALNET_SEPA_GUARANTEE,
                        'instructions'
                    ),
                    'storePayment' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_SEPA_GUARANTEE, 'shop_type'),
                    'storedPayments' => $this->getOneClickToken(self::NOVALNET_SEPA_GUARANTEE),
                    'tokenId' => $this->getOneClickToken(self::NOVALNET_SEPA_GUARANTEE, true)
                ],
                self::NOVALNET_CASHPAYMENT => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_CASHPAYMENT),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_CASHPAYMENT),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_CASHPAYMENT, 'instructions'),
                ],
                self::NOVALNET_MULTIBANCO => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_MULTIBANCO),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_MULTIBANCO),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_MULTIBANCO, 'instructions'),
                ],
                self::NOVALNET_PAYPAL => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_PAYPAL),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_PAYPAL),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_PAYPAL, 'instructions'),
                ],
                self::NOVALNET_BANKTRANSFER => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_BANKTRANSFER),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_BANKTRANSFER),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_BANKTRANSFER, 'instructions'),
                ],
                self::NOVALNET_ONLINEBANKTRANSFER => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_ONLINEBANKTRANSFER),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_ONLINEBANKTRANSFER),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_ONLINEBANKTRANSFER, 'instructions'),
                ],
                self::NOVALNET_ALIPAY => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_ALIPAY),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_ALIPAY),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_ALIPAY, 'instructions'),
                ],
                self::NOVALNET_TRUSTLY => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_TRUSTLY),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_TRUSTLY),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_TRUSTLY, 'instructions'),
                ],
                self::NOVALNET_WECHATPAY => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_WECHATPAY),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_WECHATPAY),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_WECHATPAY, 'instructions'),
                ],
                self::NOVALNET_IDEAL => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_IDEAL),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_IDEAL),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_IDEAL, 'instructions'),
                ],
                self::NOVALNET_BLIK => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_BLIK),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_BLIK),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_BLIK, 'instructions')
                ],
                self::NOVALNET_BANCONTACT => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_BANCONTACT),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_BANCONTACT),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_BANCONTACT, 'instructions'),
                ],
                self::NOVALNET_EPS => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_EPS),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_EPS),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_EPS, 'instructions'),
                ],
                self::NOVALNET_GIROPAY => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_GIROPAY),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_GIROPAY),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_GIROPAY, 'instructions'),
                ],
                self::NOVALNET_PRZELEWY => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_PRZELEWY),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_PRZELEWY),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_PRZELEWY, 'instructions'),
                ],
                self::NOVALNET_POSTFINANCE => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_POSTFINANCE),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_POSTFINANCE),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_POSTFINANCE, 'instructions'),
                ],
                self::NOVALNET_POSTFINANCE_CARD => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_POSTFINANCE_CARD),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_POSTFINANCE_CARD),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_POSTFINANCE_CARD, 'instructions'),
                ],
                self::NOVALNET_INVOICE_INSTALMENT => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_INVOICE_INSTALMENT),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_INVOICE_INSTALMENT),
                    'allow_b2b_customer' => $this->novalnetConfig->getPaymentConfig(
                        self::NOVALNET_INVOICE_INSTALMENT,
                        'allow_b2b_customer'
                    ),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(
                        self::NOVALNET_INVOICE_INSTALMENT,
                        'instructions'
                    ),
                    'instalmentCycles' => $this->novalnetConfig->getPaymentConfig(
                        self::NOVALNET_INVOICE_INSTALMENT,
                        'instalment_cycles'
                    )
                ],
                self::NOVALNET_SEPA_INSTALMENT => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_SEPA_INSTALMENT),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_SEPA_INSTALMENT),
                    'allow_b2b_customer' => $this->novalnetConfig->getPaymentConfig(
                        self::NOVALNET_SEPA_INSTALMENT,
                        'allow_b2b_customer'
                    ),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(
                        self::NOVALNET_SEPA_INSTALMENT,
                        'instructions'
                    ),
                    'instalmentCycles' => $this->novalnetConfig->getPaymentConfig(
                        self::NOVALNET_SEPA_INSTALMENT,
                        'instalment_cycles'
                    ),
                    'storePayment' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_SEPA_INSTALMENT, 'shop_type'),
                    'storedPayments' => $this->getOneClickToken(self::NOVALNET_SEPA_INSTALMENT),
                    'tokenId' => $this->getOneClickToken(self::NOVALNET_SEPA_INSTALMENT, true)
                ],
            ]
        ];
    }

    /**
     * Retrieve Credit Card iframe params
     *
     * @return array
     */
    public function getCcIframeParams()
    {
        return [
            'client_key' => $this->novalnetConfig->getGlobalConfig('client_key'),
            'lang' => $this->novalnetRequestHelper->getDefaultLanguage(),
            'cardHolderName' => $this->novalnetRequestHelper->getAccountHolder()
        ];
    }

    /**
     * Retrieve Credit Card style configuration
     *
     * @return array
     */
    public function getCcStyleConfig()
    {
        $ccStyleConfig = $this->novalnetConfig->getPaymentConfig(self::NOVALNET_CC, 'cc_style');
        if (!empty($ccStyleConfig)) {
            return $this->novalnetRequestHelper->isSerialized($ccStyleConfig)
                    ? $this->serializer->unserialize($ccStyleConfig)
                    : json_decode($ccStyleConfig, true);
        }

        return [];
    }

    /**
     * Retrieve availables Credit Card types
     *
     * @param string $code
     * @return array|bool
     */
    public function getCcAvailableTypes($code)
    {
        $availableTypes = $this->novalnetConfig->getPaymentConfig(self::NOVALNET_CC, 'cc_types');
        $icons = [];
        $ccIcons = [];

        if ($availableTypes && $this->novalnetConfig->getGlobalConfig('enable_payment_logo')) {
            foreach ($this->getCreditcardAssetUrl($code, $availableTypes) as $icon) {
                $icons = [
                    'src'   => $icon,
                    'title' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_CC, 'title')
                ];
                $ccIcons[] = $icons;
            }

            return $ccIcons;
        }

        return false;
    }

    /**
     * Loads the Credit card images source asset path
     *
     * @param string $code
     * @param string $availableTypes
     * @return string|array
     */
    public function getCreditcardAssetUrl($code, $availableTypes)
    {
        $cardTypes = ['VI' => 'novalnetvisa', 'MC' => 'novalnetmastercard',
            'AE' => 'novalnetamex', 'MA' => 'novalnetmaestro', 'CI' => 'novalnetcartasi', 'UP' => 'novalnetunionpay',
            'DC' => 'novalnetdiscover', 'DI' => 'novalnetdiners', 'JCB' => 'novalnetjcb', 'CB' => 'novalnetcartebleue'];
        $asset = [];
        $assetUrl = [];
        $params = ['_secure' => $this->request->isSecure()];
        if ($availableTypes) {
            $availableTypes = (!empty($availableTypes)) ? explode(',', $availableTypes) : [];
            foreach ($cardTypes as $code => $value) {
                if (in_array($code, $availableTypes)) {
                    $asset[$code] = $this->assetRepo->createAsset(
                        'Novalnet_Payment::images/'. ($value) .'.png',
                        $params
                    );
                    $assetUrl[] = $asset[$code]->getUrl();
                }
            }
        }

        return $assetUrl;
    }

    /**
     * Retrieve one click tokens
     *
     * @param  string $paymentMethodCode
     * @param  string|bool $token
     * @return array
     */
    public function getOneClickToken($paymentMethodCode, $token = false)
    {
        $data = [];
        $tokenId = 'new_account';
        $customerSession = $this->novalnetRequestHelper->getCustomerSession();
        if ($customerSession->isLoggedIn() && $this->novalnetConfig->getPaymentConfig($paymentMethodCode, 'shop_type')) {
            if (strpos($paymentMethodCode, self::NOVALNET_SEPA) !== false) {
                $paymentMethodCode = self::NOVALNET_SEPA;
            }
            $transactionStatusCollection = $this->transactionStatusModel->getCollection()
                ->setOrder('order_id', 'DESC')
                ->setPageSize(3)
                ->addFieldToFilter('token_info', ['neq' => 'NULL'])
                ->addFieldToFilter('customer_id', $customerSession->getCustomer()->getId())
                ->addFieldToFilter('payment_method', ['like' => $paymentMethodCode . '%']);

            foreach ($transactionStatusCollection as $key => $transactionStatus) {
                if ($transactionStatus->getTokenInfo() && !empty(json_decode($transactionStatus->getTokenInfo(), true))) {
                    $text = "";
                    if ($paymentMethodCode == self::NOVALNET_CC) {
                        $tokenInfo = json_decode($transactionStatus->getTokenInfo(), true);
                        if (empty($tokenInfo['NnCardNumber']) || empty($tokenInfo['NnCardExpiryMonth']) || empty($tokenInfo['NnCardExpiryYear'])) {
                            continue;
                        }

                        $text = __(
                            "ending in %1 (expires %2/%3)",
                            substr($tokenInfo['NnCardNumber'], -4),
                            sprintf("%02d", $tokenInfo['NnCardExpiryMonth']),
                            substr($tokenInfo['NnCardExpiryYear'], 2)
                        );
                    }

                    if ($tokenId == 'new_account') {
                        $tokenId = $transactionStatus->getToken();
                    }

                    $data[] = [
                        'id' => $transactionStatus->getId(),
                        'NnToken' => $transactionStatus->getToken(),
                        'token_info' => json_decode($transactionStatus->getTokenInfo(), true),
                        'text' => (!empty($text) ? $text : '')
                    ];

                }
            }
        }
        if ($token) {
            return $tokenId;
        }
        return $data;
    }

    /**
     * Get Payment Logo
     *
     * @param  string $paymentMethodCode
     * @return mixed
     */
    public function getPaymentLogo($paymentMethodCode)
    {
        $logoImageSrc = false;
        if ($this->novalnetConfig->getGlobalConfig('enable_payment_logo') && !empty($paymentMethodCode)) {
            $params = ['_secure' => $this->request->isSecure()];
            $asset = $this->assetRepo->createAsset(
                'Novalnet_Payment::images/'. strtolower($paymentMethodCode) .'.png',
                $params
            );
            $logoImageSrc = $asset->getUrl();
        }

        return $logoImageSrc;
    }

    /**
     * Get Payment Logo
     *
     * @return mixed
     */
    public function getCardLogoUrl()
    {
        $logoImageSrc = false;
        $params = ['_secure' => $this->request->isSecure()];
        $asset = $this->assetRepo->createAsset(
            'Novalnet_Payment::images/',
            $params
        );
        return $asset->getUrl();
    }
}

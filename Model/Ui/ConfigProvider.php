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
    const NOVALNET_CC = 'novalnetCc';
    const NOVALNET_SEPA = 'novalnetSepa';
    const NOVALNET_INVOICE = 'novalnetInvoice';
    const NOVALNET_PREPAYMENT = 'novalnetPrepayment';
    const NOVALNET_INVOICE_GUARANTEE = 'novalnetInvoiceGuarantee';
    const NOVALNET_SEPA_GUARANTEE = 'novalnetSepaGuarantee';
    const NOVALNET_CASHPAYMENT = 'novalnetCashpayment';
    const NOVALNET_PAYPAL = 'novalnetPaypal';
    const NOVALNET_BANKTRANSFER = 'novalnetBanktransfer';
    const NOVALNET_IDEAL = 'novalnetIdeal';
    const NOVALNET_APPLEPAY = 'novalnetApplepay';
    const NOVALNET_EPS = 'novalnetEps';
    const NOVALNET_GIROPAY = 'novalnetGiropay';
    const NOVALNET_PRZELEWY = 'novalnetPrzelewy';
    const NOVALNET_POSTFINANCE = 'novalnetPostFinance';
    const NOVALNET_POSTFINANCE_CARD = 'novalnetPostFinanceCard';
    const NOVALNET_INVOICE_INSTALMENT = 'novalnetInvoiceInstalment';
    const NOVALNET_SEPA_INSTALMENT = 'novalnetSepaInstalment';
    const NOVALNET_MULTIBANCO = 'novalnetMultibanco';
    const NOVALNET_BANCONTACT = 'novalnetBancontact';

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
     * @param \Magento\Checkout\Model\Session $checkoutSession,
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
                ],
                self::NOVALNET_APPLEPAY => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_APPLEPAY),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_APPLEPAY),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_APPLEPAY, 'instructions'),
                    'sellerName' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_APPLEPAY, 'seller_name'),
                    'btnStyle' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_APPLEPAY, 'button_style'),
                    'btnTheme' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_APPLEPAY, 'button_theme'),
                    'btnHeight' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_APPLEPAY, 'button_height'),
                    'btnRadius' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_APPLEPAY, 'button_corner_radius'),
                    'langCode' => $this->novalnetRequestHelper->getDefaultLanguage(),
                    'countryCode' => $this->novalnetRequestHelper->getCountryCode(),
                    'currencyCode' => $this->storeManager->getStore()->getBaseCurrencyCode(),
                    'clientKey' => $this->novalnetConfig->getGlobalConfig('client_key'),
                    'is_pending' => $this->novalnetRequestHelper->isApplePayAmountPending()
                ],
                self::NOVALNET_SEPA => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_SEPA),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_SEPA),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_SEPA, 'instructions'),
                    'storePayment' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_SEPA, 'shop_type'),
                    'storedPayments' => $this->getOneClickToken(self::NOVALNET_SEPA),
                    'tokenId' => $this->getOneClickToken(self::NOVALNET_SEPA, true)
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
                    'storePayment' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_PAYPAL, 'shop_type'),
                    'storedPayments' => $this->getOneClickToken(self::NOVALNET_PAYPAL),
                    'tokenId' => $this->getOneClickToken(self::NOVALNET_PAYPAL, true)
                ],
                self::NOVALNET_BANKTRANSFER => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_BANKTRANSFER),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_BANKTRANSFER),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_BANKTRANSFER, 'instructions'),
                ],
                self::NOVALNET_IDEAL => [
                    'logo' => $this->getPaymentLogo(self::NOVALNET_IDEAL),
                    'testmode' => $this->novalnetConfig->getTestMode(self::NOVALNET_IDEAL),
                    'instructions' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_IDEAL, 'instructions'),
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
     * @return string
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
        return $this->novalnetRequestHelper->isSerialized($this->novalnetConfig->getPaymentConfig(self::NOVALNET_CC, 'cc_style'))
                ? $this->serializer->unserialize($this->novalnetConfig->getPaymentConfig(self::NOVALNET_CC, 'cc_style'))
                : json_decode($this->novalnetConfig->getPaymentConfig(self::NOVALNET_CC, 'cc_style'), true);
    }

    /**
     * Retrieve availables Credit Card types
     *
     * @param  string $code
     * @return array
     */
    public function getCcAvailableTypes($code)
    {
        $availableTypes = $this->novalnetConfig->getPaymentConfig(self::NOVALNET_CC, 'cc_types');
        if ($availableTypes) {
            $icons = [];
            $ccIcons = false;
            if ($this->novalnetConfig->getGlobalConfig('enable_payment_logo')) {
                foreach ($this->getCreditcardAssetUrl($code, $availableTypes) as $icon) {
                    $icons = [
                        'src'   => $icon,
                        'title' => $this->novalnetConfig->getPaymentConfig(self::NOVALNET_CC, 'title')
                    ];
                    $ccIcons[] = $icons;
                }
            }

            return $ccIcons;
        }
    }

    /**
     * Loads the Credit card images source asset path
     *
     * @param  string $code
     * @param  array  $availableTypes
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
            $availableTypes = explode(',', $availableTypes);
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
     * @param  string|null $token
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
                        if (strlen(1 === $tokenInfo['NnCardExpiryMonth'])) {
                            $tokenInfo['NnCardExpiryMonth'] = '0' . $tokenInfo['NnCardExpiryMonth'];
                        }
                        $text = __(
                            "ending in %1 (expires %2/%3)",
                            substr($tokenInfo['NnCardNumber'], -4),
                            $tokenInfo['NnCardExpiryMonth'],
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
     * get Payment Logo
     *
     * @param  string $paymentMethodCode
     * @return mixed
     */
    public function getPaymentLogo($paymentMethodCode)
    {
        $logoImageSrc = false;
        if ($this->novalnetConfig->getGlobalConfig('enable_payment_logo')) {
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
     * get Payment Logo
     *
     * @param  none
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

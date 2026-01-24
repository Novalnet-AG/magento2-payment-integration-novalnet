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

use Novalnet\Payment\Model\Ui\ConfigProvider;

class NNConfig
{
    public const NOVALNET_PAYMENT_URL = 'https://payport.novalnet.de/v2/payment';
    public const NOVALNET_AUTHORIZE_URL = 'https://payport.novalnet.de/v2/authorize';
    public const NOVALNET_CAPTURE_URL = 'https://payport.novalnet.de/v2/transaction/capture';
    public const NOVALNET_REFUND_URL = 'https://payport.novalnet.de/v2/transaction/refund';
    public const NOVALNET_CANCEL_URL = 'https://payport.novalnet.de/v2/transaction/cancel';
    public const NOVALNET_INSTALMENT_CANCEL = 'https://payport.novalnet.de/v2/instalment/cancel';
    public const NOVALNET_MERCHANT_DETAIL_URL = 'https://payport.novalnet.de/v2/merchant/details';
    public const NOVALNET_TRANSACTION_DETAIL_URL = 'https://payport.novalnet.de/v2/transaction/details';
    public const NOVALNET_WEBHOOK_CONFIG_URL = 'https://payport.novalnet.de/v2/webhook/configure';
    public const NOVALNET_TRANSACTION_UPDATE_URL = 'https://payport.novalnet.de/v2/transaction/update';
    public const NOVALNET_SEAMLESS_PAYMENT_URL = 'https://payport.novalnet.de/v2/seamless/payment';
    public const ACTION_ZERO_AMOUNT = 'zero_amount';
    public const ACTION_AUTHORIZE = 'authorized';
    public const ACTION_CAPTURE = 'capture';
    public const NOVALNET_FORM_VERSION = 13;
    public const MAGENTO_BACKEND_THEME = 'Magento_Backend';

    /**
     * @var array
     */
    protected $paymentTypes = [
        ConfigProvider::NOVALNET_SEPA => 'DIRECT_DEBIT_SEPA',
        ConfigProvider::NOVALNET_ACH => 'DIRECT_DEBIT_ACH',
        ConfigProvider::NOVALNET_CC => 'CREDITCARD',
        ConfigProvider::NOVALNET_APPLEPAY => 'APPLEPAY',
        ConfigProvider::NOVALNET_GOOGLEPAY => 'GOOGLEPAY',
        ConfigProvider::NOVALNET_INVOICE => 'INVOICE',
        ConfigProvider::NOVALNET_PREPAYMENT => 'PREPAYMENT',
        ConfigProvider::NOVALNET_INVOICE_GUARANTEE => 'GUARANTEED_INVOICE',
        ConfigProvider::NOVALNET_SEPA_GUARANTEE => 'GUARANTEED_DIRECT_DEBIT_SEPA',
        ConfigProvider::NOVALNET_INVOICE_INSTALMENT => 'INSTALMENT_INVOICE',
        ConfigProvider::NOVALNET_SEPA_INSTALMENT => 'INSTALMENT_DIRECT_DEBIT_SEPA',
        ConfigProvider::NOVALNET_IDEAL => 'IDEAL',
        ConfigProvider::NOVALNET_ONLINEBANKTRANSFER => 'ONLINE_BANK_TRANSFER',
        ConfigProvider::NOVALNET_PRZELEWY => 'PRZELEWY24',
        ConfigProvider::NOVALNET_EPS => 'EPS',
        ConfigProvider::NOVALNET_PAYPAL => 'PAYPAL',
        ConfigProvider::NOVALNET_POSTFINANCE_CARD => 'POSTFINANCE_CARD',
        ConfigProvider::NOVALNET_POSTFINANCE => 'POSTFINANCE',
        ConfigProvider::NOVALNET_BANCONTACT => 'BANCONTACT',
        ConfigProvider::NOVALNET_MULTIBANCO => 'MULTIBANCO',
        ConfigProvider::NOVALNET_ALIPAY => 'ALIPAY',
        ConfigProvider::NOVALNET_WECHATPAY => 'WECHATPAY',
        ConfigProvider::NOVALNET_TRUSTLY => 'TRUSTLY',
        ConfigProvider::NOVALNET_PAYCONIQ => 'PAYCONIQ',
        ConfigProvider::NOVALNET_BLIK => 'BLIK',
        ConfigProvider::NOVALNET_TWINT => 'TWINT'
    ];

    /**
     * @var array
     */
    protected $paymentTitles = [
        ConfigProvider::NOVALNET_SEPA => 'Direct Debit SEPA',
        ConfigProvider::NOVALNET_ACH => 'Direct Debit ACH',
        ConfigProvider::NOVALNET_CC => 'Credit/Debit Cards',
        ConfigProvider::NOVALNET_APPLEPAY => 'Apple Pay',
        ConfigProvider::NOVALNET_GOOGLEPAY => 'Google Pay',
        ConfigProvider::NOVALNET_INVOICE => 'Invoice',
        ConfigProvider::NOVALNET_PREPAYMENT => 'Prepayment',
        ConfigProvider::NOVALNET_INVOICE_GUARANTEE => 'Invoice with payment guarantee',
        ConfigProvider::NOVALNET_SEPA_GUARANTEE => 'Direct Debit SEPA with payment guarantee',
        ConfigProvider::NOVALNET_INVOICE_INSTALMENT => 'Instalment by Invoice',
        ConfigProvider::NOVALNET_SEPA_INSTALMENT => 'Instalment by Direct Debit SEPA',
        ConfigProvider::NOVALNET_IDEAL => 'iDEAL',
        ConfigProvider::NOVALNET_ONLINEBANKTRANSFER => 'Online bank transfer',
        ConfigProvider::NOVALNET_PRZELEWY => 'Przelewy24',
        ConfigProvider::NOVALNET_EPS => 'eps',
        ConfigProvider::NOVALNET_PAYPAL => 'Paypal',
        ConfigProvider::NOVALNET_POSTFINANCE_CARD => 'PostFinance Card',
        ConfigProvider::NOVALNET_POSTFINANCE => 'PostFinance E-Finance',
        ConfigProvider::NOVALNET_BANCONTACT => 'Bancontact',
        ConfigProvider::NOVALNET_MULTIBANCO => 'Multibanco',
        ConfigProvider::NOVALNET_ALIPAY => 'Alipay',
        ConfigProvider::NOVALNET_WECHATPAY => 'WeChat Pay',
        ConfigProvider::NOVALNET_TRUSTLY => 'Trustly',
        ConfigProvider::NOVALNET_PAYCONIQ => 'Payconiq',
        ConfigProvider::NOVALNET_BLIK => 'Blik',
        ConfigProvider::NOVALNET_TWINT => 'TWINT'
    ];

    /**
     * @var array
     */
     protected $redirectPayments = [
        ConfigProvider::NOVALNET_PAYPAL,
        ConfigProvider::NOVALNET_ONLINEBANKTRANSFER,
        ConfigProvider::NOVALNET_IDEAL,
        ConfigProvider::NOVALNET_BANCONTACT,
        ConfigProvider::NOVALNET_EPS,
        ConfigProvider::NOVALNET_PRZELEWY,
        ConfigProvider::NOVALNET_POSTFINANCE_CARD,
        ConfigProvider::NOVALNET_POSTFINANCE,
        ConfigProvider::NOVALNET_ALIPAY,
        ConfigProvider::NOVALNET_WECHATPAY,
        ConfigProvider::NOVALNET_TRUSTLY,
        ConfigProvider::NOVALNET_PAYCONIQ,
        ConfigProvider::NOVALNET_BLIK,
        ConfigProvider::NOVALNET_TWINT
     ];

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
     protected $scopeConfig;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
     public function __construct(
         \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     ) {
         $this->scopeConfig = $scopeConfig;
     }

    /**
     * Get Novalnet Global Configuration values
     *
     * @param string $field
     * @param int|null $storeId
     * @return string
     */
     public function getGlobalConfig($field, $storeId = null)
     {
         return $this->scopeConfig->getValue(
             'payment/novalnet/' . $field,
             \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
             $storeId
         );
     }

     /**
     * Retrieve a config value from Hyvä Themes
     *
     * @param string $field
     * @return string|null
     */
     public function getHyvaCheckoutConfig($field ,$storeId = null)
     {
        return $this->scopeConfig->getValue(
            'hyva_themes_checkout/' .$field,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
     }


    /**
     * Get global on-hold status
     *
     * @param int|null $storeId
     * @return string
     */
     public function getOnholdStatus($storeId = null)
     {
         return $this->scopeConfig->getValue(
             'novalnet_global/novalnet/onhold_status',
             \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
             $storeId
         );
     }

    /**
     * Get global order completion status
     *
     * @param int|null $storeId
     * @return string
     */
     public function getOrderCompletionStatus($storeId = null)
     {
         return $this->scopeConfig->getValue(
             'novalnet_global/novalnet/order_status',
             \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
             $storeId
         );
     }

    /**
     * Get Novalnet Global Merchant Script Configuration values
     *
     * @param string $field
     * @param int $storeId
     * @return string
     */
     public function getMerchantScriptConfig($field, $storeId = null)
     {
         return $this->scopeConfig->getValue(
             'payment/merchant_script/' . $field,
             \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
             $storeId
         );
     }

    /**
     * To get current theme id
     *
     * @param int $storeId
     * @return string
     */
     public function getThemeId($storeId = null)
     {
         return $this->scopeConfig->getValue(
             \Magento\Framework\View\DesignInterface::XML_PATH_THEME_ID,
             \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
             $storeId
         );
     }

    /**
     * Check is redirect Payment method by code
     *
     * @param string $code
     * @return bool
     */
     public function isRedirectPayment($code)
     {
         return (bool) (in_array($code, $this->redirectPayments));
     }

    /**
     * Get Payment Type by method code
     *
     * @param string $code
     * @return string
     */
     public function getPaymentTypeByCode($code)
     {
         return $this->paymentTypes[$code];
     }

     /**
      * To get recurring payment types with trail
      *
      * @return array
      */
      public function getRecurringPaymentTypesBasedonCondition()
      {
          return [
              $this->paymentTypes[ConfigProvider::NOVALNET_SEPA],
              $this->paymentTypes[ConfigProvider::NOVALNET_ACH],
              $this->paymentTypes[ConfigProvider::NOVALNET_CC],
              $this->paymentTypes[ConfigProvider::NOVALNET_INVOICE],
              $this->paymentTypes[ConfigProvider::NOVALNET_PREPAYMENT],
              $this->paymentTypes[ConfigProvider::NOVALNET_INVOICE_GUARANTEE],
              $this->paymentTypes[ConfigProvider::NOVALNET_SEPA_GUARANTEE],
              $this->paymentTypes[ConfigProvider::NOVALNET_APPLEPAY],
              $this->paymentTypes[ConfigProvider::NOVALNET_GOOGLEPAY],
              $this->paymentTypes[ConfigProvider::NOVALNET_PAYPAL]
          ];
      }
 
      /**
       * To get recurring payment types without trail
       *
       * @return array
       */
       public function getRecurringPaymentTypes()
       {
           return [
               $this->paymentTypes[ConfigProvider::NOVALNET_SEPA],
               $this->paymentTypes[ConfigProvider::NOVALNET_ACH],
               $this->paymentTypes[ConfigProvider::NOVALNET_CC],
               $this->paymentTypes[ConfigProvider::NOVALNET_INVOICE],
               $this->paymentTypes[ConfigProvider::NOVALNET_PREPAYMENT],
               $this->paymentTypes[ConfigProvider::NOVALNET_INVOICE_GUARANTEE],
               $this->paymentTypes[ConfigProvider::NOVALNET_SEPA_GUARANTEE],
               $this->paymentTypes[ConfigProvider::NOVALNET_APPLEPAY],
               $this->paymentTypes[ConfigProvider::NOVALNET_GOOGLEPAY]
           ];
       }

    /**
     * Check is Subscription Supported
     *
     * @param string $type
     * @return bool
     */
     public function isSubscriptionSupported($type)
     {
         return (bool) (in_array($type, $this->getRecurringPaymentTypes()));
     }

    /**
     * Get Payment Titles by code
     *
     * @param string $code
     * @return string
     */
     public function getPaymentTitleByCode($code)
     {
         return $this->paymentTitles[$code];
     }
}

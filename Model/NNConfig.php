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
    public const ACTION_ZERO_AMOUNT_BOOKING = 'zeroAmountBooking';

    /**
     * @var array
     */
    protected $paymentTypes = [
        ConfigProvider::NOVALNET_SEPA => 'DIRECT_DEBIT_SEPA',
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
        ConfigProvider::NOVALNET_BANKTRANSFER => 'ONLINE_TRANSFER',
        ConfigProvider::NOVALNET_ONLINEBANKTRANSFER => 'ONLINE_BANK_TRANSFER',
        ConfigProvider::NOVALNET_GIROPAY => 'GIROPAY',
        ConfigProvider::NOVALNET_CASHPAYMENT => 'CASHPAYMENT',
        ConfigProvider::NOVALNET_PRZELEWY => 'PRZELEWY24',
        ConfigProvider::NOVALNET_EPS => 'EPS',
        ConfigProvider::NOVALNET_PAYPAL => 'PAYPAL',
        ConfigProvider::NOVALNET_POSTFINANCE_CARD => 'POSTFINANCE_CARD',
        ConfigProvider::NOVALNET_POSTFINANCE => 'POSTFINANCE',
        ConfigProvider::NOVALNET_BANCONTACT => 'BANCONTACT',
        ConfigProvider::NOVALNET_MULTIBANCO => 'MULTIBANCO',
        ConfigProvider::NOVALNET_ALIPAY => 'ALIPAY',
        ConfigProvider::NOVALNET_WECHATPAY => 'WECHATPAY',
        ConfigProvider::NOVALNET_TRUSTLY => 'TRUSTLY'
    ];

    /**
     * @var array
     */
    public $oneClickPayments = [
        ConfigProvider::NOVALNET_CC,
        ConfigProvider::NOVALNET_SEPA,
        ConfigProvider::NOVALNET_SEPA_GUARANTEE,
        ConfigProvider::NOVALNET_SEPA_INSTALMENT,
        ConfigProvider::NOVALNET_PAYPAL,
    ];

    /**
     * @var array
     */
     protected $zeroAmountPayments = [
        ConfigProvider::NOVALNET_CC,
        ConfigProvider::NOVALNET_SEPA
     ];

    /**
     * @var array
     */
     protected $redirectPayments = [
        ConfigProvider::NOVALNET_PAYPAL,
        ConfigProvider::NOVALNET_BANKTRANSFER,
        ConfigProvider::NOVALNET_ONLINEBANKTRANSFER,
        ConfigProvider::NOVALNET_IDEAL,
        ConfigProvider::NOVALNET_BANCONTACT,
        ConfigProvider::NOVALNET_EPS,
        ConfigProvider::NOVALNET_GIROPAY,
        ConfigProvider::NOVALNET_PRZELEWY,
        ConfigProvider::NOVALNET_POSTFINANCE_CARD,
        ConfigProvider::NOVALNET_POSTFINANCE,
        ConfigProvider::NOVALNET_ALIPAY,
        ConfigProvider::NOVALNET_WECHATPAY,
        ConfigProvider::NOVALNET_TRUSTLY,
     ];

    /**
     * @var array
     */
     public $subscriptionSupportedPayments = [
        ConfigProvider::NOVALNET_SEPA,
        ConfigProvider::NOVALNET_CC,
        ConfigProvider::NOVALNET_INVOICE,
        ConfigProvider::NOVALNET_PREPAYMENT,
        ConfigProvider::NOVALNET_INVOICE_GUARANTEE,
        ConfigProvider::NOVALNET_SEPA_GUARANTEE,
        ConfigProvider::NOVALNET_PAYPAL
     ];

    /**
     * @var array
     */
     protected $allowedCountry = [ 'AT', 'DE', 'CH' ];

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
     * Get Payment methods
     *
     * @return array
     */
     public function getPaymentMethodCodes()
     {
         return array_keys($this->paymentTypes);
     }

    /**
     * Get Payment Type by method code
     *
     * @param string $code
     * @return string
     */
     public function getPaymentType($code)
     {
         return $this->paymentTypes[$code];
     }

    /**
     * Get Payment method code by Type
     *
     * @param string $paymentType
     * @return mixed
     */
     public function getPaymentCodeByType($paymentType)
     {
         return array_search($paymentType, $this->paymentTypes);
     }

    /**
     * Check is one click Payment method by code
     *
     * @param string $code
     * @return bool
     */
     public function isOneClickPayment($code)
     {
         return (bool) (in_array($code, $this->oneClickPayments));
     }

    /**
     * Check is zero amount booking supported
     *
     * @param string $code
     * @return bool
     */
     public function isZeroAmountBookingSupported($code)
     {
         return (bool) (in_array($code, $this->zeroAmountPayments));
     }

    /**
     * Check is one Subscription Supported Payment method by code
     *
     * @param string $code
     * @return bool
     */
     public function isSubscriptionSupported($code)
     {
         return (bool) (in_array($code, $this->subscriptionSupportedPayments));
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
     * Check Allowed country
     *
     * @param string $county
     * @param bool $b2b
     * @return bool
     */
     public function isAllowedCountry($county, $b2b = false)
     {
         if ($b2b) {
             $allowedCountryB2B = $this->scopeConfig->getValue(
                 'general/country/eu_countries',
                 \Magento\Store\Model\ScopeInterface::SCOPE_STORE
             );
             $allowedCountryB2B = (!empty($allowedCountryB2B)) ? explode(",", $allowedCountryB2B) : [];
             array_push($allowedCountryB2B, 'CH');
         }

         return (bool) (($b2b && in_array($county, $allowedCountryB2B)) || in_array($county, $this->allowedCountry));
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
     * Get Novalnet Global on-hold status
     *
     * @param int|null $storeId
     * @return string
     */
     public function getGlobalOnholdStatus($storeId = null)
     {
         return $this->scopeConfig->getValue(
             'novalnet_global/novalnet/onhold_status',
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
     * Get Novalnet Payment Configuration values
     *
     * @param string $code
     * @param string $field
     * @param int $storeId
     * @return string
     */
     public function getPaymentConfig($code, $field, $storeId = null)
     {
         return $this->scopeConfig->getValue(
             'payment/' . $code . '/' . $field,
             \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
             $storeId
         );
     }

    /**
     * Get Novalnet Payment Test Mode
     *
     * @param string $paymentMethodCode
     * @param int $storeId
     * @return int
     */
     public function getTestMode($paymentMethodCode, $storeId = null)
     {
         if ($paymentMethodCode) {
             $livePaymentMethods = $this->scopeConfig->getValue(
                 'payment/novalnet/live_mode',
                 \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                 $storeId
             );

             $livePaymentMethods = (!empty($livePaymentMethods)) ? explode(',', $livePaymentMethods) : [];

             return (in_array($paymentMethodCode, $livePaymentMethods) === false) ? 1 : 0;
         }

         return 1;
     }
}

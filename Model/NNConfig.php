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
    const NOVALNET_PAYMENT_URL = 'https://payport.novalnet.de/v2/payment';
    const NOVALNET_AUTHORIZE_URL = 'https://payport.novalnet.de/v2/authorize';
    const NOVALNET_CAPTURE_URL = 'https://payport.novalnet.de/v2/transaction/capture';
    const NOVALNET_REFUND_URL = 'https://payport.novalnet.de/v2/transaction/refund';
    const NOVALNET_CANCEL_URL = 'https://payport.novalnet.de/v2/transaction/cancel';
    const NOVALNET_INSTALMENT_CANCEL = 'https://payport.novalnet.de/v2/instalment/cancel';
    const NOVALNET_MERCHANT_DETAIL_URL = 'https://payport.novalnet.de/v2/merchant/details';
    const NOVALNET_TRANSACTION_DETAIL_URL = 'https://payport.novalnet.de/v2/transaction/details';
    const NOVALNET_WEBHOOK_CONFIG_URL = 'https://payport.novalnet.de/v2/webhook/configure';

    /**
     * @array Novalnet payment types
     */
    protected $paymentTypes = [
        ConfigProvider::NOVALNET_SEPA => 'DIRECT_DEBIT_SEPA',
        ConfigProvider::NOVALNET_CC => 'CREDITCARD',
        ConfigProvider::NOVALNET_APPLEPAY => 'APPLEPAY',
        ConfigProvider::NOVALNET_INVOICE => 'INVOICE',
        ConfigProvider::NOVALNET_PREPAYMENT => 'PREPAYMENT',
        ConfigProvider::NOVALNET_INVOICE_GUARANTEE => 'GUARANTEED_INVOICE',
        ConfigProvider::NOVALNET_SEPA_GUARANTEE => 'GUARANTEED_DIRECT_DEBIT_SEPA',
        ConfigProvider::NOVALNET_INVOICE_INSTALMENT => 'INSTALMENT_INVOICE',
        ConfigProvider::NOVALNET_SEPA_INSTALMENT => 'INSTALMENT_DIRECT_DEBIT_SEPA',
        ConfigProvider::NOVALNET_IDEAL => 'IDEAL',
        ConfigProvider::NOVALNET_BANKTRANSFER => 'ONLINE_TRANSFER',
        ConfigProvider::NOVALNET_GIROPAY => 'GIROPAY',
        ConfigProvider::NOVALNET_CASHPAYMENT => 'CASHPAYMENT',
        ConfigProvider::NOVALNET_PRZELEWY => 'PRZELEWY24',
        ConfigProvider::NOVALNET_EPS => 'EPS',
        ConfigProvider::NOVALNET_PAYPAL => 'PAYPAL',
        ConfigProvider::NOVALNET_POSTFINANCE_CARD => 'POSTFINANCE_CARD',
        ConfigProvider::NOVALNET_POSTFINANCE => 'POSTFINANCE',
        ConfigProvider::NOVALNET_BANCONTACT => 'BANCONTACT',
        ConfigProvider::NOVALNET_MULTIBANCO => 'MULTIBANCO'
    ];

    /**
     * @array Novalnet payment types
     */
    public $oneClickPayments = [
        ConfigProvider::NOVALNET_CC,
        ConfigProvider::NOVALNET_SEPA,
        ConfigProvider::NOVALNET_SEPA_GUARANTEE,
        ConfigProvider::NOVALNET_SEPA_INSTALMENT,
        ConfigProvider::NOVALNET_PAYPAL,
    ];

    /**
     * @array Novalnet Redirect payments
     */
    protected $redirectPayments = [
        ConfigProvider::NOVALNET_PAYPAL,
        ConfigProvider::NOVALNET_BANKTRANSFER,
        ConfigProvider::NOVALNET_IDEAL,
        ConfigProvider::NOVALNET_BANCONTACT,
        ConfigProvider::NOVALNET_EPS,
        ConfigProvider::NOVALNET_GIROPAY,
        ConfigProvider::NOVALNET_PRZELEWY,
        ConfigProvider::NOVALNET_POSTFINANCE_CARD,
        ConfigProvider::NOVALNET_POSTFINANCE,
    ];

    /**
     * @array Allowed country
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
     * get Payment methods
     *
     * @return array
     */
    public function getPaymentMethodCodes()
    {
        return array_keys($this->paymentTypes);
    }

    /**
     * get Payment Type by method code
     *
     * @param  string $code
     * @return string
     */
    public function getPaymentType($code)
    {
        return $this->paymentTypes[$code];
    }

    /**
     * get Payment method code by Type
     *
     * @param  string $paymentType
     * @return string
     */
    public function getPaymentCodeByType($paymentType)
    {
        return array_search($paymentType, $this->paymentTypes);
    }

    /**
     * check is one click Payment method by code
     *
     * @param  string $code
     * @return array
     */
    public function isOneClickPayment($code)
    {
        return (bool) (in_array($code, $this->oneClickPayments));
    }

    /**
     * check is redirect Payment method by code
     *
     * @param  string $code
     * @return array
     */
    public function isRedirectPayment($code)
    {
        return (bool) (in_array($code, $this->redirectPayments));
    }

    /**
     * check Allowed country
     *
     * @param  string $county
     * @param  boolean $b2b
     * @return array
     */
    public function isAllowedCountry($county, $b2b = false)
    {
        if ($b2b) {
            $allowedCountryB2B = $this->scopeConfig->getValue(
                'general/country/eu_countries',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $allowedCountryB2B = explode(",", $allowedCountryB2B);
            array_push($allowedCountryB2B, 'CH');
        }

        return (bool) (($b2b && in_array($county, $allowedCountryB2B)) || in_array($county, $this->allowedCountry));
    }

    /**
     * get Novalnet Global Configuration values
     *
     * @param  string $field
     * @param  int|null $storeId
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
     * get Novalnet Global Merchant Script Configuration values
     *
     * @param  string $field
     * @param  int $storeId
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
     * get Novalnet Payment Configuration values
     *
     * @param  string $code
     * @param  string $field
     * @param  int $storeId
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
     * get Novalnet Payment Test Mode
     *
     * @param  string $paymentMethodCode
     * @param  int $storeId
     * @return bool
     */
    public function getTestMode($paymentMethodCode, $storeId = null)
    {
        if ($paymentMethodCode) {
            $livePaymentMethods = $this->scopeConfig->getValue(
                'payment/novalnet/live_mode',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );

            return (strpos($livePaymentMethods, $paymentMethodCode) === false) ? 1 : 0;
        }
    }
}

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

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    public const NOVALNET_PAY = 'novalnetPay';
    public const NOVALNET_ACH = 'novalnetAch';
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
    public const NOVALNET_PAYCONIQ = 'Payconiq';

    /**
     * @var \Novalnet\Payment\Model\NovalnetRepository
     */
    protected $novalnetRepository;

    /**
     * @param \Novalnet\Payment\Model\NovalnetRepository $novalnetRepository
     */
    public function __construct(
        \Novalnet\Payment\Model\NovalnetRepository $novalnetRepository
    ) {
        $this->novalnetRepository = $novalnetRepository;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::NOVALNET_PAY => [
                    'selectedMethod' => $this->novalnetRepository->getCustomerLastOrderPaymentMethod()
                ]
            ]
        ];
    }
}

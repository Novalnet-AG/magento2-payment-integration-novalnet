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
namespace Novalnet\Payment\Setup\Patch\Data;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RemoveAccessKey implements DataPatchInterface
{
    /**
     * @var \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config\Data
     */
    private $data;

    /**
     * @var \Magento\Framework\App\Config\ConfigResource\ConfigInterface
     */
    private $resourceConfig;

    /**
     * @var \Novalnet\Payment\Logger\NovalnetLogger
     */
    private $novalnetLogger;

    /**
     * @param \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $collectionFactory,
     * @param \Magento\Config\Model\ResourceModel\Config\Data $data
     * @param \Magento\Framework\App\Config\ConfigResource\ConfigInterface  $resourceConfig
     * @param \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
     */
    public function __construct(
        \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $collectionFactory,
        \Magento\Config\Model\ResourceModel\Config\Data $data,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface  $resourceConfig,
        \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->configResource = $data;
        $this->resourceConfig = $resourceConfig;
        $this->novalnetLogger = $novalnetLogger;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $novalnetGlobalConfig = [
            'novalnet_global/novalnet/payment_access_key',
            'novalnet_global/novalnet/signature',
            'payment/novalnet/payment_access_key',
            'payment/novalnet/signature'
        ];

        $novalnetMapping = [
            'novalnet_global/novalnet/live_mode'                        => 'payment/novalnet/live_mode',
            'novalnet_global/novalnet/enable_payment_logo'              => 'payment/novalnet/enable_payment_logo',
            'novalnet_global/novalnet/restore_cart'                     => 'payment/novalnet/restore_cart',
            'novalnet_global/merchant_script/test_mode'                 => 'payment/merchant_script/test_mode',
            'novalnet_global/merchant_script/mail_to_addr'              => 'payment/merchant_script/mail_to_addr',
            'payment/novalnetSepa/enable_guarantee'                     => 'payment/novalnetSepaGuarantee/active',
            'payment/novalnetSepa/sepa_duedate'                         => 'payment/novalnetSepa/due_date',
            'payment/novalnetSepaInstalment/sepa_duedate'               => 'payment/novalnetSepaInstalment/due_date',
            'payment/novalnetInvoice/enable_guarantee'                  => 'payment/novalnetInvoiceGuarantee/active',
            'payment/novalnetInvoice/payment_duration'                  => 'payment/novalnetInvoice/due_date',
            'payment/novalnetCashpayment/payment_duration'              => 'payment/novalnetCashpayment/due_date',
            'payment/novalnetSepa/guarantee_min_order_total'            => 'payment/novalnetSepaGuarantee/min_order_total',
            'payment/novalnetInvoice/guarantee_min_order_total'         => 'payment/novalnetInvoiceGuarantee/min_order_total',
            'payment/novalnetSepa/payment_guarantee_force'              => 'payment/novalnetSepaGuarantee/payment_guarantee_force',
            'payment/novalnetSepa/active'                               => 'payment/novalnetSepaGuarantee/payment_guarantee_force',
            'payment/novalnetInvoice/payment_guarantee_force'           => 'payment/novalnetInvoiceGuarantee/payment_guarantee_force',
            'payment/novalnetInvoice/active'                            => 'payment/novalnetInvoiceGuarantee/payment_guarantee_force',
            'payment/novalnetInvoiceInstalment/min_order_total'         => 'payment/novalnetInvoiceInstalment/min_order_total',
            'payment/novalnetSepaInstalment/min_order_total'            => 'payment/novalnetSepaInstalment/min_order_total',
            'payment/novalnetInvoiceInstalment/instalment_total_period' => 'payment/novalnetInvoiceInstalment/instalment_cycles',
            'payment/novalnetSepaInstalment/instalment_total_period'    => 'payment/novalnetSepaInstalment/instalment_cycles',
            'payment/novalnetCc/order_status_after_payment'             => 'payment/novalnetCc/order_status',
            'payment/novalnetPaypal/order_status_after_payment'         => 'payment/novalnetPaypal/order_status',
            'payment/novalnetBanktransfer/order_status_after_payment'   => 'payment/novalnetBanktransfer/order_status',
            'payment/novalnetIdeal/order_status_after_payment'          => 'payment/novalnetIdeal/order_status',
            'payment/novalnetEps/order_status_after_payment'            => 'payment/novalnetEps/order_status',
            'payment/novalnetGiropay/order_status_after_payment'        => 'payment/novalnetGiropay/order_status',
            'payment/novalnetPrzelewy/order_status_after_payment'       => 'payment/novalnetPrzelewy/order_status',
        ];

        //set default value for enable payment logo option
        $this->resourceConfig->saveConfig(
            'payment/novalnet/enable_payment_logo',
            1,
            'default',
            0
        );
        //set default value for restore cart
        $this->resourceConfig->saveConfig(
            'payment/novalnet/restore_cart',
            1,
            'default',
            0
        );
        //set default value for ip control
        $this->resourceConfig->saveConfig(
            'payment/merchant_script/test_mode',
            0,
            'default',
            0
        );

        $novalnetConfigurations = $this->collectionFactory->create()->addFieldToFilter('path', [
            ['like' => '%novalnet_global%'],
            ['like' => '%novalnetInvoice%'],
            ['like' => '%novalnetSepa%'],
            ['like' => '%novalnetCashpayment%'],
            ['like' => '%order_status_after_payment'],
            ['like' => '%paymentaction'],
        ]);

        foreach ($novalnetConfigurations as $config) {
            if (in_array($config->getPath(), $novalnetGlobalConfig)) {
                //Remove payment access key and signature
                $this->configResource->delete($config);
                $this->novalnetLogger->notice('Removed Novalnet global configuration. The config path: ' . $config->getPath());
            } elseif (isset($novalnetMapping[$config->getPath()])) {
                $value = $config->getValue();
                //update guarantee min order total into EUR
                if (preg_match('/guarantee_min_order_total/', $config->getPath()) && !empty($config->getValue())) {
                    $value = $this->getFormattedAmount(
                        $config->getValue(),
                        'RAW'
                    );
                } elseif (preg_match('/guarantee_min_order_total/', $config->getPath()) && empty($config->getValue())) {
                    $value = $this->getFormattedAmount(
                        999,
                        'RAW'
                    );
                }

                //update min order total for Instalment payments
                if (preg_match('/Instalment\/min_order_total/', $config->getPath())) {
                    $value = '19.98';
                }

                //update Guarantee Force configuration
                if (preg_match('/payment_guarantee_force/', $config->getPath())) {
                    $value = 0;
                }

                $this->resourceConfig->saveConfig(
                    $novalnetMapping[$config->getPath()],
                    $value,
                    $config->getScope(),
                    $config->getScopeId()
                );
                $this->novalnetLogger->notice(
                    'Update Novalnet payment config path & value. Old path: ' . $config->getPath() .
                    ' New path: ' . $novalnetMapping[$config->getPath()] . ' Values is: ' . $value
                );
            } else {
                //update payment_action configuration
                if (preg_match('/novalnet/', $config->getPath()) && preg_match('/paymentaction/', $config->getPath())) {
                    $path = preg_replace('/paymentaction/i', 'payment_action', $config->getPath());
                    if ($config->getValue() == 1) {
                        $value = AbstractMethod::ACTION_AUTHORIZE;
                    } else {
                        $value = AbstractMethod::ACTION_AUTHORIZE_CAPTURE;
                    }
                    $this->resourceConfig->saveConfig(
                        $path,
                        $value,
                        $config->getScope(),
                        $config->getScopeId()
                    );
                    $this->novalnetLogger->notice(
                        'Update Novalnet payment config path & value. Old path: ' . $config->getPath() .
                        ' New path: ' . $path . ' Values is: ' . $value
                    );
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
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
}

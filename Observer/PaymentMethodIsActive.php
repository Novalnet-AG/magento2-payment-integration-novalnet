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
namespace Novalnet\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Novalnet\Payment\Model\Ui\ConfigProvider;

class PaymentMethodIsActive implements ObserverInterface
{
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $salesOrderModel;

    /**
     * @var \Novalnet\Payment\Model\NNConfig
     */
    protected $novalnetConfig;

    /**
     * @var \Novalnet\Payment\Helper\Request
     */
    protected $novalnetRequestHelper;

    /**
     * @param \Magento\Sales\Model\Order $salesOrderModel
     * @param \Novalnet\Payment\Model\NNConfig $novalnetConfig
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     */
    public function __construct(
        \Magento\Sales\Model\Order $salesOrderModel,
        \Novalnet\Payment\Model\NNConfig $novalnetConfig,
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper
    ) {
        $this->salesOrderModel = $salesOrderModel;
        $this->novalnetConfig = $novalnetConfig;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
    }

    /**
     * @param Observer $observer
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $methodInstance = $event->getData('method_instance');
        $quote = $event->getData('quote');
        $result = $event->getData('result');
        $paymentMethodCode = $methodInstance->getCode();

        if ($quote && $result->getData('is_available') && strpos($paymentMethodCode, 'novalnet') === 0) {

            $available = $this->validateBasicParams()
                && $this->validateOrdersCount($paymentMethodCode)
                && $this->validateCustomerGroup($paymentMethodCode);

            if (in_array(
                $paymentMethodCode,
                [
                    ConfigProvider::NOVALNET_SEPA_GUARANTEE,
                    ConfigProvider::NOVALNET_INVOICE_GUARANTEE,
                    ConfigProvider::NOVALNET_SEPA_INSTALMENT,
                    ConfigProvider::NOVALNET_INVOICE_INSTALMENT
                ]
            )) {
                $orderAmount = $this->novalnetRequestHelper->getFormattedAmount($quote->getBaseGrandTotal());
                $countryCode = strtoupper($quote->getBillingAddress()->getCountryId()); // Get country code
                $b2b = $this->novalnetConfig->getPaymentConfig($paymentMethodCode, 'allow_b2b_customer');
                $force = "";

                if (in_array(
                    $paymentMethodCode,
                    [ConfigProvider::NOVALNET_SEPA_INSTALMENT, ConfigProvider::NOVALNET_INVOICE_INSTALMENT]
                )) {
                    $allcycle = explode(',', $this->novalnetConfig->getPaymentConfig($paymentMethodCode, 'instalment_cycles'));
                    $orderAmount = !empty($allcycle[0]) ? ($orderAmount / $allcycle[0]) : $orderAmount;
                } else {
                    $force = $this->novalnetConfig->getPaymentConfig($paymentMethodCode, 'payment_guarantee_force');
                }

                if (!$force && ($orderAmount < 999 || $quote->getBaseCurrencyCode() != 'EUR'
                    || !$this->novalnetConfig->isAllowedCountry($countryCode, $b2b) ||
                    ($this->novalnetRequestHelper->isAdmin() && !$this->checkBillingShippingAreSame($quote)))
                ) {
                    $available = false;
                }
            }
            if (in_array(
                $paymentMethodCode,
                [
                ConfigProvider::NOVALNET_SEPA,
                ConfigProvider::NOVALNET_INVOICE,
                ]
            )) {
                $paymentCode = preg_match('/Sepa/', $paymentMethodCode) ? ConfigProvider::NOVALNET_SEPA_GUARANTEE : ConfigProvider::NOVALNET_INVOICE_GUARANTEE;
                $force = $this->novalnetConfig->getPaymentConfig($paymentCode, 'payment_guarantee_force');
                $active = $this->novalnetConfig->getPaymentConfig($paymentCode, 'active');
                if ($active && !$force) {
                    $available = false;
                }
            }

            $result->setData('is_available', $available);
        }
    }

    /**
     * Validate Novalnet basic params
     *
     * @return bool
     */
    private function validateBasicParams()
    {
        return ($this->novalnetConfig->getGlobalConfig('signature') &&
            $this->novalnetConfig->getGlobalConfig('payment_access_key') &&
            $this->novalnetRequestHelper->checkIsNumeric($this->novalnetConfig->getGlobalConfig('tariff_id')));
    }

    /**
     * Check orders count by customer id
     *
     * @param  $paymentMethodCode
     * @return bool
     */
    public function validateOrdersCount($paymentMethodCode)
    {
        $minOrderCount = $this->novalnetConfig->getPaymentConfig($paymentMethodCode, 'orders_count');
        if ($minOrderCount) {
            if ($this->novalnetRequestHelper->getCustomerSession()->isLoggedIn()) {
                $customerId = $this->novalnetRequestHelper->getCustomerSession()->getCustomer()->getId();
            } elseif ($this->novalnetRequestHelper->isAdmin()) {
                $customerId = $this->novalnetRequestHelper->getAdminCheckoutSession()->getCustomerId();
            } else {
                return false;
            }

            // get orders by customer id
            $ordersCount = $this->salesOrderModel->getCollection()->addFieldToFilter('customer_id', $customerId)
                ->count();
            return ($ordersCount >= trim($minOrderCount));
        } else {
            return true;
        }
    }

    /**
     * Check whether the payment is available for current customer group
     *
     * @param  $paymentMethodCode
     * @return boolean
     */
    public function validateCustomerGroup($paymentMethodCode)
    {
        // Excluded customer groups from payment configuration
        $excludedGroups = $this->novalnetConfig->getPaymentConfig($paymentMethodCode, 'user_group_excluded');
        if (!$this->novalnetRequestHelper->isAdmin() && strlen($excludedGroups)) {
            $excludedGroups = explode(',', $excludedGroups);
            $customerGroupId = $this->novalnetRequestHelper->getCustomerSession()->getCustomer()->getGroupId();
            if (!$this->novalnetRequestHelper->getCustomerSession()->isLoggedIn()) {
                $customerGroupId = 0;
            }
            return !in_array($customerGroupId, $excludedGroups);
        }

        return true;
    }

    /**
     * check billing and shipping address are same for guarantee payments
     *
     * @param  Varien_Object $quote
     * @return boolean
     */
    public function checkBillingShippingAreSame($quote)
    {
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = !$quote->getIsVirtual() ? $quote->getShippingAddress() : '';
        return ($shippingAddress == '' || (($billingAddress->getCity() == $shippingAddress->getCity())
            && (implode(',', $billingAddress->getStreet()) == implode(',', $shippingAddress->getStreet()))
            && ($billingAddress->getPostcode() == $shippingAddress->getPostcode())
            && ($billingAddress->getCountry() == $shippingAddress->getCountry()))
        );
    }
}

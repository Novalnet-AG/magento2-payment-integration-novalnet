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
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Novalnet\Payment\Model\Ui\ConfigProvider;

class DataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * @var \Novalnet\Payment\Helper\Request
     */
    protected $novalnetRequestHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $datetime;

    /**
     * @var \Novalnet\Payment\Model\NNConfig
     */
    protected $novalnetConfig;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Novalnet\Payment\Logger\NovalnetLogger
     */
    private $novalnetLogger;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    private $backendQuoteSession;

    /**
     * @param Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     * @param \Novalnet\Payment\Model\NNConfig $novalnetConfig
     * @param \Magento\Backend\Model\Session\Quote $backendQuoteSession
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
     */
    public function __construct(
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Novalnet\Payment\Model\NNConfig $novalnetConfig,
        \Magento\Backend\Model\Session\Quote $backendQuoteSession,
        \Magento\Checkout\Model\Cart $cart,
        \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
    ) {
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->datetime = $datetime;
        $this->novalnetConfig = $novalnetConfig;
        $this->backendQuoteSession = $backendQuoteSession;
        $this->cart = $cart;
        $this->novalnetLogger = $novalnetLogger;
    }

    /**
     * Get Quote
     *
     * @param none
     * @return object $quote
     */
    public function getCartQuote()
    {
        $quote = (!$this->novalnetRequestHelper->isAdmin())
            ? $this->cart->getQuote()
            : $this->backendQuoteSession->getQuote();
        return $quote;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $method = $this->readMethodArgument($observer);
        $paymentMethodCode = $method->getCode();
        $this->novalnetRequestHelper->getMethodSession($paymentMethodCode, true);

        $parameters = [
            '_pan_hash', '_unique_id', '_do_redirect', '_iban', '_create_token', '_token', '_cycle', '_dob', '_wallet_token'
        ];
        $this->setMethodSession($observer, $paymentMethodCode, $parameters);
    }

    /**
     * Set method session
     *
     * @param Object $observer
     * @param string $paymentMethodCode
     * @param array $parameters
     *
     * @return void
     */
    private function setMethodSession($observer, $paymentMethodCode, $parameters)
    {
        $additionalData = $this->readDataArgument($observer)->getAdditionalData();
        $methodSession = $this->novalnetRequestHelper->getMethodSession($paymentMethodCode);
        $this->validateGuaranteePayment($paymentMethodCode);
        $this->validateBillingShippingAddress($paymentMethodCode);

        foreach ($parameters as $parameter) {
            if (!empty($additionalData[$paymentMethodCode . $parameter])) {
                $methodSession->setData(
                    $paymentMethodCode . $parameter,
                    $additionalData[$paymentMethodCode . $parameter]
                );
            }
        }

        if (!empty($additionalData[$paymentMethodCode . '_dob']) || !$this->validateSubscriptionConditions($paymentMethodCode)) {
            $forcedPayment = '';
            if (preg_match('/(.*)Guarantee/', $paymentMethodCode, $reassignedPaymentMethodCode)) {
                $forcedPayment = $reassignedPaymentMethodCode[1];
            }
            $birthDate = !empty($additionalData[$paymentMethodCode . '_dob']) ? $additionalData[$paymentMethodCode . '_dob'] : '';
            if (!$this->validateBirthDate($birthDate) || !$this->validateSubscriptionConditions($paymentMethodCode)) {
                if ($this->novalnetConfig->getPaymentConfig($paymentMethodCode, 'payment_guarantee_force') &&
                    $forcedPayment && $this->novalnetConfig->getPaymentConfig($forcedPayment, 'active')) {
                    $methodSession = $this->novalnetRequestHelper->getMethodSession($paymentMethodCode);
                    $forcedPaymentSession = $this->novalnetRequestHelper->getMethodSession($forcedPayment);
                    if ($forcedPayment == ConfigProvider::NOVALNET_SEPA) {
                        $forcedPaymentSession->setData($forcedPayment.'_iban', $methodSession->getData($paymentMethodCode.'_iban'));
                    }
                    $forcedPaymentSession->setData($forcedPayment.'_token', $methodSession->getData($paymentMethodCode.'_token'));
                    $forcedPaymentSession->setData($forcedPayment.'_create_token', $methodSession->getData($paymentMethodCode.'_create_token'));
                    $forcedPaymentSession->setData($forcedPayment.'_dob', $methodSession->getData($paymentMethodCode.'_dob'));
                    $quote = $this->getCartQuote();
                    $quote->getPayment()->setMethod($forcedPayment);
                    if ($this->novalnetRequestHelper->isAdmin()) {
                        $methodSession->setData($paymentMethodCode.'_force', 1);
                        $methodSession->setData($paymentMethodCode.'_force_payment', $forcedPayment);
                    }
                    $this->novalnetLogger->notice("Update payment method from $paymentMethodCode to $forcedPayment");
                } else {
                    throw new \Magento\Framework\Exception\LocalizedException(__('You need to be at least 18 years old'));
                }
            }
        }
    }

    /**
     * Check customer DOB is valid
     *
     * @param  string $birthDate
     * @return boolean
     */
    public function validateBirthDate($birthDate)
    {
        if (empty($birthDate)) {
            return true;
        }
        $age = strtotime('+18 years', strtotime($birthDate));
        $currentDate = $this->datetime->gmtTimestamp();
        return ($currentDate < $age) ? false : true;
    }

    /**
     * Check Billing and Shipping are Same
     *
     * @param  string $paymentMethodCode
     * @return none
     */
    public function validateBillingShippingAddress($paymentMethodCode)
    {
        $quote = $this->getCartQuote();
        if ($quote->getIsVirtual()) {
            return;
        }
        $billingAddress = $quote->getBillingAddress();
        $shipingAddress = $quote->getShippingAddress();
        $billingStreet = $this->getStreet($billingAddress);
        $shipingStreet = $this->getStreet($shipingAddress);
        if (!($billingAddress->getFirstname() == $shipingAddress->getFirstname() &&
                $billingAddress->getLastname() == $shipingAddress->getLastname() &&
                $billingStreet == $shipingStreet &&
                $billingAddress->getCity() == $shipingAddress->getCity() &&
                $billingAddress->getCountryId() ==$shipingAddress->getCountryId() &&
                $billingAddress->getPostcode() == $shipingAddress->getPostcode()) &&
                in_array(
                    $paymentMethodCode,
                    [
                        ConfigProvider::NOVALNET_INVOICE_GUARANTEE,
                        ConfigProvider::NOVALNET_SEPA_GUARANTEE,
                        ConfigProvider::NOVALNET_SEPA_INSTALMENT,
                        ConfigProvider::NOVALNET_INVOICE_INSTALMENT
                    ]
                ) &&
                !$this->novalnetConfig->getPaymentConfig($paymentMethodCode, 'payment_guarantee_force') && !$quote->isVirtual()) {
            $errorPaymentName = in_array(
                $paymentMethodCode,
                [ConfigProvider::NOVALNET_SEPA_GUARANTEE, ConfigProvider::NOVALNET_INVOICE_GUARANTEE]
            ) ? __('payment guarantee') : __('instalment payment');
            throw new \Magento\Framework\Exception\LocalizedException(__(
                "The payment cannot be processed, because the basic requirements for the %1 haven't been met (The shipping address must be the same as the billing address)",
                $errorPaymentName
            ));
        }
    }

    /**
     * Check Allowed Country
     *
     * @param  string $paymentMethodCode
     * @return none
     */
    public function validateGuaranteePayment($paymentMethodCode)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $coreSession = $objectManager->create(\Magento\Framework\Session\SessionManagerInterface::class);

        //skip conditions for recurring order
        if ($coreSession->getRecurringProcess()) {
            return true;
        }

        if (in_array(
            $paymentMethodCode,
            [
                ConfigProvider::NOVALNET_INVOICE_GUARANTEE,
                ConfigProvider::NOVALNET_SEPA_GUARANTEE,
                ConfigProvider::NOVALNET_SEPA_INSTALMENT,
                ConfigProvider::NOVALNET_INVOICE_INSTALMENT
            ]
        )) {
            $quote = $this->getCartQuote();
            $orderAmount = $this->novalnetRequestHelper->getFormattedAmount($quote->getBaseGrandTotal());
            $countryCode = strtoupper($quote->getBillingAddress()->getCountryId()); // Get country code
            $b2b = $this->novalnetConfig->getPaymentConfig($paymentMethodCode, 'allow_b2b_customer');
            $billingAddress = $quote->getBillingAddress();
            $errorPaymentName = in_array(
                $paymentMethodCode,
                [ConfigProvider::NOVALNET_SEPA_GUARANTEE, ConfigProvider::NOVALNET_INVOICE_GUARANTEE]
            ) ? __('payment guarantee') : __('instalment payment');
            $errorText = '';
            if ($quote->getBaseCurrencyCode() != 'EUR') {
                $errorText = __('Only EUR currency allowed %1', $errorPaymentName);
            } elseif ($orderAmount < 999) {
                $errorText = __('Minimum order amount should be %1', $errorPaymentName);
            } elseif (!$this->novalnetConfig->isAllowedCountry($countryCode, $b2b)) {
                $errorText = __('Only DE, AT, CH countries allowed %1', $errorPaymentName);
            }

            if (!empty($errorText)) {
                throw new \Magento\Framework\Exception\LocalizedException(__(
                    $errorText
                ));
            }
        }
    }

    /**
     * Validate subscription conditions for Guarantee payments
     *
     * @param string $paymentMethodCode
     * @return string
     */
    public function validateSubscriptionConditions($paymentMethodCode)
    {
        if (in_array(
            $paymentMethodCode,
            [
                ConfigProvider::NOVALNET_INVOICE_GUARANTEE,
                ConfigProvider::NOVALNET_SEPA_GUARANTEE
            ]
        )) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $coreSession = $objectManager->create(\Magento\Framework\Session\SessionManagerInterface::class);

            //skip conditions for recurring order
            if ($coreSession->getRecurringProcess()) {
                return true;
            }

            $errorText = '';
            $quote = $this->getCartQuote();
            $subscription = false;
            if (!empty($quote->getItems())) {
                foreach ($quote->getItems() as $item) {
                    $additionalData = (!empty($item->getAdditionalData())) ? json_decode($item->getAdditionalData(), true) : [];
                    if (!empty($additionalData['period_unit']) && !empty($additionalData['billing_frequency'])) {
                        $subscription = true;
                        break;
                    }
                }
            }
            if ($subscription) {
                if ($quote->getItemsCount() > 1) {
                    $errorText = __('Multi cart not supported for this payment');
                } elseif ($quote->getItemsCount() == 1) {
                    foreach ($quote->getAllItems() as $item) {
                        $orderAmount = $this->novalnetRequestHelper->getFormattedAmount($item->getBaseRowTotal());
                        if ($orderAmount < 999) {
                            $errorText = __('Minimum order amount should be %1', 'payment guarantee');
                        }
                    }
                }
            }

            if (!empty($errorText) && $this->novalnetConfig->getPaymentConfig($paymentMethodCode, 'payment_guarantee_force')) {
                return false;
            } if (!empty($errorText)) {
                throw new \Magento\Framework\Exception\LocalizedException(__(
                    $errorText
                ));
            }
        }

        return true;
    }

    /**
     * Get Street from address
     *
     * @param object $address
     * @return string
     */
    public function getStreet($address)
    {
        if (method_exists($address, 'getStreetFull')) {
            $street = $address->getStreetFull();
        } else {
            $street = implode(' ', [$address->getStreetLine1(), $address->getStreetLine2()]);
        }

        return $street;
    }
}

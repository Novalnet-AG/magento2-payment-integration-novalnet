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
     * @var \Novalnet\Payment\Helper\Data
     */
    protected $novalnetHelper;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    private $backendQuoteSession;

    /**
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     * @param \Magento\Backend\Model\Session\Quote $backendQuoteSession
     * @param \Magento\Checkout\Model\Cart $cart
     */
    public function __construct(
        \Novalnet\Payment\Helper\Data $novalnetHelper,
        \Magento\Backend\Model\Session\Quote $backendQuoteSession,
        \Magento\Checkout\Model\Cart $cart
    ) {
        $this->novalnetHelper = $novalnetHelper;
        $this->backendQuoteSession = $backendQuoteSession;
        $this->cart = $cart;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $paymentMethodCode = $this->readMethodArgument($observer)->getCode();
        if ($paymentMethodCode == ConfigProvider::NOVALNET_PAY) {
            $data = $this->readDataArgument($observer)->getAdditionalData();
            $paymentData = !empty($data[ConfigProvider::NOVALNET_PAY . '_payment_data']) ? $data[ConfigProvider::NOVALNET_PAY . '_payment_data'] : '{}';
            $additionalData = ($this->novalnetHelper->isJSON($paymentData)) ? $this->novalnetHelper->jsonDecode($paymentData) : $paymentData;
            $this->novalnetHelper->getMethodSession($paymentMethodCode, true);
            $methodSession = $this->novalnetHelper->getMethodSession($paymentMethodCode);

            if (!empty($additionalData['payment_details'])) {
                foreach ($additionalData['payment_details'] as $key => $value) {
                    $methodSession->setData(
                        $paymentMethodCode . '_' . $key,
                        $value
                    );
                }
            }

            if (!empty($additionalData['booking_details'])) {
                foreach ($additionalData['booking_details'] as $key => $value) {
                    $methodSession->setData(
                        $paymentMethodCode . '_' . $key,
                        $value
                    );
                }
            }

            if (!empty($additionalData['recurring_details'])) {
                foreach ($additionalData['recurring_details'] as $key => $value) {
                    $methodSession->setData(
                        $paymentMethodCode . '_' . $key,
                        $value
                    );
                }
            }

            $this->validateSubscriptionConditions($paymentMethodCode);
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
        $methodSession = $this->novalnetHelper->getMethodSession($paymentMethodCode);
        $paymentType = $methodSession->getData($paymentMethodCode . '_type');
        if (in_array($paymentType, ['GUARANTEED_INVOICE', 'GUARANTEED_DIRECT_DEBIT_SEPA'])) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            if ($objectManager->create(\Magento\Framework\Session\SessionManagerInterface::class)->getRecurringProcess()) {
                return true;
            }

            $quote = $this->getCartQuote();
            foreach ($quote->getItems() as $item) {
                $additionalData = $this->novalnetHelper->jsonDecode($item->getAdditionalData());
                if (!empty($additionalData['period_unit']) && !empty($additionalData['billing_frequency'])) {
                    if ($quote->getItemsCount() > 1) {
                        throw new \Magento\Framework\Exception\LocalizedException(__('Multi cart not supported for this payment'));
                    } elseif ($quote->getItemsCount() == 1) {
                        foreach ($quote->getAllItems() as $item) {
                            $orderAmount = $this->novalnetHelper->getFormattedAmount($item->getBaseRowTotal());
                            if ($orderAmount < 999) {
                                throw new \Magento\Framework\Exception\LocalizedException(__('Minimum order amount should be %1', 'payment guarantee'));
                            }
                        }
                    }

                    break;
                }
            }
        }

        return true;
    }

    /**
     * Get Quote
     *
     * @return object $quote
     */
    public function getCartQuote()
    {
        return (!$this->novalnetHelper->isAdmin())
            ? $this->cart->getQuote()
            : $this->backendQuoteSession->getQuote();
    }
}

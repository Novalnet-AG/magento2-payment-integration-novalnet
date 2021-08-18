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
namespace Novalnet\Payment\Plugin\Checkout;

use Novalnet\Payment\Model\Ui\ConfigProvider;

class LayoutProcessor
{
    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    protected $novalnetHelper;

    /**
     * @var \Novalnet\Payment\Model\NNConfig
     */
    protected $novalnetConfig;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param \Novalnet\Payment\Helper\Request $novalnetHelper
     * @param \Novalnet\Payment\Model\NNConfig $NNConfig
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Novalnet\Payment\Helper\Request $novalnetHelper,
        \Novalnet\Payment\Model\NNConfig $NNConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->novalnetHelper = $novalnetHelper;
        $this->novalnetConfig = $NNConfig;
        $this->scopeConfig = $scopeConfig;
    }

    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        array $jsLayout
    ) {
        $isGuestCheckoutEnabled = in_array('guest_checkout_page', explode(',', $this->novalnetConfig->getPaymentConfig(ConfigProvider::NOVALNET_APPLEPAY, 'enabled_pages')));
        $isApplePayEnabled = $this->novalnetConfig->getPaymentConfig(ConfigProvider::NOVALNET_APPLEPAY, 'active');

        if ($this->novalnetHelper->getCustomerSession()->isLoggedIn() || !$isGuestCheckoutEnabled || !$isApplePayEnabled) {
            if (isset($jsLayout['components']['checkout']['children']['steps']
                ['children']['shipping-step']['children']['novalnet-applepay-guest-checkout'])) {
                    unset($jsLayout['components']['checkout']['children']['steps']
                         ['children']['shipping-step']['children']['novalnet-applepay-guest-checkout']);
            }
        }

        return $jsLayout;
    }
}

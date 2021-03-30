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
namespace Novalnet\Payment\Model\Adminhtml\Source;

/**
 * Source model for enabled Novalnet payment methods
 */
class Activemethods implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Novalnet\Payment\Model\NNConfig $novalnetConfig
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Novalnet\Payment\Model\NNConfig $novalnetConfig
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->novalnetConfig = $novalnetConfig;
    }

    /**
     * Options getter (Active Payment methods)
     *
     * @return array
     */
    public function toOptionArray()
    {
        $methods = [];
        $activePayment = false;
        $storeId = (int) $this->request->getParam('store');
        $websiteId = (int) $this->request->getParam('website');
        $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $scopeValue = null;

        if ($storeId) {
            $scopeValue = $storeId;
        }

        if ($websiteId) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
            $scopeValue = $websiteId;
        }

        foreach ($this->novalnetConfig->getPaymentMethodCodes() as $paymentCode) {
            $paymentActive = $this->scopeConfig->getValue('payment/' . $paymentCode . '/active', $scope, $scopeValue);

            if ($paymentActive == true) {
                $paymentTitle = $this->scopeConfig->getValue(
                    'payment/' . $paymentCode . '/title',
                    $scope,
                    $scopeValue
                );
                $methods[$paymentCode] = ['value' => $paymentCode, 'label' => $paymentTitle];
                $activePayment = true;
            }
        }

        if (!$activePayment) {
            $methods[$paymentCode] = ['value' => '', 'label' => __('No active payment method for this store')];
        }

        return $methods;
    }
}

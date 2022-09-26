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
namespace Novalnet\Payment\Block\Form;

class InvoiceInstalment extends \Magento\Payment\Block\Form
{
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $priceHelper;

    /**
     * @var \Novalnet\Payment\Model\NNConfig
     */
    protected $novalnetConfig;

    /**
     * @var \Novalnet\Payment\Model\Ui\ConfigProvider
     */
    protected $novalnetConfigProvider;

    /**
     * @var \Novalnet\Payment\Helper\Request
     */
    protected $novalnetRequestHelper;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelperRequest;

    /**
     * @var \Magento\Directory\Model\Currency
     */
    protected $currency;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Novalnet\Payment\Model\NNConfig $novalnetConfig
     * @param \Novalnet\Payment\Model\Ui\ConfigProvider $novalnetConfigProvider
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelperRequest
     * @param \Magento\Directory\Model\Currency $currency
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Novalnet\Payment\Model\NNConfig $novalnetConfig,
        \Novalnet\Payment\Model\Ui\ConfigProvider $novalnetConfigProvider,
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelperRequest,
        \Magento\Directory\Model\Currency $currency,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setTemplate('Novalnet_Payment::form/InvoiceInstalment.phtml');
        $this->priceHelper = $priceHelper;
        $this->novalnetConfig = $novalnetConfig;
        $this->novalnetConfigProvider = $novalnetConfigProvider;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->jsonHelperRequest = $jsonHelperRequest;
        $this->currency = $currency;
        $this->storeManager = $storeManager;
    }

    /**
     * Get payment logo enabled status
     *
     * @param  string $paymentMethodcode
     * @return string
     */
    public function getPaymentLogo($paymentMethodcode)
    {
        return $this->novalnetConfigProvider->getPaymentLogo($paymentMethodcode);
    }

    /**
     * Verify whether the payment is in Test mode
     *
     * @param  string $paymentMethodcode
     * @return int
     */
    public function getTestMode($paymentMethodcode)
    {
        return $this->novalnetConfig->getTestMode($paymentMethodcode);
    }

    /**
     * Get Novalnet Guarantee instalment cycles
     *
     * @return null|string
     */
    public function getInstalmentCycles()
    {
        return $this->novalnetConfig->getPaymentConfig($this->getMethodCode(), 'instalment_cycles');
    }

    /**
     * Get amount with currency
     *
     * @param  int $amount
     * @return null|string
     */
    public function updateCurrency($amount)
    {
        return $this->priceHelper->currency($amount, true, false);
    }
    
    /**
     * Returns Instalment cycle details api URL for the Novalnet module
     *
     * @param  none
     * @return string
     */
    public function getInstalmentCycleDetailUrl()
    {
        $baseUrl = $this->_storeManager->getStore()->getBaseUrl();
        return str_replace('index.php/', '', $baseUrl) . 'rest/V1/novalnet/payment/instalment_cycle/';
    }

    /**
     * Get novalnet helper
     *
     * @param  null
     * @return DataObject
     */
    public function novalnetHelper()
    {
        return $this->novalnetRequestHelper;
    }

    /**
     * Get json helper request
     *
     * @param  null
     * @return DataObject
     */
    public function jsonHelper()
    {
        return $this->jsonHelperRequest;
    }

    /**
     * Get currency symbol for current locale and currency code
     *
     * @return string
     */
    public function getCurrentCurrencySymbol()
    {
        $store = $this->storeManager->getStore();
        $currencyCode = $store->getCurrentCurrencyCode();
        $currentCurrency = trim($currencyCode);
        $currency = $this->currency->load($currentCurrency);
        return $currency->getCurrencySymbol();
    }
}

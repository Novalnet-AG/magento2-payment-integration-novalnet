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
namespace Novalnet\Payment\Block\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Novalnet Payment webhook URL renderer
 */
class WebhookURL extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    protected $_template = 'Novalnet_Payment::config/webhooks_configuration.phtml';

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Novalnet\Payment\Model\NNConfig $config,
        \Magento\Framework\App\Request\Http $request,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->storeManager = $context->getStoreManager();
        $this->request = $request;
        $this->storeId = $this->getAdminConfigStoreId();
    }

    /**
     * Render element html
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Returns storeId
     *
     * @param none
     * @return int
     */
    public function getAdminConfigStoreId()
    {
        $storeId = (int)$this->request->getParam('store', 0);
        $websiteId = (int)$this->request->getParam('website', 0);

        if ($storeId) {
            return $storeId;
        } elseif ($websiteId) {
            return $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
        }

        return 0; // Default store
    }

    /**
     * Returns Button html
     *
     * @param none
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            [
                'id' => 'novalnet_configure_webhooks',
                'label' => __('Configure'),
            ]
        );

        return $button->toHtml();
    }

    /**
     * Returns Ajax URL
     *
     * @param none
     * @return string
     */
    public function getAjaxUrl()
    {
        $storeBaseUrl = $this->storeManager->getStore()->getBaseUrl();
        return str_replace('index.php/', '', $storeBaseUrl) . 'rest/V1/novalnet/config_webhook_url/';
    }

    /**
     * Returns Webhook URL
     *
     * @param none
     * @return string
     */
    public function getWebHookUrl()
    {
        $webhookUrl = $this->config->getMerchantScriptConfig('vendor_script_url');
        $storeBaseUrl = $this->storeManager->getStore()->getBaseUrl();
        return !empty($webhookUrl) ? $webhookUrl : str_replace('index.php/', '', $storeBaseUrl) . 'rest/V1/novalnet/callback';
    }

    /**
     * Returns element html
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElements($element);
        return $this->_toHtml();
    }

    /**
     * Return element value
     *
     * @param  none
     * @return object
     */
    public function getElement()
    {
        return $this->getElements();
    }
}

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
namespace Novalnet\Payment\Block;

use Magento\Framework\View\Element\Template;

class VendorAutoConfig extends Template
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Laminas\Uri\Uri
     */
    protected $uriParser;

    /**
     * @param Template\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Laminas\Uri\Uri $uriParser
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Laminas\Uri\Uri $uriParser,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->scopeConfig = $context->getScopeConfig();
        $this->_storeManager = $storeManager;
        $this->uriParser = $uriParser;
    }

    /**
     * Returns vendor auto config url for the Novalnet module
     *
     * @return string
     */
    public function getVendorAutoConfigUrl()
    {
        $baseUrl = $this->_storeManager->getStore()->getBaseUrl();
        return str_replace('index.php/', '', $baseUrl) . 'rest/V1/novalnet/activate_product_key/';
    }

    /**
     * Gets request section value
     *
     * @return string|null
     */
    public function getSectionParam()
    {
        return ($this->getRequest()->getParam('section') ? $this->getRequest()->getParam('section') : '');
    }

    /**
     * Retrieves Store configuration values
     *
     * @param  string $path
     * @return string
     */
    public function getConfigValue($path)
    {
        $scope = '';
        $id = '';
        if ($this->getRequest()->getParam('website', 0)) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
            $id = $this->getRequest()->getParam('website', 0);
        } elseif ($this->getRequest()->getParam('store', 0)) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $id = $this->getRequest()->getParam('store', 0);
        }

        if ($scope && $id) {
            return $this->scopeConfig->getValue($path, $scope, $id);
        } else {
            return $this->scopeConfig->getValue($path);
        }
    }
}

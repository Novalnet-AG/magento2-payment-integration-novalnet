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
namespace Novalnet\Payment\Block\Checkout\Cart;
 
class ApplepayConfig extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Novalnet\Payment\Helper\Request
     */
    protected $novalnetRequestHelper;

    /**
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Registry $registry,
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->productFactory = $productFactory;
        $this->registry = $registry;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get Payment method active status
     *
     * @param string $page
     * @return bool
     */
    public function isPageEnabledForApplePay($page)
    {
        return $this->novalnetRequestHelper->isPageEnabledForApplePay($page);
    }

    /**
     * Get Product Id
     *
     * @param  none
     * @return int
     */
    public function getProductId()
    {
        $product = $this->registry->registry('product');
        return $product->getId();
    }

    /**
     * Get Product Type
     *
     * @param  none
     * @return array
     */
    public function loadProductById()
    {
        $productId = $this->getProductId();
        $model = $this->productFactory->create();
        $product = $model->load($productId);
        return [
            'productId' => $product->getId(),
            'isVirtual' => ($product->getTypeId() == 'downloadable' || $product->getTypeId() == 'virtual') ? true : false
        ];
    }
}

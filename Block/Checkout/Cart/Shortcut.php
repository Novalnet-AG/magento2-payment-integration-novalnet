<?php

namespace Novalnet\Payment\Block\Checkout\Cart;

use Novalnet\Payment\Block\Checkout\Cart\ShortcutButtonsConfig;
use Magento\Catalog\Block\ShortcutInterface;

class Shortcut extends ShortcutButtonsConfig implements ShortcutInterface
{
    public const ALIAS_ELEMENT_INDEX = 'alias';

    /**
     * Template file for the block
     *
     * @var string
     */
    protected $_template = 'Novalnet_Payment::checkout/MinicartShortcut.phtml';

    /**
     * @var bool
     */
    private $isMiniCart = false;

    /**
     * @var bool
     */
    private $isShoppingCart = false;

    /**
     * Get shortcut alias
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->getData(self::ALIAS_ELEMENT_INDEX);
    }

    /**
     * Set is in catalog product
     *
     * @param bool $isCatalog
     *
     * @return $this
     */
    public function setIsInCatalogProduct($isCatalog)
    {
        $this->isMiniCart = !$isCatalog;
        return $this;
    }

    /**
     * Set is in shopping cart
     *
     * @param bool $isShoppingCart
     * @return void
     */
    public function setIsShoppingCart($isShoppingCart)
    {
        $this->isShoppingCart = $isShoppingCart;

        if ($isShoppingCart) {
            $this->_template = 'Novalnet_Payment::checkout/CartPageShortcut.phtml';
        } else {
            $this->_template = 'Novalnet_Payment::checkout/MinicartShortcut.phtml';
        }
    }

    /**
     * Is Should Rendered
     *
     * @return bool
     */
    protected function shouldRender()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $session = $objectManager->create(\Magento\Checkout\Model\Session::class);

        if ($this->isShoppingCart) {
            return true;
        }

        return $this->isMiniCart;
    }

    /**
     * Render the block if needed
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->shouldRender()) {
            return '';
        }

        return parent::_toHtml();
    }
}

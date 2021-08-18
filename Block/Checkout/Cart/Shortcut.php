<?php

namespace Novalnet\Payment\Block\Checkout\Cart;

use Novalnet\Payment\Block\Checkout\Cart\ApplepayConfig;
use Magento\Catalog\Block\ShortcutInterface;

class Shortcut extends ApplepayConfig implements ShortcutInterface
{
    const ALIAS_ELEMENT_INDEX = 'alias';

    /**
     * Template file for the block
     *
     * @var string
     */
    protected $_template = 'Novalnet_Payment::checkout/ApplepayMinicart.phtml';

    /**
     * @var bool
     */
    private $isMiniCart = false;
    
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
     * @param bool $isCatalog
     * @return $this
     */
    public function setIsInCatalogProduct($isCatalog)
    {
        $this->isMiniCart = !$isCatalog;
        return $this;
    }

    /**
     * @param bool $isShoppingCart
     * @return none
     */
    public function setIsShoppingCart($isShoppingCart)
    {
        $this->isShoppingCart = $isShoppingCart;

        if ($isShoppingCart) {
            $this->_template = 'Novalnet_Payment::checkout/ApplepayCartPage.phtml';
        } else {
            $this->_template = 'Novalnet_Payment::checkout/ApplepayMinicart.phtml';
        }
    }

    /**
     * Is Should Rendered
     *
     * @param none
     * @return bool
     */
    protected function shouldRender()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $session = $objectManager->create(\Magento\Checkout\Model\Session::class);

        if ($this->getIsCart()) {
            return true;
        }

        return $this->isMiniCart;
    }

    /**
     * Render the block if needed
     *
     * @param none
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

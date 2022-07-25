<?php

namespace Novalnet\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class ApplePayButtons implements ObserverInterface
{
    /**
     * Render Apple Pay Buttons In Cart & Minicart
     *
     * @param  \Magento\Framework\Event\Observer $observer
     * @return none
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Block\ShortcutButtons $shortcutButtons */
        $shortcutButtons = $observer->getEvent()->getContainer();

        /** @var \Magento\Framework\View\Element\Template $shortcut */
        $shortcut = $shortcutButtons->getLayout()->createBlock(
            \Novalnet\Payment\Block\Checkout\Cart\Shortcut::class,
            '',
            []
        );

        $shortcut->setIsInCatalogProduct(
            $observer->getEvent()->getIsCatalogProduct()
        )->setShowOrPosition(
            $observer->getEvent()->getOrPosition()
        );

        $shortcut->setIsShoppingCart($observer->getEvent()->getIsShoppingCart());

        $shortcutButtonClass = (!empty($shortcutButtons)) ? get_class($shortcutButtons) : '';

        $shortcut->setIsCart($shortcutButtonClass == \Magento\Checkout\Block\QuoteShortcutButtons::class);

        $shortcutButtons->addShortcut($shortcut);
    }
}

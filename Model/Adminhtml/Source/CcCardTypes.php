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
 * Source model for Credit card types options
 */
class CcCardTypes implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter (Credit Card types)
     *
     * @param  none
     * @return array $options
     */
    public function toOptionArray()
    {
        $cardTypes = ['VI' => 'Visa', 'MC' => 'MasterCard', 'AE' => 'American Express',
            'MA' => 'Maestro', 'CI' => 'Cartasi', 'UP' => 'Union Pay',
            'DC' => 'Discover', 'DI' => 'Diners', 'JCB' => 'Jcb', 'CB' => 'Carte Bleue'];
        $options = [];

        foreach ($cardTypes as $code => $name) {
            $options[] = ['value' => $code, 'label' => $name];
        }

        return $options;
    }
}

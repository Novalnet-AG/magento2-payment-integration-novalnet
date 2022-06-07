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

class InstalmentCycles implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter (Total period for recurring transaction)
     *
     * @param  none
     * @return array
     */
    public function toOptionArray()
    {
        $methods = [];
        $allCycles =  [2 => 2 . __(' cycles'),
                       3 => 3 . __(' cycles'),
                       4 => 4 . __(' cycles'),
                       6 => 6 . __(' cycles'),
                       8 => 8 . __(' cycles'),
                       9 => 9 . __(' cycles'),
                       10 => 10 . __(' cycles'),
                       12 => 12 . __(' cycles'),
                       15 => 15 . __(' cycles'),
                       18 => 18 . __(' cycles'),
                       24 => 24 . __(' cycles')
        ];

        foreach ($allCycles as $key => $value) {
            $methods[$key] = ['value' => $key, 'label' => $value];
        }

        return $methods;
    }
}

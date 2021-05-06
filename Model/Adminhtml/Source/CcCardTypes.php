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
        return [
            [
                'value' => 'VI',
                'label' => __('Visa')
            ],
            [
                'value' => 'MC',
                'label' => __('MasterCard')
            ],
            [
                'value' => 'AE',
                'label' => __('American Express')
            ],
            [
                'value' => 'MA',
                'label' => __('Maestro')
            ],
            [
                'value' => 'CI',
                'label' => __('Cartasi')
            ],
            [
                'value' => 'UP',
                'label' => __('Union Pay')
            ],
            [
                'value' => 'DC',
                'label' => __('Discover')
            ],
            [
                'value' => 'DI',
                'label' => __('Diners')
            ],
            [
                'value' => 'JCB',
                'label' => __('Jcb')
            ],
            [
                'value' => 'CB',
                'label' => __('Carte Bleue')
            ],
        ];
    }
}

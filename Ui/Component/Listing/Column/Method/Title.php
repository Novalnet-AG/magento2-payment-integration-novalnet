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
namespace Novalnet\Payment\Ui\Component\Listing\Column\Method;

use Novalnet\Payment\Model\Ui\ConfigProvider;

class Title extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * Map Novalnet v2 payment method title to v3
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                if (!empty($item[$name]) && $item[$name] != ConfigProvider::NOVALNET_PAY && preg_match('/novalnet/i', $item[$name])) {
                    $item[$name] = ConfigProvider::NOVALNET_PAY;
                }
            }
        }

        return $dataSource;
    }
}

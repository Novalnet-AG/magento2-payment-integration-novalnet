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
namespace Novalnet\Payment\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;

class RemoveAccessKey implements DataPatchInterface
{
    /**
     * @var \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config\Data
     */
    private $configResource;

    /**
     * @var \Magento\Framework\App\Config\ConfigResource\ConfigInterface
     */
    private $resourceConfig;

    /**
     * @var \Novalnet\Payment\Logger\NovalnetLogger
     */
    private $novalnetLogger;

    /**
     * @param \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $collectionFactory
     * @param \Magento\Config\Model\ResourceModel\Config\Data $configResource
     * @param \Magento\Framework\App\Config\ConfigResource\ConfigInterface $resourceConfig
     * @param \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
     */
    public function __construct(
        \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $collectionFactory,
        \Magento\Config\Model\ResourceModel\Config\Data $configResource,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface  $resourceConfig,
        \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->configResource = $configResource;
        $this->resourceConfig = $resourceConfig;
        $this->novalnetLogger = $novalnetLogger;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $novalnetGlobalConfig = [
            'novalnet_global/novalnet/payment_access_key',
            'novalnet_global/novalnet/password',
            'novalnet_global/novalnet/public_key',
            'novalnet_global/novalnet/signature',
            'payment/novalnet/payment_access_key',
            'payment/novalnet/signature'
        ];

        $this->resourceConfig->saveConfig(
            'payment/novalnet/restore_cart',
            1,
            'default',
            0
        );

        $this->resourceConfig->saveConfig(
            'payment/merchant_script/test_mode',
            0,
            'default',
            0
        );

        $novalnetConfigurations = $this->collectionFactory->create()->addFieldToFilter('path', [['like' => '%novalnet_global%']]);

        foreach ($novalnetConfigurations as $config) {
            if (in_array($config->getPath(), $novalnetGlobalConfig)) {
                $this->configResource->delete($config);
                $this->novalnetLogger->notice('Removed Novalnet global configuration. The config path: ' . $config->getPath());
            }
        }
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}

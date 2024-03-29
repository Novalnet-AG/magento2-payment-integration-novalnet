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

class CustomerGroups implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\Collection
     */
    private $customerGroupColl;

    /**
     * @param \Magento\Customer\Model\ResourceModel\Group\Collection $customerGroupColl
     */
    public function __construct(\Magento\Customer\Model\ResourceModel\Group\Collection $customerGroupColl)
    {
        $this->customerGroupColl = $customerGroupColl;
    }

    /**
     * Options getter (Customer Groups)
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->customerGroupColl->toOptionArray();
    }
}

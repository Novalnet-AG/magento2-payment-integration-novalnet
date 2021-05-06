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
namespace Novalnet\Payment\Block\Info;

class InvoiceInstalment extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_template = 'Novalnet_Payment::info/InvoiceInstalment.phtml';

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $priceHelper;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $novalnetRequestHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    protected $serializer;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->priceHelper = $priceHelper;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->serializer = $serializer;
    }

    /**
     * Set template forPdf
     *
     * @param  none
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('Novalnet_Payment::pdf/InvoiceInstalment.phtml');
        return $this->toHtml();
    }

    /**
     * Get additional information for the payment
     *
     * @param  string $key
     * @return string|null
     */
    public function getAdditionalData($key)
    {
        $details = $this->novalnetRequestHelper->isSerialized($this->getInfo()->getAdditionalData())
                ? $this->serializer->unserialize($this->getInfo()->getAdditionalData())
                : json_decode($this->getInfo()->getAdditionalData(), true);
        if (is_array($details)) {
            return (!empty($key) && isset($details[$key])) ? $details[$key] : '';
        }
    }

    /**
     * Get amount with currency
     *
     * @param int $amount
     * @return null|string
     */
    public function updateCurrency($amount)
    {
        return $this->priceHelper->currency($amount, true, false);
    }

    /**
     * Get novalnet helper
     *
     * @param  null
     * @return DataObject
     */
    public function novalnetHelper()
    {
        return $this->novalnetRequestHelper;
    }

    /**
     * Get GrandTotal
     *
     * @param  null
     * @return int
     */
    public function getGrandTotal()
    {
        return $this->novalnetRequestHelper->getAmountWithSymbol($this->getInfo()->getOrder()->getGrandTotal(), $this->getInfo()->getOrder()->getStoreId());
    }
}

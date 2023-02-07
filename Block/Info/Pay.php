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

class Pay extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_template = 'Novalnet_Payment::info/Pay.phtml';

    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    protected $novalnetHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    protected $serializer;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $priceHelper;

    /**
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Novalnet\Payment\Helper\Data $novalnetHelper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->novalnetHelper = $novalnetHelper;
        $this->priceHelper = $priceHelper;
        $this->serializer = $serializer;
        parent::__construct($context, $data);
    }

    /**
     * Set template for Pdf
     *
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('Novalnet_Payment::pdf/Pay.phtml');
        return $this->toHtml();
    }

    /**
     * Get additional data for the payment
     *
     * @param  string $key
     * @return string|null
     */
    public function getAdditionalData($key)
    {
        $details = ($this->getInfo()->getAdditionalData()) ? ($this->novalnetHelper->isSerialized($this->getInfo()->getAdditionalData())
                ? $this->serializer->unserialize($this->getInfo()->getAdditionalData())
                : json_decode($this->getInfo()->getAdditionalData(), true)) : [];

        if (is_array($details)) {
            return (!empty($key) && isset($details[$key])) ? $details[$key] : '';
        }
    }

    /**
     * Get additional information for the payment
     *
     * @param  string $key
     * @return string|null
     */
    public function getAdditionalInfo($key)
    {
        if ($this->getInfo()->hasAdditionalInformation($key)) {
            return $this->getInfo()->getAdditionalInformation($key);
        }

        return '';
    }

    /**
     * Get novalnet helper
     *
     * @return \Novalnet\Payment\Helper\Data
     */
    public function novalnetHelper()
    {
        return $this->novalnetHelper;
    }

    /**
     * Get amount with currency
     *
     * @param int $amount
     * @return float|string
     */
    public function updateCurrency($amount)
    {
        return $this->priceHelper->currency($amount, true, false);
    }
}

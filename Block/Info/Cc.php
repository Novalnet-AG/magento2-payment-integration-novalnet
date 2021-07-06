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

class Cc extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_template = 'Novalnet_Payment::info/Cc.phtml';

    /**
     * @var \Novalnet\Payment\Helper\Request
     */
    protected $novalnetRequestHelper;

    /**
     * @var Magento\Framework\Serialize\Serializer\Serialize
     */
    protected $serializer;

    /**
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param Magento\Framework\Serialize\Serializer\Serialize $serializer
     */
    public function __construct(
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->serializer = $serializer;
        parent::__construct($context, $data);
    }

    /**
     * Set template for Pdf
     *
     * @param  none
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('Novalnet_Payment::pdf/Cc.phtml');
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
     * Get novalnet helper
     *
     * @param  null
     * @return DataObject
     */
    public function novalnetHelper()
    {
        return $this->novalnetRequestHelper;
    }
}

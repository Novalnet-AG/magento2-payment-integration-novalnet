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
namespace Novalnet\Payment\Block\Form;

class Pay extends \Magento\Payment\Block\Form
{
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $backendQuote;

    /**
     * @var \Novalnet\Payment\Model\NovalnetRepository
     */
    protected $novalnetRepository;

    /**
     * @var \Magento\Framework\View\Element\Template\Context
     */
    protected $context;

    /**
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Backend\Model\Session\Quote $backendQuote
     * @param \Novalnet\Payment\Model\NovalnetRepository $novalnetRepository
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Backend\Model\Session\Quote $backendQuote,
        \Novalnet\Payment\Model\NovalnetRepository $novalnetRepository,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->backendQuote = $backendQuote;
        $this->novalnetRepository = $novalnetRepository;
        $this->setTemplate('Novalnet_Payment::form/Pay.phtml');
        parent::__construct($context, $data);
    }

    /**
     * Returns Iframe source link
     *
     * @return string
     */
    public function getIframeSrc()
    {
        $response = $this->novalnetRepository->buildPayBylinkRequest($this->backendQuote->getQuote()->getId(), true);
        $response = (!empty($response)) ? $this->jsonHelper->jsonDecode($response) : [];
        if (!empty($response['result']['redirect_url'])) {
            return $response['result']['redirect_url'];
        }

        return '';
    }
}

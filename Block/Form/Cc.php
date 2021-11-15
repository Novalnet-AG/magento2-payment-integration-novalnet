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

class Cc extends \Magento\Payment\Block\Form
{
    /**
     * @var \Novalnet\Payment\Model\NNConfig
     */
    protected $novalnetConfig;

    /**
     * @var \Novalnet\Payment\Model\Ui\ConfigProvider
     */
    protected $novalnetConfigProvider;

    /**
     * @var \Novalnet\Payment\Helper\Request
     */
    protected $novalnetRequestHelper;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelperRequest;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Novalnet\Payment\Model\NNConfig $novalnetConfig
     * @param \Novalnet\Payment\Model\Ui\ConfigProvider $novalnetConfigProvider
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelperRequest
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Novalnet\Payment\Model\NNConfig $novalnetConfig,
        \Novalnet\Payment\Model\Ui\ConfigProvider $novalnetConfigProvider,
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelperRequest,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setTemplate('Novalnet_Payment::form/Cc.phtml'); // Sets form template
        $this->novalnetConfig = $novalnetConfig;
        $this->novalnetConfigProvider = $novalnetConfigProvider;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->jsonHelperRequest = $jsonHelperRequest;
    }

    /**
     * Get payment logo enabled status
     *
     * @param  string $paymentMethodcode
     * @return string
     */
    public function getPaymentLogo($paymentMethodcode)
    {
        return $this->novalnetConfigProvider->getPaymentLogo($paymentMethodcode);
    }

    /**
     * Get Credit Card logos
     *
     * @param  string $paymentMethodcode
     * @return string
     */
    public function getCreditCardLogos($paymentMethodcode)
    {
        return $this->novalnetConfigProvider->getCcAvailableTypes($paymentMethodcode);
    }

    /**
     * Verify whether the payment is in Test mode
     *
     * @param  string $paymentMethodcode
     * @return int
     */
    public function getTestMode($paymentMethodcode)
    {
        return $this->novalnetConfig->getTestMode($paymentMethodcode);
    }

    /**
     * Get instructions text from config
     *
     * @param  none
     * @return string|null
     */
    public function isInlineForm()
    {
        return $this->getMethod()->getConfigData('inline_form');
    }

    /**
     * Retrieve Credit Card iframe params
     *
     * @param  none
     * @return boolean
     */
    public function getCcIframeParams()
    {
        return $this->novalnetConfigProvider->getCcIframeParams();
    }

    /**
     * Get form style configuration
     *
     * @param string $param
     * @return string|null
     */
    public function getStyleConfig($param)
    {
        $creditCardStyle = $this->novalnetConfigProvider->getCcStyleConfig();
        if (isset($creditCardStyle[$param])) {
            return $creditCardStyle[$param];
        }

        return '';
    }

    /**
     * Retrieve Credit Card configs
     *
     * @param  string $paymentMethodcode
     * @return array
     */
    public function getConfig($paymentMethodcode)
    {
        $config = $this->novalnetConfigProvider->getConfig();
        return $config['payment'][$paymentMethodcode];
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
     * Get json helper request
     *
     * @param  null
     * @return DataObject
     */
    public function jsonHelper()
    {
        return $this->jsonHelperRequest;
    }
}

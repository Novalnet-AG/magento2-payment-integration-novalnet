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
namespace Novalnet\Payment\Block\Adminhtml\Sales\Order\View\Tab;

use Novalnet\Payment\Model\Ui\ConfigProvider;

class Instalment extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'Novalnet_Payment::sales/order/view/tab/instalment.phtml';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $pricingHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $datetime;

    /**
     * @var \Novalnet\Payment\Helper\Request
     */
    protected $novalnetRequestHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    protected $serializer;

    /**
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $datetime
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $datetime,
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->pricingHelper = $pricingHelper;
        $this->datetime = $datetime;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->serializer = $serializer;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Instalment');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Instalment');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        $payment = $this->getOrder()->getPayment();
        if (in_array(
            $payment->getMethodInstance()->getCode(),
            [
                ConfigProvider::NOVALNET_SEPA_INSTALMENT,
                ConfigProvider::NOVALNET_INVOICE_INSTALMENT
            ]
        )) {
            $additionalData = $this->novalnetRequestHelper->isSerialized($payment->getAdditionalData())
                ? $this->serializer->unserialize($payment->getAdditionalData())
                : json_decode($payment->getAdditionalData(), true);
            $paymentStatus = !empty($additionalData['NnStatus'])
                ? $this->novalnetRequestHelper->getStatus($additionalData['NnStatus'], $this->getOrder()) : '';
            return (bool) ($paymentStatus == 'CONFIRMED');
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Get Tab Class
     *
     * @return string
     */
    public function getTabClass()
    {
        return 'ajax only';
    }

    /**
     * Get Class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->getTabClass();
    }

    /**
     * Get Tab Url
     *
     * @return string
     */
    public function getTabUrl()
    {
        return $this->getUrl('novalnetpayment/sales/instalment', ['_current' => true]);
    }

    /**
     * Get URL to edit the customer.
     *
     * @return string
     */
    public function getCustomerViewUrl()
    {
        if ($this->getOrder()->getCustomerIsGuest() || !$this->getOrder()->getCustomerId()) {
            return '';
        }

        return $this->getUrl('customer/index/edit', ['id' => $this->getOrder()->getCustomerId()]);
    }

    /**
     * Check if is single store mode
     *
     * @return bool
     */
    public function isSingleStoreMode()
    {
        return $this->_storeManager->isSingleStoreMode();
    }

    /**
     * Get order store name
     *
     * @return null|string
     */
    public function getOrderStoreName()
    {
        if ($this->getOrder()) {
            $storeId = $this->getOrder()->getStoreId();
            if ($storeId === null) {
                $deleted = __(' [deleted]');
                return nl2br($this->getOrder()->getStoreName()) . $deleted;
            }
            $store = $this->_storeManager->getStore($storeId);
            $name = [$store->getWebsite()->getName(), $store->getGroup()->getName(), $store->getName()];
            return implode('<br>', $name);
        }

        return null;
    }

    /**
     * Get payment additional data
     *
     * @param string $key
     * @return null|string
     */
    public function getAdditionalData($key)
    {
        $payment = $this->getOrder()->getPayment();
        $details = $this->novalnetRequestHelper->isSerialized($payment->getAdditionalData())
                ? $this->serializer->unserialize($payment->getAdditionalData())
                : json_decode($payment->getAdditionalData(), true);
        if (is_array($details)) {
            return (!empty($key) && isset($details[$key])) ? $details[$key] : '';
        }
    }

    /**
     * get Formated Date
     *
     * @param string $date
     * @return string
     */
    public function getFormatedDate($date)
    {
        return $this->datetime->formatDate($date, \IntlDateFormatter::LONG);
    }

    /**
     * update Currency
     *
     * @param string $amount
     * @return string
     */
    public function updateCurrency($amount)
    {
        return $this->pricingHelper->currency($amount);
    }
}

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

class ZeroAmountBooking extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'Novalnet_Payment::sales/order/view/tab/zeroamountbooking.phtml';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    protected $novalnetHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    protected $serializer;

    /**
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Template\Context $context,
        \Novalnet\Payment\Helper\Data $novalnetHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->novalnetHelper = $novalnetHelper;
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
     * @inheritdoc
     */
    public function getTabLabel()
    {
        return __('Zero amount booking');
    }

    /**
     * @inheritdoc
     */
    public function getTabTitle()
    {
        return __('Zero amount booking');
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        $payment = $this->getOrder()->getPayment();
        $paymentMethodCode = $payment->getMethodInstance()->getCode();
        if (!empty($paymentMethodCode) && preg_match('/novalnet/', $paymentMethodCode)) {
            $additionalData = [];
            if (!empty($payment->getAdditionalData())) {
                $additionalData = $this->novalnetHelper->isSerialized($payment->getAdditionalData())
                    ? $this->serializer->unserialize($payment->getAdditionalData())
                    : json_decode($payment->getAdditionalData(), true);
            }

            $paymentType = !empty($additionalData['NnPaymentType']) ? $additionalData['NnPaymentType'] : '';
            if (in_array($paymentType, ['DIRECT_DEBIT_SEPA', 'CREDITCARD'])) {
                $paymentStatus = !empty($additionalData['NnStatus'])
                    ? $this->novalnetHelper->getStatus($additionalData['NnStatus'], $this->getOrder(), $paymentType) : '';
                return (bool) ($paymentStatus == 'CONFIRMED' && !empty($additionalData['NnZeroAmountBooking']) && empty($additionalData['NnZeroAmountDone']));
            }
        }

        return false;
    }

    /**
     * @inheritdoc
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
        return $this->getUrl('novalnetpayment/sales/zeroamountbookingTab', ['_current' => true]);
    }

    /**
     * Get payment additional data
     *
     * @param string $key
     *
     * @return null|string
     */
    public function getAdditionalData($key)
    {
        $payment = $this->getOrder()->getPayment();
        $details = [];
        if (!empty($payment->getAdditionalData())) {
            $details = $this->novalnetHelper->isSerialized($payment->getAdditionalData())
                    ? $this->serializer->unserialize($payment->getAdditionalData())
                    : json_decode($payment->getAdditionalData(), true);
        }

        if (is_array($details)) {
            return (!empty($key) && isset($details[$key])) ? $details[$key] : '';
        }

        return null;
    }

    /**
     * Get the formated amount in cents/euro
     *
     * @param  float  $amount
     * @param  string $type
     * @return int
     */
    public function getFormattedAmount($amount, $type = 'CENT')
    {
        return $this->novalnetHelper->getFormattedAmount($amount, $type);
    }
}

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
namespace Novalnet\Payment\Block\Adminhtml\Button;

use Magento\Backend\Block\Widget\Button\Toolbar as ToolbarContext;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Backend\Block\Widget\Button\ButtonList;

class Control extends \Magento\Sales\Block\Adminhtml\Order\View
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Novalnet\Payment\Model\NNConfig
     */
    protected $novalnetConfig;

    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    protected $novalnetHelper;

    /**
     * @param \Magento\Framework\Registry $registry
     * @param \Novalnet\Payment\Model\NNConfig $novalnetConfig
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Novalnet\Payment\Model\NNConfig $novalnetConfig,
        \Novalnet\Payment\Helper\Data $novalnetHelper
    ) {
        $this->coreRegistry = $registry;
        $this->novalnetConfig = $novalnetConfig;
        $this->novalnetHelper = $novalnetHelper;
    }

    /**
     * Before push buttons
     *
     * @param ToolbarContext $toolbar
     * @param AbstractBlock $context
     * @param ButtonList $buttonList
     * @return mixed
     */
    public function beforePushButtons(
        ToolbarContext $toolbar,
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
    ) {
        if (!$context instanceof \Magento\Sales\Block\Adminhtml\Order\View) {
            return [$context, $buttonList];
        }

        $order = $this->getOrder();
        $payment = $order->getPayment();
        $paymentMethodCode = $payment->getMethodInstance()->getCode();
        $additionalData = $this->novalnetHelper->getPaymentAdditionalData($payment->getAdditionalData());
        $paymentType = !empty($additionalData['NnPaymentType']) ? $additionalData['NnPaymentType'] : '';

        if (!empty($paymentMethodCode) && preg_match('/novalnet/i', $paymentMethodCode)) {
            if ($context instanceof \Magento\Sales\Block\Adminhtml\Order\Invoice\View) {
                if ($paymentType == "INVOICE") {
                    $buttonList->remove('capture');
                }
                return [$context, $buttonList];
            }

            $transactionStatus = !empty($additionalData['NnStatus'])
                ? $this->novalnetHelper->getStatus($additionalData['NnStatus'], $order, $paymentType) : '';

            if ($transactionStatus) {
                // remove Capture button
                $buttonList->update('order_invoice', 'label', __('Capture'));

                if (in_array($transactionStatus, ['PENDING', 'DEACTIVATED']) || !empty($additionalData['NnZeroAmountBooking'])) {
                    $buttonList->remove('order_invoice');
                }

                if ($order->canInvoice() && $transactionStatus == 'ON_HOLD' &&
                    $paymentType == "INVOICE"
                ) {
                    $buttonList->remove('order_invoice');
                    $message = __('Are you sure you want to capture the payment?');
                    $capturePaymentUrl = $context->getUrl(
                        'novalnetpayment/sales/ordercapture',
                        ['order_id' => $order->getId()]
                    );

                    $buttonList->add(
                        'novalnet_confirm',
                        [
                            'label' => __('Novalnet Capture'),
                            'onclick' => "confirmSetLocation('{$message}', '{$capturePaymentUrl}')"
                        ]
                    );
                }

                if ($transactionStatus == 'ON_HOLD') {
                    $buttonList->remove('void_payment');
                    $message = __('Are you sure you want to cancel the payment?');
                    $voidPaymentUrl = $context->getUrl(
                        'sales/*/voidPayment',
                        ['order_id' => $order->getId()]
                    );
                    $buttonList->add(
                        'void_payment',
                        [
                            'label' => __('Void'),
                            'onclick' => "confirmSetLocation('{$message}', '{$voidPaymentUrl}')"
                        ]
                    );
                }

                $processMode = !empty($additionalData['NnPaymentProcessMode']) ? $additionalData['NnPaymentProcessMode'] : '';

                if ($transactionStatus == 'PENDING' && ($processMode == 'redirect' && !in_array($paymentType, ['GOOGLEPAY', 'APPLEPAY', 'CREDITCARD'])) ||
                    in_array(
                        $paymentType,
                        [
                            'PREPAYMENT',
                            'CASHPAYMENT',
                            'MULTIBANCO'
                        ]
                    )
                ) {
                    $buttonList->remove('order_cancel'); // remove Cancel button
                    $buttonList->remove('void_payment'); // remove Void button
                }
            }
        }

        return [$context, $buttonList];
    }

    /**
     * Retrieve order model object
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('sales_order');
    }
}

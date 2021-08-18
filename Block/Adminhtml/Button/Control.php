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
use Novalnet\Payment\Model\Ui\ConfigProvider;

class Control extends \Magento\Sales\Block\Adminhtml\Order\View
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Novalnet\Payment\Model\NNConfig
     */
    protected $novalnetConfig;

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
     * @param \Novalnet\Payment\Model\NNConfig $novalnetConfig
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Novalnet\Payment\Model\NNConfig $novalnetConfig,
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer
    ) {
        $this->coreRegistry = $registry;
        $this->novalnetConfig = $novalnetConfig;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->serializer = $serializer;
    }

    /**
     * @param ToolbarContext $toolbar
     * @param AbstractBlock $context
     * @param ButtonList $buttonList
     * @return array
     */
    public function beforePushButtons(
        ToolbarContext $toolbar,
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
    ) {
        if ($context instanceof \Magento\Sales\Block\Adminhtml\Order\Invoice\View) {
            $orderPayment = $context->getInvoice()->getOrder()->getPayment();
            if ($orderPayment->getMethodInstance()->getCode() == ConfigProvider::NOVALNET_INVOICE) {
                $buttonList->remove('capture');
            }
            return [$context, $buttonList];
        }

        if (!$context instanceof \Magento\Sales\Block\Adminhtml\Order\View) {
            return [$context, $buttonList];
        }

        $order = $this->getOrder();
        $payment = $order->getPayment();
        $paymentMethodCode = $payment->getMethodInstance()->getCode();
        if (preg_match('/novalnet/i', $paymentMethodCode)) {
            $additionalData = $this->novalnetRequestHelper->isSerialized($payment->getAdditionalData())
                ? $this->serializer->unserialize($payment->getAdditionalData())
                : json_decode($payment->getAdditionalData(), true);
            $transactionStatus = !empty($additionalData['NnStatus'])
                ? $this->novalnetRequestHelper->getStatus($additionalData['NnStatus'], $order) : '';
            if ($transactionStatus) {
                // remove Capture button
                $buttonList->update('order_invoice', 'label', __('Capture'));
                if (in_array($transactionStatus, ['PENDING', 'DEACTIVATED'])) {
                    $buttonList->remove('order_invoice');
                }

                if ($order->canInvoice() && $transactionStatus == 'ON_HOLD' &&
                    $paymentMethodCode == ConfigProvider::NOVALNET_INVOICE
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

                if ($transactionStatus == 'PENDING' && $this->novalnetConfig->isRedirectPayment($paymentMethodCode) ||
                    in_array(
                        $paymentMethodCode,
                        [
                            ConfigProvider::NOVALNET_PREPAYMENT,
                            ConfigProvider::NOVALNET_CASHPAYMENT,
                            ConfigProvider::NOVALNET_MULTIBANCO
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
     * @param  none
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('sales_order');
    }
}

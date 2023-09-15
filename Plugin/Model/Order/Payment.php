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
namespace Novalnet\Payment\Plugin\Model\Order;

use Novalnet\Payment\Model\Ui\ConfigProvider;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class Payment
{
    /**
     * @var \Novalnet\Payment\Model\NNConfig
     */
    protected $novalnetConfig;

    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    protected $novalnetHelper;

    /**
     * @param \Novalnet\Payment\Model\NNConfig $novalnetConfig
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     */
    public function __construct(
        \Novalnet\Payment\Model\NNConfig $novalnetConfig,
        \Novalnet\Payment\Helper\Data $novalnetHelper
    ) {
        $this->novalnetConfig = $novalnetConfig;
        $this->novalnetHelper = $novalnetHelper;
    }

    /**
     * Map the Novalnet v2 Payment orders to v3 Payment code
     *
     * @param \Magento\Sales\Model\Order\Payment $subject
     * @return string
     */
    public function afterGetMethod(\Magento\Sales\Model\Order\Payment $subject)
    {
        $paymentMethodCode = $subject->getData(OrderPaymentInterface::METHOD);

        if (!empty($paymentMethodCode) && preg_match('/novalnet/i', $paymentMethodCode) && $paymentMethodCode !== ConfigProvider::NOVALNET_PAY) {
            $additionalData = $this->novalnetHelper->getPaymentAdditionalData($subject->getAdditionalData());
            $needUpdate = false;

            if (empty($additionalData['NnPaymentTitle'])) {
                $additionalData['NnPaymentTitle'] = $this->novalnetConfig->getPaymentTitleByCode($paymentMethodCode);
                $needUpdate = true;
            }

            if (empty($additionalData['NnPaymentType'])) {
                $additionalData['NnPaymentType'] = $this->novalnetConfig->getPaymentTypeByCode($paymentMethodCode);
                $needUpdate = true;
            }

            if (empty($additionalData['NnPaymentProcessMode'])) {
                $processMode = ($this->novalnetConfig->isRedirectPayment($paymentMethodCode)) ? 'redirect' : 'direct';
                $additionalData['NnPaymentProcessMode'] = $processMode;
                $needUpdate = true;
            }

            if ($needUpdate) {
                $subject->setAdditionalData($this->novalnetHelper->jsonEncode($additionalData))->save();
            }

            $paymentMethodCode = ConfigProvider::NOVALNET_PAY;
        }

        return $paymentMethodCode;
    }
}

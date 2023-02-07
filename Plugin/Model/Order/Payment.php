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
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    protected $serializer;

    /**
     * @param \Novalnet\Payment\Model\NNConfig $novalnetConfig
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     */
    public function __construct(
        \Novalnet\Payment\Model\NNConfig $novalnetConfig,
        \Novalnet\Payment\Helper\Data $novalnetHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer
    ) {
        $this->novalnetConfig = $novalnetConfig;
        $this->novalnetHelper = $novalnetHelper;
        $this->jsonHelper = $jsonHelper;
        $this->serializer = $serializer;
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
            $additionalData = [];
            $canUpdate = false;

            if (!empty($subject->getAdditionalData())) {
                $additionalData = $this->novalnetHelper->isSerialized($subject->getAdditionalData())
                    ? $this->serializer->unserialize($subject->getAdditionalData())
                    : json_decode($subject->getAdditionalData(), true);
            }

            if (empty($additionalData['NnPaymentTitle'])) {
                $additionalData['NnPaymentTitle'] = $this->novalnetConfig->getPaymentTitleByCode($paymentMethodCode);
                $canUpdate = true;
            }

            if (empty($additionalData['NnPaymentType'])) {
                $additionalData['NnPaymentType'] = $this->novalnetConfig->getPaymentTypeByCode($paymentMethodCode);
                $canUpdate = true;
            }

            if (empty($additionalData['NnPaymentProcessMode'])) {
                $processMode = ($this->novalnetConfig->isRedirectPayment($paymentMethodCode)) ? 'redirect' : 'direct';
                $additionalData['NnPaymentProcessMode'] = $processMode;
                $canUpdate = true;
            }

            if ($canUpdate) {
                $subject->setAdditionalData($this->jsonHelper->jsonEncode($additionalData))->save();
            }

            $paymentMethodCode = ConfigProvider::NOVALNET_PAY;
        }

        return $paymentMethodCode;
    }
}

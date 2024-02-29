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
namespace Novalnet\Payment\Gateway\Config;

use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Novalnet\Payment\Model\Ui\ConfigProvider;

class CanInitializeHandler implements ValueHandlerInterface
{
    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    private $novalnetHelper;

    /**
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     */
    public function __construct(
        \Novalnet\Payment\Helper\Data $novalnetHelper
    ) {
        $this->novalnetHelper = $novalnetHelper;
    }

    /**
     * Initialize Novalnet Credit Card payment only if do_redirect is 1
     *
     * @param array $subject
     * @param int|null $storeId
     * @return bool
     */
    public function handle(array $subject, $storeId = null)
    {
        $paymentDataObject = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($subject);
        $paymentMethodCode = $paymentDataObject->getPayment()->getMethodInstance()->getCode();
        if ($paymentMethodCode == ConfigProvider::NOVALNET_PAY) {
            $methodSession = $this->novalnetHelper->getMethodSession(ConfigProvider::NOVALNET_PAY);
            $paymentType = $methodSession->getData(ConfigProvider::NOVALNET_PAY . '_type');
            if ($methodSession->getData(ConfigProvider::NOVALNET_PAY . '_do_redirect') == '1' ||
                $methodSession->getData(ConfigProvider::NOVALNET_PAY . '_process_mode') == 'redirect' ||
                in_array($paymentType, ['GUARANTEED_DIRECT_DEBIT_SEPA', 'GUARANTEED_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA', 'INSTALMENT_INVOICE'])
            ) {
                return true;
            }
        }

        return false;
    }
}

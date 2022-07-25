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

class OrderPlaceRedirectUrlHandler implements ValueHandlerInterface
{
    /**
     * @var \Novalnet\Payment\Model\NNConfig
     */
    private $novalnetConfig;
    
    /**
     * @var \Novalnet\Payment\Helper\Request
     */
    private $novalnetRequestHelper;

    /**
     * @param \Novalnet\Payment\Model\NNConfig $novalnetConfig
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper
     */
    public function __construct(
        \Novalnet\Payment\Model\NNConfig $novalnetConfig,
        \Novalnet\Payment\Helper\Request $novalnetRequestHelper
    ) {
        $this->novalnetConfig = $novalnetConfig;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
    }

    /**
     * Initialize Mail for Credit Card payment only if do_rediret is 1
     *
     * @param array $subject
     * @param int|null $storeId
     * @return bool
     */
    public function handle(array $subject, $storeId = null)
    {
        $paymentDataObject = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($subject);
        $paymentMethodCode = $paymentDataObject->getPayment()->getMethodInstance()->getCode();
        if ($paymentMethodCode == ConfigProvider::NOVALNET_CC) {
            $methodSession = $this->novalnetRequestHelper->getMethodSession(ConfigProvider::NOVALNET_CC);
            if ($methodSession->getData(ConfigProvider::NOVALNET_CC . '_do_redirect') != 0) {
                return true;
            } else {
                return false;
            }
        }

        return true;
    }
}

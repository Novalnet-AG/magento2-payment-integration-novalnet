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
namespace Novalnet\Payment\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Framework\HTTP\Client\Curl;
use Novalnet\Payment\Model\NNConfig;

class TransactionInitialize implements ClientInterface
{
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private $clientFactory;

    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    private $novalnetHelper;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $coreSession;

    /**
     * @param Curl $clientFactory
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     */
    public function __construct(
        Curl $clientFactory,
        \Novalnet\Payment\Helper\Data $novalnetHelper,
        \Magento\Framework\Session\SessionManagerInterface $coreSession
    ) {
        $this->clientFactory = $clientFactory;
        $this->novalnetHelper = $novalnetHelper;
        $this->coreSession = $coreSession;
    }

    /**
     * Send request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $requestData = $transferObject->getBody();
        $storeId = $requestData['storeId'];
        $paymentAction = !empty($requestData['transaction']['paymentAction']) ? $requestData['transaction']['paymentAction'] : null;
        unset($requestData['storeId']);
        if (isset($requestData['transaction']['paymentAction'])) {
            unset($requestData['transaction']['paymentAction']);
        }
        $this->clientFactory->setHeaders($this->novalnetHelper->getRequestHeaders(false, $storeId));

        if ($paymentAction == NNConfig::ACTION_AUTHORIZE || $paymentAction == NNConfig::ACTION_ZERO_AMOUNT) {
            $this->clientFactory->post(NNConfig::NOVALNET_AUTHORIZE_URL, $this->novalnetHelper->jsonEncode($requestData));
        } else {
            $this->clientFactory->post(NNConfig::NOVALNET_PAYMENT_URL, $this->novalnetHelper->jsonEncode($requestData));
        }

        $response = (!empty($this->clientFactory->getBody())) ? $this->novalnetHelper->jsonDecode($this->clientFactory->getBody()) : [];

        if (!empty($response['result']['status']) && $response['result']['status'] == 'SUCCESS') {
            return $response;
        } elseif (!empty($response['result']['status_text'])) {
            throw new \Magento\Framework\Exception\LocalizedException(__($response['result']['status_text']));
        }
    }
}

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
use Novalnet\Payment\Helper\Request;
use Novalnet\Payment\Model\NNConfig;
use Novalnet\Payment\Model\Ui\ConfigProvider;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Serialize\Serializer\Serialize;

class TransactionAuthorize implements ClientInterface
{
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private $clientFactory;

    /**
     * @var \Novalnet\Payment\Model\NNConfig
     */
    private $novalnetConfig;

    /**
     * @var \Novalnet\Payment\Helper\Request
     */
    private $novalnetRequestHelper;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    private $serializer;

    /**
     * @param Curl $clientFactory
     * @param NNConfig $novalnetConfig
     * @param Request $novalnetRequestHelper
     * @param Data $jsonHelper
     * @param Data $serializer
     */
    public function __construct(
        Curl $clientFactory,
        NNConfig $novalnetConfig,
        Request $novalnetRequestHelper,
        Data $jsonHelper,
        Serialize $serializer
    ) {
        $this->clientFactory = $clientFactory;
        $this->novalnetConfig = $novalnetConfig;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->jsonHelper = $jsonHelper;
        $this->serializer = $serializer;
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
        if (!empty($requestData['action'])) {
            return $requestData;
        }

        $storeId = $requestData['storeId'];
        unset($requestData['storeId']);
        $this->clientFactory->setHeaders($this->novalnetRequestHelper->getRequestHeaders(false, $storeId));
        $paymentMethodCode = $this->novalnetConfig->getPaymentCodeByType($requestData['transaction']['payment_type']);

        if (in_array(
            $paymentMethodCode,
            [
                ConfigProvider::NOVALNET_PREPAYMENT,
                ConfigProvider::NOVALNET_CASHPAYMENT,
                ConfigProvider::NOVALNET_MULTIBANCO
            ]
        )) {
            $this->clientFactory->post(NNConfig::NOVALNET_PAYMENT_URL, $this->jsonHelper->jsonEncode($requestData));
        } else {
            $this->clientFactory->post(NNConfig::NOVALNET_AUTHORIZE_URL, $this->jsonHelper->jsonEncode($requestData));
        }

        $response = json_decode($this->clientFactory->getBody(), true);

        if (!empty($response['result']['status']) && $response['result']['status'] == 'SUCCESS') {
            return $response;
        } elseif (!empty($response['result']['status_text'])) {
            throw new \Magento\Framework\Exception\LocalizedException(__($response['result']['status_text']));
        }
    }
}

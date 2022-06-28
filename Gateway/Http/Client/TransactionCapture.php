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
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Serialize\Serializer\Serialize;
use Novalnet\Payment\Logger\NovalnetLogger;

class TransactionCapture implements ClientInterface
{
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private $clientFactory;

    /**
     * @var \Novalnet\Payment\Helper\Request
     */
    private $novalnetRequestHelper;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var Magento\Framework\Serialize\Serializer\Serialize
     */
    private $serializer;

    /**
     * @var \Novalnet\Payment\Logger\NovalnetLogger
     */
    private $novalnetLogger;

    /**
     * @param Curl $clientFactory
     * @param Request $novalnetRequestHelper
     * @param Data $jsonHelper
     * @param Data $serializer
     * @param Data $novalnetLogger
     */
    public function __construct(
        Curl $clientFactory,
        Request $novalnetRequestHelper,
        Data $jsonHelper,
        Serialize $serializer,
        NovalnetLogger $novalnetLogger
    ) {
        $this->clientFactory = $clientFactory;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->jsonHelper = $jsonHelper;
        $this->serializer = $serializer;
        $this->novalnetLogger = $novalnetLogger;
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
        if (isset($requestData['action'])) {
            return $requestData;
        }

        $storeId = $requestData['storeId'];
        unset($requestData['storeId']);
        $this->clientFactory->setHeaders($this->novalnetRequestHelper->getRequestHeaders(false, $storeId));
        if (empty($requestData['transaction']['tid'])) {
            $this->clientFactory->post(NNConfig::NOVALNET_PAYMENT_URL, $this->jsonHelper->jsonEncode($requestData));
        } else {
            $this->novalnetLogger->notice('Capture has been initiated for the TID: ' . $requestData['transaction']['tid']);
            $this->clientFactory->post(NNConfig::NOVALNET_CAPTURE_URL, $this->jsonHelper->jsonEncode($requestData));
        }

        $response = (!empty($this->clientFactory->getBody())) ? json_decode($this->clientFactory->getBody(), true) : [];

        if (!empty($response['result']['status']) && $response['result']['status'] == 'SUCCESS') {
            $this->novalnetLogger->notice('The transaction has been confirmed successfully');
            return $response;
        } elseif (!empty($response['result']['status_text'])) {
            $this->novalnetLogger->notice('The transaction capture not working. The status text: ' . $response['result']['status_text']);
            throw new \Magento\Framework\Exception\LocalizedException(__($response['result']['status_text']));
        }
    }
}

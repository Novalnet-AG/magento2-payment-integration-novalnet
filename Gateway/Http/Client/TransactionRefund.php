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

class TransactionRefund implements ClientInterface
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
     * @var \Novalnet\Payment\Logger\NovalnetLogger
     */
    private $novalnetLogger;

    /**
     * @param \Magento\Framework\HTTP\Client\Curl $clientFactory
     * @param \Novalnet\Payment\Helper\Request $novalnetRequestHelper,
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     * @param \Novalnet\Payment\Logger\NovalnetLogger $novalnetLogger
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
        $storeId = $requestData['storeId'];
        unset($requestData['storeId']);
        $this->novalnetLogger->notice('Refund has been initiated for the TID: ' . $requestData['transaction']['tid']);
        $this->clientFactory->setHeaders($this->novalnetRequestHelper->getRequestHeaders(false, $storeId));
        $this->clientFactory->post(
            NNConfig::NOVALNET_REFUND_URL,
            $this->jsonHelper->jsonEncode($requestData)
        );
        $response = json_decode($this->clientFactory->getBody(), true);

        if (!empty($response['result']['status']) && $response['result']['status'] == 'SUCCESS') {
            $this->novalnetLogger->notice('The refund has been successfully');
            return $response;
        } elseif (!empty($response['result']['status_text'])) {
            $this->novalnetLogger->notice('The refund not working. The status text: ' . $response['result']['status_text']);
            throw new \Magento\Framework\Exception\LocalizedException(__($response['result']['status_text']));
        }
    }
}

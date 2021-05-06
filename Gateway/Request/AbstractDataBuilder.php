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
namespace Novalnet\Payment\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Novalnet\Payment\Helper\Request;
use Novalnet\Payment\Model\NNConfig;
use Novalnet\Payment\Model\Ui\ConfigProvider;
use Magento\Framework\Json\Helper\Data as jsonHelper;
use Magento\Framework\Serialize\Serializer\Serialize as serializer;

class AbstractDataBuilder implements BuilderInterface
{
    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $datetime;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * @var NNConfig
     */
    protected $novalnetConfig;

    /**
     * @var Request
     */
    protected $novalnetRequestHelper;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var jsonHelper
     */
    protected $jsonHelper;

    /**
     * @var serializer
     */
    protected $serializer;

    /**
     * @param UrlInterface $urlInterface
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param Request $novalnetRequestHelper
     * @param NNConfig $novalnetConfig
     * @param ConfigInterface $config
     * @param jsonHelper $jsonHelper
     * @param serializer $serializer
     */
    public function __construct(
        UrlInterface $urlInterface,
        StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Magento\Backend\Model\Auth\Session $authSession,
        Request $novalnetRequestHelper,
        NNConfig $novalnetConfig,
        ConfigInterface $config,
        jsonHelper $jsonHelper,
        serializer $serializer
    ) {
        $this->urlInterface = $urlInterface;
        $this->storeManager = $storeManager;
        $this->datetime = $datetime;
        $this->authSession = $authSession;
        $this->novalnetRequestHelper = $novalnetRequestHelper;
        $this->novalnetConfig = $novalnetConfig;
        $this->config = $config;
        $this->jsonHelper = $jsonHelper;
        $this->serializer = $serializer;
    }

    /**
     * Build request for Payment action
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDataObject = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($buildSubject);
        $order = $paymentDataObject->getOrder();
        $paymentMethodCode = $paymentDataObject->getPayment()->getMethodInstance()->getCode();

        $merchantParams = $this->buildMerchantParams($order);
        $customerParams = $this->buildCustomerParams($order, $paymentMethodCode);
        $transactionParams = $this->buildTransactionParams($order, $paymentMethodCode);
        $customParams = $this->buildCustomParams();
        $data = array_merge($merchantParams, $customerParams, $transactionParams, $customParams);
        $data['storeId'] = $paymentDataObject->getPayment()->getOrder()->getStoreId();
        $data = $this->filterStandardParameter($data);
        return $data;
    }

    /**
     * Build Merchant params
     *
     * @param DataObject $order
     * @return array
     */
    public function buildMerchantParams($order)
    {
        $storeId = $order->getStoreId();
        // Build Merchant Data
        $data['merchant'] = [
            'signature' => $this->novalnetConfig->getGlobalConfig('signature', $storeId),
            'tariff'    => $this->novalnetConfig->getGlobalConfig('tariff_id', $storeId),
        ];

        return $data;
    }

    /**
     * Build Customer params
     *
     * @param DataObject $order
     * @param string $paymentMethodCode
     * @return array
     */
    public function buildCustomerParams($order, $paymentMethodCode)
    {
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        $billingStreet = $this->getStreet($billingAddress);
        
        $methodSession = $this->novalnetRequestHelper->getMethodSession($paymentMethodCode);

        $shopperIp = $order->getRemoteIp();

        if (!$this->novalnetRequestHelper->isAdmin() && empty($shopperIp)) {
            $shopperIp = $order->getXForwardedFor();
        }
        $requestIp = $this->novalnetRequestHelper->getRequestIp();

        // Forming billing data
        if ($billingAddress) {
            $data['customer'] = [
                'first_name'  => $billingAddress->getFirstname(),
                'last_name'   => $billingAddress->getLastname(),
                'email'       => $billingAddress->getEmail(),
                'tel'         => $billingAddress->getTelephone(),
                'customer_ip' => ($shopperIp != $requestIp) ? $this->novalnetRequestHelper->getRequestIp() : $shopperIp,
                'customer_no' => $this->novalnetRequestHelper->getCustomerId(),
            ];

            $data['customer']['billing'] = [
                'street'       => $billingStreet,
                'city'         => $billingAddress->getCity(),
                'zip'          => $billingAddress->getPostcode(),
                'country_code' => $billingAddress->getCountryId(),
            ];

            if ($methodSession->getData($paymentMethodCode . '_dob')) {
                $data['customer']['birth_date'] = $this->datetime->date(
                    'Y-m-d',
                    $methodSession->getData($paymentMethodCode . '_dob')
                );
            }

            if (empty($data['customer']['birth_date']) && !empty($billingAddress->getCompany())) {
                $data['customer']['billing']['company'] = $billingAddress->getCompany();
            }
        }
        // Forming shipping data
        if (empty($shippingAddress) || (!empty($shippingAddress) && ($billingAddress->getFirstname() == $shippingAddress->getFirstname() &&
            $billingAddress->getLastname() == $shippingAddress->getLastname() &&
            $billingStreet == $this->getStreet($shippingAddress) &&
            $billingAddress->getCity() == $shippingAddress->getCity() &&
            $billingAddress->getCountryId() ==$shippingAddress->getCountryId() &&
            $billingAddress->getPostcode() == $shippingAddress->getPostcode()))
        ) {
            $data['customer']['shipping']['same_as_billing'] = 1;
        } else {
            $data['customer']['shipping'] = [
                'first_name'   => $shippingAddress->getFirstname(),
                'last_name'    => $shippingAddress->getLastname(),
                'email'        => $shippingAddress->getEmail(),
                'tel'          => $shippingAddress->getTelephone(),
                'street'       => $this->getStreet($shippingAddress),
                'city'         => $shippingAddress->getCity(),
                'zip'          => $shippingAddress->getPostcode(),
                'country_code' => $shippingAddress->getCountryId()
            ];
            if (!empty($shippingAddress->getCompany())) {
                $data['customer']['shipping']['company'] = $shippingAddress->getCompany();
            }
        }

        return $data;
    }

    /**
     * Build Transaction params
     *
     * @param DataObject $order
     * @param string $paymentMethodCode
     * @return array
     */
    public function buildTransactionParams($order, $paymentMethodCode)
    {
        $billingAddress = $order->getBillingAddress();
        $methodSession = $this->novalnetRequestHelper->getMethodSession($paymentMethodCode);

        $data['transaction'] = [
            'payment_type'     => $this->novalnetConfig->getPaymentType($paymentMethodCode),
            'amount'           => $this->novalnetRequestHelper->getFormattedAmount($order->getGrandTotalAmount()),
            'currency'         => $order->getCurrencyCode(),
            'test_mode'        => $this->novalnetConfig->getTestMode($paymentMethodCode),
            'order_no'         => $order->getOrderIncrementId(),
            'system_ip'        => $this->novalnetRequestHelper->getServerAddr(),
            'system_name'      => 'Magento',
            'system_version'   => $this->novalnetRequestHelper->getMagentoVersion() . '-' .
                                    $this->novalnetRequestHelper->getNovalnetVersion(),
            'system_url'       => $this->storeManager->getStore()->getBaseUrl()
        ];

        $paymentDatas = ['token', 'pan_hash', 'unique_id', 'iban', 'wallet_token'];

        foreach ($paymentDatas as $paymentData) {
            if ($methodSession->getData($paymentMethodCode . '_' . $paymentData)) {
                $data['transaction']['payment_data'][$paymentData] = preg_replace('/\s+/', '', $methodSession->getData($paymentMethodCode . '_' . $paymentData));
            }
        }

        if ($this->novalnetConfig->getPaymentConfig($paymentMethodCode, 'shop_type') && $methodSession->getData($paymentMethodCode . '_create_token') && empty($methodSession->getData($paymentMethodCode . '_token'))) {
            $data['transaction']['create_token'] = $methodSession->getData($paymentMethodCode . '_create_token');
        }

        $paymentDueDate = $this->novalnetConfig->getPaymentConfig($paymentMethodCode, 'due_date');
        $paymentDueDate = ltrim($paymentDueDate, '0');
        if ($paymentDueDate) {
            $data['transaction']['due_date'] = date('Y-m-d', strtotime('+' . $paymentDueDate . ' days'));
        }

        if ($methodSession->getData($paymentMethodCode . '_cycle')) {
            $data['instalment']['cycles'] = $methodSession->getData($paymentMethodCode . '_cycle');
            $data['instalment']['interval'] = '1m';
        }

        if ($this->novalnetConfig->isRedirectPayment($paymentMethodCode) || $methodSession->getData($paymentMethodCode . '_do_redirect')) {
            $data['transaction']['return_url'] = $this->urlInterface->getUrl('novalnet/redirect/success', ['order_no' => $order->getOrderIncrementId()]);
            $data['transaction']['error_return_url'] = $this->urlInterface->getUrl('novalnet/redirect/failure', ['order_no' => $order->getOrderIncrementId()]);
        }
        if ($this->novalnetConfig->getPaymentConfig($paymentMethodCode, 'enforce_3d')) {
            $data['transaction']['enforce_3d'] = 1;
        }

        return $data;
    }

    /**
     * Build Custom params
     *
     * @param none
     * @return array
     */
    public function buildCustomParams()
    {
        // Custom Data
        $data['custom'] = [
            'lang'      => $this->novalnetRequestHelper->getDefaultLanguage(),
        ];

        if ($this->novalnetRequestHelper->isAdmin()) {
            $data['custom']['input1'] = 'admin_user';
            $data['custom']['inputval1'] = $this->authSession->getUser()->getID();
        }

        return $data;
    }

    /**
     * Build Extension params
     *
     * @param array $buildSubject
     * @param string|null $refundAction
     * @return array
     */
    public function buildExtensionParams($buildSubject, $refundAction = false)
    {
        $paymentDataObject = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($buildSubject);
        $additionalData = $paymentDataObject->getPayment()->getAdditionalData();
        $storeId = $paymentDataObject->getPayment()->getOrder()->getStoreId();

        if ($additionalData) {
            $additionalData = $this->novalnetRequestHelper->isSerialized($additionalData)
                ? $this->serializer->unserialize($additionalData)
                : json_decode($additionalData, true);

            $data['transaction'] = [
                'tid' => $additionalData['NnTid'],
            ];

            if ($refundAction) {
                $refundAmount = \Magento\Payment\Gateway\Helper\SubjectReader::readAmount($buildSubject);

                $data['transaction']['amount'] = $this->novalnetRequestHelper->getFormattedAmount($refundAmount);
            }

            $data['custom'] = [
                'lang'         => $this->novalnetRequestHelper->getDefaultLanguage(),
                'shop_invoked' => 1
            ];
            $data['storeId'] = $storeId;
            return $data;
        }
    }

    /**
     * get Street from address
     *
     * @param object $address
     * @return string
     */
    public function getStreet($address)
    {
        if (method_exists($address, 'getStreetFull')) {
            $street = $address->getStreetFull();
        } else {
            $street = implode(' ', [$address->getStreetLine1(), $address->getStreetLine2()]);
        }

        return $street;
    }

    /**
     * @param array $requestData
     *
     * @return array
     */
    protected function filterStandardParameter($requestData)
    {
        $excludedParams = ['test_mode', 'enforce_3d', 'amount', 'storeId'];

        foreach ($requestData as $key => $value) {
            if (is_array($value)) {
                $requestData[$key] = $this->filterStandardParameter($requestData[$key]);
            }

            if (!in_array($key, $excludedParams) && empty($requestData[$key])) {
                unset($requestData[$key]);
            }
        }

        return $requestData;
    }
}

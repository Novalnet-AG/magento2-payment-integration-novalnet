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
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Novalnet\Payment\Model\NNConfig;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Downloadable\Model\Product\Type as DownloadableProduct;

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
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var NNConfig
     */
    protected $novalnetConfig;

    /**
     * @var \Novalnet\Payment\Helper\Data
     */
    protected $novalnetHelper;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @param UrlInterface $urlInterface
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Novalnet\Payment\Helper\Data $novalnetHelper
     * @param NNConfig $novalnetConfig
     * @param ConfigInterface $config
     */
    public function __construct(
        UrlInterface $urlInterface,
        StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Checkout\Model\Cart $cart,
        \Novalnet\Payment\Helper\Data $novalnetHelper,
        NNConfig $novalnetConfig,
        ConfigInterface $config
    ) {
        $this->urlInterface = $urlInterface;
        $this->storeManager = $storeManager;
        $this->datetime = $datetime;
        $this->authSession = $authSession;
        $this->novalnetHelper = $novalnetHelper;
        $this->novalnetConfig = $novalnetConfig;
        $this->config = $config;
        $this->cart = $cart;
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
        $methodSession = $this->novalnetHelper->getMethodSession($paymentMethodCode);
        $selectedMethod = $methodSession->getData($paymentMethodCode . '_type');

        $merchantParams = $this->buildMerchantParams($order);
        $customerParams = $this->buildCustomerParams($order, $paymentMethodCode);
        $transactionParams = $this->buildTransactionParams($order, $paymentMethodCode);
        $customParams = $this->buildCustomParams();
        $data = array_merge($merchantParams, $customerParams, $transactionParams, $customParams);
        $data['storeId'] = $paymentDataObject->getPayment()->getOrder()->getStoreId();
        $data = $this->filterStandardParameter($data);
        if ($selectedMethod == 'PAYPAL') {
            $cartInfoParams = $this->buildCartInfoParams();
            $data = array_merge($data, $cartInfoParams);
        }

        return $data;
    }

    /**
     * Build Merchant params
     *
     * @param mixed $order
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
     * @param mixed $order
     * @param string $paymentMethodCode
     * @return array
     */
    public function buildCustomerParams($order, $paymentMethodCode)
    {
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        $billingStreet = $this->novalnetHelper->getStreet($billingAddress);
        $methodSession = $this->novalnetHelper->getMethodSession($paymentMethodCode);
        $shopperIp = $order->getRemoteIp();

        if (!$this->novalnetHelper->isAdmin() && empty($shopperIp) && method_exists($order, 'getXForwardedFor')) {
            $shopperIp = $order->getXForwardedFor();
        }
        $requestIp = $this->novalnetHelper->getRequestIp();
        $data = [];

        // Forming billing data
        if ($billingAddress) {
            $data['customer'] = [
                'first_name'  => $billingAddress->getFirstname(),
                'last_name'   => $billingAddress->getLastname(),
                'email'       => $billingAddress->getEmail(),
                'tel'         => $billingAddress->getTelephone(),
                'customer_ip' => ($shopperIp != $requestIp) ? $this->novalnetHelper->getRequestIp() : $shopperIp,
                'customer_no' => $this->novalnetHelper->getCustomerId(),
            ];

            $data['customer']['billing'] = [
                'street'       => $billingStreet,
                'city'         => $billingAddress->getCity(),
                'zip'          => $billingAddress->getPostcode(),
                'country_code' => $billingAddress->getCountryId(),
                'state'        => $this->novalnetHelper->getRegionNameByCode($billingAddress->getRegionCode(), $billingAddress->getCountryId())
            ];

            if ($methodSession->getData($paymentMethodCode . '_birth_date')) {
                $data['customer']['birth_date'] = $this->datetime->date(
                    'Y-m-d',
                    $methodSession->getData($paymentMethodCode . '_birth_date')
                );
            }

            if (empty($data['customer']['birth_date']) && !empty($billingAddress->getCompany())) {
                $data['customer']['billing']['company'] = $billingAddress->getCompany();
            }
        }

        // Forming shipping data
        if (!empty($shippingAddress)) {
            $shippingStreet = $this->novalnetHelper->getStreet($shippingAddress);
            if ($billingAddress->getFirstname() == $shippingAddress->getFirstname() &&
                $billingAddress->getLastname() == $shippingAddress->getLastname() &&
                $billingStreet ==  $shippingStreet &&
                $billingAddress->getCity() == $shippingAddress->getCity() &&
                $billingAddress->getCountryId() ==$shippingAddress->getCountryId() &&
                $billingAddress->getPostcode() == $shippingAddress->getPostcode()
            ) {
                $data['customer']['shipping']['same_as_billing'] = 1;
            } else {
                $data['customer']['shipping'] = [
                    'first_name'   => $shippingAddress->getFirstname(),
                    'last_name'    => $shippingAddress->getLastname(),
                    'email'        => $shippingAddress->getEmail(),
                    'tel'          => $shippingAddress->getTelephone(),
                    'street'       => $shippingStreet,
                    'city'         => $shippingAddress->getCity(),
                    'zip'          => $shippingAddress->getPostcode(),
                    'country_code' => $shippingAddress->getCountryId(),
                    'state'        => $this->novalnetHelper->getRegionNameByCode($shippingAddress->getRegionCode(), $shippingAddress->getCountryId())
                ];
                if (!empty($shippingAddress->getCompany())) {
                    $data['customer']['shipping']['company'] = $shippingAddress->getCompany();
                }
            }
        }

        return $data;
    }

    /**
     * Build Transaction params
     *
     * @param mixed $order
     * @param string $paymentMethodCode
     * @return array
     */
    public function buildTransactionParams($order, $paymentMethodCode)
    {
        $methodSession = $this->novalnetHelper->getMethodSession($paymentMethodCode);
        $selectedMethod = $methodSession->getData($paymentMethodCode . '_type');

        $data['transaction'] = [
            'payment_type'     => $selectedMethod,
            'amount'           => $this->novalnetHelper->getFormattedAmount($order->getGrandTotalAmount()),
            'currency'         => $order->getCurrencyCode(),
            'test_mode'        => $methodSession->getData($paymentMethodCode . '_test_mode'),
            'order_no'         => $order->getOrderIncrementId(),
            'system_ip'        => $this->novalnetHelper->getServerAddr(),
            'system_name'      => 'Magento',
            'system_version'   => $this->novalnetHelper->getMagentoVersion() . '-' .
                                    $this->novalnetHelper->getNovalnetVersion(),
            'system_url'       => $this->storeManager->getStore()->getBaseUrl(),
            'paymentAction' => $methodSession->getData($paymentMethodCode . '_payment_action')
        ];

        $paymentDatas = ['token', 'pan_hash', 'unique_id', 'iban', 'wallet_token', 'bic','account_number', 'routing_number','account_holder'];

        foreach ($paymentDatas as $paymentData) {
            if ($methodSession->getData($paymentMethodCode . '_' . $paymentData)) {
                $data['transaction']['payment_data'][$paymentData] = preg_replace('/\s+/', '', $methodSession->getData($paymentMethodCode . '_' . $paymentData));
            }
        }

        $paymentRef = $methodSession->getData($paymentMethodCode . '_payment_ref');

        if (!empty($paymentRef['token'])) {
            $data['transaction']['payment_data']['token'] = $paymentRef['token'];
        }

        if ($methodSession->getData($paymentMethodCode . '_create_token')) {
            $data['transaction']['create_token'] = $methodSession->getData($paymentMethodCode . '_create_token');
        }

        if ($methodSession->getData($paymentMethodCode . '_payment_action') == NNConfig::ACTION_ZERO_AMOUNT) {
            $data['transaction']['create_token'] = 1;
            $data['transaction']['amount'] = 0;
        }

        $paymentDueDate = $methodSession->getData($paymentMethodCode . '_due_date');
        $paymentDueDate = (!empty($paymentDueDate)) ? ltrim($paymentDueDate, '0') : '';
        if ($paymentDueDate) {
            $data['transaction']['due_date'] = date('Y-m-d', strtotime('+' . $paymentDueDate . ' days'));
        }

        if ($methodSession->getData($paymentMethodCode . '_cycle')) {
            $data['instalment']['cycles'] = $methodSession->getData($paymentMethodCode . '_cycle');
            $data['instalment']['interval'] = '1m';
        }

        if ($methodSession->getData($paymentMethodCode . '_do_redirect') == '1' ||
            $methodSession->getData($paymentMethodCode . '_do_redirect') == true ||
            $methodSession->getData($paymentMethodCode . '_process_mode') == 'redirect'
         ) {
            $data['transaction']['return_url'] = $this->urlInterface->getUrl('novalnet/redirect/success', ['order_no' => $order->getOrderIncrementId()]);
            $data['transaction']['error_return_url'] = $this->urlInterface->getUrl('novalnet/redirect/failure', ['order_no' => $order->getOrderIncrementId()]);
        }

        if ($methodSession->getData($paymentMethodCode . '_enforce_3d')) {
            $data['transaction']['enforce_3d'] = $methodSession->getData($paymentMethodCode . '_enforce_3d');
        }

        return $this->buildSubscriptionParams($order, $data);
    }

    /**
     * Build subscription params
     *
     * @param mixed $order
     * @param array $data
     * @return array
     */
    public function buildSubscriptionParams($order, $data)
    {
        foreach ($order->getItems() as $item) {
            $additionalData = $this->novalnetHelper->jsonDecode($item->getAdditionalData());
            if (!empty($additionalData['period_unit']) && !empty($additionalData['billing_frequency'])) {
                if (!isset($data['transaction']['create_token']) && !isset($data['transaction']['payment_data']['token'])) {
                    $data['transaction']['create_token'] = 1;
                }

                break;
            }
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        if ($objectManager->create(\Magento\Framework\Session\SessionManagerInterface::class)->getRecurringProcess()) {
            unset($data['transaction']['return_url']);
            unset($data['transaction']['error_return_url']);
        }

        return $data;
    }

    /**
     * Build Custom params
     *
     * @return array
     */
    public function buildCustomParams()
    {
        // Custom Data
        $data['custom'] = [
            'lang'      => $this->novalnetHelper->getDefaultLanguage(),
        ];

        if ($this->novalnetHelper->isAdmin()) {
            $data['custom']['input1'] = 'admin_user';
            $data['custom']['inputval1'] = $this->authSession->getUser()->getID();
        }

        return $data;
    }

    /**
     * Build cart info params
     *
     * @return array
     */
    public function buildCartInfoParams()
    {
        $data['cart_info'] = [];
        $quote = $this->cart->getQuote();
        $discount = $quote->getBaseSubtotal() - $quote->getBaseSubtotalWithDiscount();
        $discount = ($discount > 0) ? $this->novalnetHelper->getFormattedAmount($discount) : 0;
        $taxAmount = !empty($quote->getTotals()['tax']->getValue()) ? $quote->getTotals()['tax']->getValue() : 0;
        $taxAmount = $this->novalnetHelper->deltaRounding($taxAmount);
        $data['cart_info'] = [
            'items_shipping_price' => ($quote->getShippingAddress()->getBaseShippingAmount() > 0) ? $this->novalnetHelper->getFormattedAmount($quote->getShippingAddress()->getBaseShippingAmount()) : 0,
            'items_tax_price' => ($taxAmount > 0) ? $this->novalnetHelper->getFormattedAmount($taxAmount) : 0
        ];

        $lineItems = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            array_push($lineItems, [
                'category' => ($item->getTypeId() == DownloadableProduct::TYPE_DOWNLOADABLE || $item->getTypeId() == ProductType::TYPE_VIRTUAL) ? 'virtual' : 'physical',
                'description' => '',
                'name' => $item->getName(),
                'price' => $this->novalnetHelper->getFormattedAmount($item->getPrice()),
                'quantity' => $item->getQty()
            ]);
        }

        if ($discount) {
            array_push($lineItems, [
                'category' => '',
                'description' => '',
                'name' => 'Discount',
                'price' => '-' . $discount,
                'quantity' => 1
            ]);
        }

        $data['cart_info']['line_items'] = $lineItems;

        return $data;
    }

    /**
     * Build Extension params
     *
     * @param array $buildSubject
     * @param string|bool $refundAction
     * @return array
     */
    public function buildExtensionParams($buildSubject, $refundAction = false)
    {
        $paymentDataObject = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($buildSubject);
        $additionalData = $paymentDataObject->getPayment()->getAdditionalData();
        $storeId = $paymentDataObject->getPayment()->getOrder()->getStoreId();

        if ($additionalData) {
            $additionalData = $this->novalnetHelper->getPaymentAdditionalData($additionalData);

            $data['transaction'] = [
                'tid' => $additionalData['NnTid']
            ];

            if ($refundAction) {
                $refundAmount = \Magento\Payment\Gateway\Helper\SubjectReader::readAmount($buildSubject);

                $data['transaction']['amount'] = $this->novalnetHelper->getFormattedAmount($refundAmount);

                if (!empty($additionalData['NnZeroAmountBooking']) && !empty($additionalData['NnZeroAmountDone'])) {
                    $data['transaction']['tid'] = $additionalData['NnZeroAmountRefTid'];
                }
            }

            $data['custom'] = [
                'lang'         => $this->novalnetHelper->getDefaultLanguage(),
                'shop_invoked' => 1
            ];
            $data['storeId'] = $storeId;
            return $data;
        }
    }

    /**
     * Get filter standard param
     *
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

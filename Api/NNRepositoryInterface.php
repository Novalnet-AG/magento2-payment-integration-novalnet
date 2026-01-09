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
namespace Novalnet\Payment\Api;

interface NNRepositoryInterface
{
    /**
     * Novalnet product activation key auto config
     *
     * @api
     * @param string $signature
     * @param string $payment_access_key
     * @return string
     */
    public function activateProductKey($signature, $payment_access_key);

    /**
     * Novalnet Webhook URL configuration
     *
     * @api
     * @param string $signature
     * @param string $payment_access_key
     * @return string
     */
    public function configWebhookUrl($signature, $payment_access_key);

    /**
     * Get redirect URL
     *
     * @api
     * @param string[] $data
     * @return string
     */
    public function getRedirectURL($data);

    /**
     * Remove Novalnet payment token
     *
     * @api
     * @param int $transactionRowId
     * @return bool
     */
    public function removeToken($transactionRowId);

    /**
     * Get Instalment payment options
     *
     * @api
     * @param string $code
     * @param float $total
     * @return string
     */
    public function getInstalmentOptions($code, $total);

    /**
     * Get Instalment payment cycle details
     *
     * @api
     * @param float $amount
     * @param int $period
     * @return string
     */
    public function getInstalmentCycleAmount($amount, $period);

    /**
     * Novalnet payment callback
     *
     * @api
     * @return string
     */
    public function callback();

    /**
     * Add to Cart
     *
     * @api
     * @param string $data
     * @return string
     */
    public function addToCart($data);

    /**
     * Place Order
     *
     * @api
     * @param string[] $paymentData
     * @param string[] $billingAddress
     * @param string[] $shippingAddress
     * @param string[] $shippingMethod
     * @param bool $isPaymentPage
     * @return string
     */
    public function placeOrder($paymentData, $billingAddress, $shippingAddress = [], $shippingMethod = [], $isPaymentPage = false);

    /**
     * Get Cart Contents
     *
     * @api
     * @return string
     */
    public function getCart();

    /**
     * Estimate Shipping by Address
     *
     * @api
     * @param string[] $address
     * @return string
     */
    public function estimateShippingMethod($address);

    /**
     * Get express checkout request params for Product page
     *
     * @api
     * @param string[] $data
     * @return string
     */
    public function getProductPageParams($data);

    /**
     * Apply shipping method and calculate totals
     *
     * @api
     * @param string[] $shippingMethod
     * @return string
     */
    public function applyShippingMethod($shippingMethod);
}

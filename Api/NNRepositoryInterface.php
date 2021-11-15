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
     * Novalnet Webhook URL config
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
     * @param mixed $quoteId
     * @return mixed
     */
    public function getRedirectURL($quoteId);

    /**
     * Remove Novalnet payment token
     *
     * @api
     * @param int $transactionRowId
     * @return bool
     */
    public function removeToken($transactionRowId);

    /**
     * Get Instalment payment cycle details
     *
     * @api
     * @param string $code
     * @param float $total
     * @return mixed
     */
    public function getInstalmentOptions($code, $total);

    /**
     * Get Instalment payment cycle details
     *
     * @api
     * @param float $amount
     * @param int $period
     * @return bool
     */
    public function getInstalmentCycleAmount($amount, $period);

    /**
     * Novalnet payment callback
     *
     * @api
     * @return bool
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
     * @param mixed $data
     * @param bool $paymentPage
     * @return string
     */
    public function placeOrder($data, $paymentPage = false);

    /**
     * Get Cart Contents
     *
     * @api
     * @param none
     * @return string
     */
    public function getCart();

    /**
     * Estimate Shipping by Address
     *
     * @api
     * @param mixed $address
     * @return string
     */
    public function estimateShippingMethod($address);

    /**
     * Set billing address from data object
     *
     * @api
     * @param mixed $productId
     * @return string
     */
    public function getProductPageParams($productId);

    /**
     * Apply shipping method and calculate totals
     *
     * @api
     * @param mixed $shippingMethod
     * @return string
     */
    public function applyShippingMethod($shippingMethod);
}

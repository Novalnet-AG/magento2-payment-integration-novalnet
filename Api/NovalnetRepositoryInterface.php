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

interface NovalnetRepositoryInterface
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
     * Returns URL to redirect after place order
     *
     * @api
     * @param string[] $data
     * @return string
     */
    public function getRedirectURL($data);

    /**
     * Place Order
     *
     * @api
     * @param string[] $data
     * @param bool $paymentPage
     * @return string
     */
    public function placeOrder($data, $paymentPage = false);

    /**
     * To get v3 payment form link
     *
     * @api
     * @param string[] $data
     * @return string
     */
    public function getPayByLink($data);
}

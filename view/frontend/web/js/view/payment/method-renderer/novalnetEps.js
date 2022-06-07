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
define(
    [
        'jquery',
        'mage/url',
        'mage/translate',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/redirect-on-success',
        'Novalnet_Payment/js/action/get-redirect-url',
        'Magento_Ui/js/model/messageList'
    ],
    function (
        $,
        url,
        $t,
        Component,
        redirectOnSuccessAction,
        redirectURLAction,
        globalMessageList
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Novalnet_Payment/payment/novalnetEps'
            },

            /**
             * After place order callback
             */
            afterPlaceOrder: function () {
                this.redirectAfterPlaceOrder = false;
                    
                redirectURLAction().success(function (response) {
                    window.location.replace(response);
                }).error(function (xhr, ajaxOptions, thrownError) {
                    globalMessageList.addErrorMessage({
                        message: $t(thrownError)
                    });
                    window.location.replace(url.build('checkout/cart'));
                });
            },

            /**
             * Returns payment method instructions
             */
            getInstructions: function () {
                return window.checkoutConfig.payment[this.getCode()].instructions;
            },

            /**
             * Returns payment testmode status
             */
            getTestmode: function () {
                return window.checkoutConfig.payment[this.getCode()].testmode;
            },

            /**
             * Returns payment method logo URL
             */
            getLogo: function () {
                return window.checkoutConfig.payment[this.getCode()].logo;
            }
        });
    }
);

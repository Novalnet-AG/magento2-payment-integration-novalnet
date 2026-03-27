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
        'mage/storage',
        'mage/translate',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/action/redirect-on-success',
        'Novalnet_Payment/js/action/get-redirect-url',
        'Magento_Ui/js/model/messageList',
        'Magento_Customer/js/model/customer',
        'Magento_Ui/js/modal/alert',
        'Magento_Ui/js/modal/modal',
        'mage/validation'
    ],
    function (
        $,
        url,
        storage,
        $t,
        Component,
        urlBuilder,
        redirectOnSuccessAction,
        redirectURLAction,
        globalMessageList,
        customer,
        alert,
        modal
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: {
                    name: 'Novalnet_Payment/payment/novalnetPaypal',
                    afterRender: function (renderedNodesArray, data) {
                        data.displayForm();
                    }
                },
                novalnetPaymentToken: ''
            },

            initObservable: function () {
                this._super()
                    .observe(
                        [
                        'novalnetPaymentToken'
                        ]
                    );
                this.novalnetPaymentToken.subscribe(this.onAccountChange, this);
                this.novalnetPaymentToken(window.checkoutConfig.payment[this.getCode()].tokenId);

                return this;
            },

            onAccountChange: function (selectedAccount) {
                if (selectedAccount == 'new_account') {
                    $('#' + this.getCode() + '_store_payment_container').show();
                } else {
                    $('#' + this.getCode() + '_store_payment_container').hide();
                }
            },

            displayForm: function () {
                    this.onAccountChange('new_account');
            },

            /**
             * After place order callback
             */
            afterPlaceOrder: function () {
                this.redirectAfterPlaceOrder = false;

                redirectURLAction().done(function (redirectUrl) {
                    window.location.replace(redirectUrl);
                }).fail(function (xhr, ajaxOptions, thrownError) {
                    globalMessageList.addErrorMessage({
                        message: $t(thrownError)
                    });
                    window.location.replace(url.build('checkout/cart'));
                });
            },

            getData: function () {
                return {
                    'method': this.item.method,
                };
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
            },

            validate: function () {
                return true;
            },
        });
    }
);

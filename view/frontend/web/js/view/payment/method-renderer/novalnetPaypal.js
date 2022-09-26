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
                if ((window.checkoutConfig.payment[this.getCode()].storePayment != '1') || (window.checkoutConfig.payment[this.getCode()].storedPayments.length == '0')) {
                    this.onAccountChange('new_account');
                } else {
                    this.onAccountChange(window.checkoutConfig.payment[this.getCode()].tokenId);
                }
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

            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'novalnetPaypal_create_token': this.canStorePayment(),
                        'novalnetPaypal_token': this.getPaymentToken()
                    }
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

            /**
             * check can store payment reference
             */
            canStorePayment: function () {
                if (!this.getPaymentToken()) {
                    return ($('#' + this.getCode() + '_store_payment').prop("checked") == true);
                } else {
                    return false;
                }
            },

            /**
             * check can show store payment
             */
            showStorePayment: function () {
                if (window.checkoutConfig.payment[this.getCode()].storePayment == '1') {
                    return customer.isLoggedIn();
                } else {
                    return false;
                }
            },

            /**
             * get stored payments
             */
            getStoredPayments: function () {
                if (window.checkoutConfig.payment[this.getCode()].storePayment == '1') {
                    return window.checkoutConfig.payment[this.getCode()].storedPayments;
                } else {
                    return false;
                }
            },

            /**
             * get payment token
             */
            getPaymentToken: function () {
                if (this.novalnetPaymentToken() && this.novalnetPaymentToken() != 'new_account') {
                    return this.novalnetPaymentToken();
                } else {
                    return false;
                }
            },

            /**
             * remove payment token
             */
            removeToken: function (tokenId) {
                var parent = this;

                var options = {
                    type: 'popup',
                    modalClass: 'nntoken-remove-popup-modal',
                    responsive: true,
                    innerScroll: false,
                    buttons: [{
                        text: $t('No'),
                        class: 'nntoken-cancel-remove-modal action tocart primary',
                        click: function () {
                            this.closeModal();
                        }
                    },
                    {
                        text: $t('Yes'),
                        class: 'nntoken-confirm-remove-modal action tocart primary',
                        click: function () {
                            var button = this;
                            storage.get(
                                urlBuilder.createUrl('/novalnet/payment/remove_token/' + tokenId, {})
                            ).success(function (response) {
                                button.closeModal();
                                if (response) {
                                    $('a[nntoken-id=' + tokenId + ']').closest('.novalnet-payment-saved-payments').remove();
                                    if ($('form#novalnetPaypal .novalnet-payment-saved-payments').length <= 0) {
                                        $('form#novalnetPaypal .novalnet-payment-new_account').remove();
                                    }
                                    $('#novalnetPaypal_store_payment_container').show();
                                    parent.novalnetPaymentToken('new_account');
                                    window.location = window.location.hash ;
                                    location.reload();
                                } else {
                                    alert({
                                        content: $t('Novalnet Payment Token does not found')
                                    });
                                }
                            }).error(function (xhr, ajaxOptions, thrownError) {
                                button.closeModal();
                                alert({
                                    content: $t(thrownError)
                                });
                            });
                        }
                    }]
                };

                // Initialize and Open popup
                modal(options, $('#remove-nntoken-modal'));
                $("#remove-nntoken-modal").modal("openModal");
            },

            validate: function () {
                return true;
            },
        });
    }
);

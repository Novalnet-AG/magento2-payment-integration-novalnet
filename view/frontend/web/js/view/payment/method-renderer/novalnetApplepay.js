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
        'Magento_Checkout/js/view/payment/default',
        'Magento_Ui/js/modal/alert',
        'Magento_Checkout/js/model/quote',
        'mage/translate',
        'Magento_Checkout/js/model/totals',
        'mage/url',
        'mage/storage',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/cart/totals-processor/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'novalnetCheckout'
    ],
    function(
        $,
        Component,
        alert,
        quote,
        $t,
        totals,
        urlBuilder,
        storage,
        customerData,
        totalsDefaultProvider,
        additionalValidators
    ) {
        'use strict';
        var nn_applepay = {

            /**
             * Default's
             */
            defaults: {
                template: 'Novalnet_Payment/payment/novalnetApplepay'
            },

            /**
             * Initialize function
             */
            initObservable: function () {
                this._super();
                var cart = customerData.get('cart');
                cart.subscribe(this.refreshCheckout, this);
                return this;
            },

            /**
             * Refresh quote grand total on cart change
             */
            refreshCheckout: function() {
                var cart = customerData.get('cart');
                if (cart().summary_count && cart().summary_count > 0) {
                    totalsDefaultProvider.estimateTotals(quote.shippingAddress());
                }
            },

            /**
             * Returns payment method instructions
             */
            getInstructions: function() {
                return window.checkoutConfig.payment[this.item.method].instructions;
            },

            /**
             * Returns payment method logo
             */
            getLogo: function() {
                return window.checkoutConfig.payment[this.item.method].logo;
            },

            isApplePayAllowed: function() {
                return NovalnetUtility.isApplePayAllowed();
            },

            /**
             * Returns payment testmode status
             */
            getTestmode: function() {
                return window.checkoutConfig.payment[this.item.method].testmode;
            },

            /**
             * Returns Languge Code
             */
            getLanguageCode: function() {
                return window.checkoutConfig.payment[this.item.method].langCode;
            },

            /**
             * Returns Grand Total
             */
            getGrandTotal: function() {
                if (quote.totals()) {
                    return Math.round(parseFloat(quote.totals().base_grand_total) * 100);
                }
            },

            /**
             * Throw's alert on payment error
             */
            throwError: function(message) {
                alert({
                    title: $t('Error'),
                    content: message
                });
            },

            /**
             * Returns display items for payment sheet
             */
            getLineItems: function() {
                var items = totals.totals().items,
                    currencyRate = window.checkoutConfig.quoteData.base_to_quote_rate,
                    lineItem = [],
                    i;

                if(items.length) {
                    for( i = 0; i < items.length; i++ ) {
                        lineItem[i] = {
                                label: items[i].name + ' (' + Math.round(parseFloat(items[i].qty)) + ' x ' + Number.parseFloat(items[i].base_price).toFixed(2) + ')',
                                amount: Math.round((parseFloat(items[i].base_row_total)) * 100)
                            };
                    }
                }

                if (totals.totals().hasOwnProperty('tax_amount')) {
                    var tax = Math.round( (parseFloat(totals.totals().tax_amount) / parseFloat(currencyRate)) * 100),
                        taxItem = {label: 'Tax', amount: tax };
                    if(taxItem) {
                        lineItem.push(taxItem);
                    }
                }

                if (totals.totals().hasOwnProperty('discount_amount')) {
                    var discountTotal = (Math.round( (parseFloat(totals.totals().discount_amount) / parseFloat(currencyRate)) * 100)).toString(),
                        discount = ((Math.sign(discountTotal)) == -1 ) ? discountTotal.substr(1) : discountTotal,
                        discountItem = {label: 'Discount', amount: - discount};
                    if(discountItem) {
                        lineItem.push(discountItem);
                    }
                }

                if (totals.totals().hasOwnProperty('shipping_amount') && !quote.isVirtual()) {
                    var Shipping = Math.round( (parseFloat(totals.totals().shipping_amount) / parseFloat(currencyRate)) * 100),
                        ShippingItem = {label: 'Shipping', amount: Shipping.toString()};
                        lineItem.push(ShippingItem);
                }

                return lineItem;
            },

            /**
             * Returns Applepay payment configuration
             */
            getApplepayConfig: function() {
                return window.checkoutConfig.payment.novalnetApplepay;
            },

            /**
             * Loads applepay button
             */
            initApplepayButton: function() {
                var config = this.getApplepayConfig();
                $('#novalnet_applepay_checkoutbtn').addClass(`${config.btnStyle} ${config.btnTheme}`)
                .css({ 'width': '300px', 'height': `${config.btnHeight}px`, 'border-radius':  `${config.btnRadius}px`});
            },

            /**
             * Init checkout page applepay button on DOM load
             */
            initCheckoutPage: function() {
                $('#novalnet_applepay_guest_checkoutdiv').css('display','none');

                var self = this;
                if(NovalnetUtility.isApplePayAllowed()) {
                    NovalnetUtility.setClientKey(window.checkoutConfig.payment.novalnetApplepay.clientKey);
                    self.initApplepayButton();

                    $('#novalnet_applepay_checkoutbtn').off('click').on('click', function(event) {
                        if (!additionalValidators.validate()) {
                            event.preventDefault();
                            return false;
                        } else {
                            self.applepayPaymentRequest();
                        }
                    });
                }
            },

            /**
             * Applepay Payment Request
             */
            applepayPaymentRequest: function() {
                var self = this,
                    configurations =  self.getApplepayConfig(),
                    is_amountPending = quote.isVirtual() ? true : configurations.is_pending,
                    requestData = {
                    callback: {
                        on_completion: function(response, callback) {
                            if(response && response.result.status) {
                                if(response.result.status == 'SUCCESS') {
                                    var serviceUrl = urlBuilder.build('/rest/V1/novalnet/payment/placeOrder', {}),
                                        payload = {data: response, paymentPage: true};
                                    storage.post( serviceUrl, JSON.stringify(payload)).done(function(response) {
                                            var result = JSON.parse(response);
                                            window.location.replace(result.successUrl);
                                            callback('SUCCESS');
                                    }).fail(function(xhr, textStatus, errorThrown) {
                                            var errMsg = JSON.parse(xhr.responseText);
                                                self.throwError(errMsg.message);
                                            callback('ERROR');
                                    });
                                }
                            }

                            return true;
                        }
                    },
                    merchant: {
                        country_code: configurations.countryCode
                    },
                    transaction: {
                        amount: self.getGrandTotal(),
                        currency: configurations.currencyCode,
                        set_pending: is_amountPending
                    },
                    custom: {
                        lang: configurations.langCode
                    },
                    wallet: {
                        shop_name: configurations.sellerName,
                        order_info: self.getLineItems(),
                        required_fields: {
                            shipping: ['email', 'phone'],
                            contact: ['postalAddress']
                        }
                    }
                };

                NovalnetUtility.processApplePay(requestData);
            }
        };

        $(document).on('click', '#co-payment-form input[type="radio"]', function(event) {
            if(this.value == "novalnetApplepay") {
                if(NovalnetUtility.isApplePayAllowed()) {
                    nn_applepay.initApplepayButton();
                    $('#novalnet_applepay_checkoutbtn').off('click').on('click', function(ev) {
                        if (!additionalValidators.validate()) {
                            ev.preventDefault();
                            return false;
                        } else {
                            nn_applepay.applepayPaymentRequest();
                        }
                    });
                }
            }
        });

        $(document).ready(function () {
            setTimeout(function() {
                $('#novalnet_applepay_minicartdiv').css('display','none');
            },1000);
        });

        return Component.extend(nn_applepay);
    }
);

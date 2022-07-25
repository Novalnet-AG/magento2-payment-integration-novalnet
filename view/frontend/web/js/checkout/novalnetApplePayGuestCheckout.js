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
        'uiComponent',
        'mage/url',
        'mage/storage',
        'Magento_Ui/js/modal/alert',
        'Magento_Checkout/js/model/quote',
        'mage/translate',
        'Magento_Checkout/js/model/totals',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/cart/totals-processor/default',
        'novalnetCheckout'
    ],
    function($, Component, urlBuilder, storage, alert, quote, $t, totals, customerData, totalsDefaultProvider)
    {
        'use strict';
        var nnApplepay = {
            /**
             * Default's
             */
            defaults: {
                template: 'Novalnet_Payment/checkout/ApplepayGuestCheckout'
            },

            /**
             * Initialize function
             */
            initObservable: function () {
                this._super();
                window.addEventListener('hashchange', _.bind(this.handleCheckoutVisibility,this));
                var cart = customerData.get('cart');
                cart.subscribe(this.refreshCheckout, this);
                return this;
            },

            /**
             * Handle's Guest checkout applepay button visibility
             */
            handleCheckoutVisibility: function() {
                var hashVal = window.location.hash;
                if (hashVal == '#shipping' && NovalnetUtility.isApplePayAllowed()) {
                    $('#novalnet_applepay_guest_checkoutdiv').css({'display' : 'block'});
                } else {
                    $('#novalnet_applepay_guest_checkoutdiv').css({'display' : 'none'});
                }
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
             * Returns Grand Total 
             */
            getGrandTotal: function() {
                if (quote.totals()) {
                    return Math.round(parseFloat(quote.totals().base_grand_total) * 100);
                }
            },

            /**
             * Returns Languge Code
             */
            getLangCode: function() {
                return window.checkoutConfig.payment[this.getCode()].langCode;
            },

            /**
             * Returns Method Code
             */         
            getCode: function() {
                return 'novalnetApplepay';
            },


            /**
             * Throw's alert on payment error
             */
            throwError: function(message){
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
                return window.checkoutConfig.payment[this.getCode()];
            },

            /**
             * Loads applepay button
             */
            initApplepayButton: function() {
                var config = this.getApplepayConfig();
                $('#novalnet_applepay_guest_checkoutBtn').addClass(`${config.btnStyle} ${config.btnTheme}`)
                .css({'width': '300px', 'height': `${config.btnHeight}px`, 'border-radius':  `${config.btnRadius}px`});
                $('#novalnet_applepay_guest_checkoutdiv').css({'display' : 'block'});
            },

            /**
             * Built Applepay Payment Request
             */
            applepayPaymentRequest: function() {
                var self = this,
                    configurations =  self.getApplepayConfig(),
                    requestData = {
                    callback: {
                        on_completion: function(response, callback) {
                            if(response && response.result.status) {
                                if(response.result.status == 'SUCCESS') {
                                    var serviceUrl = urlBuilder.build('/rest/V1/novalnet/payment/placeOrder', {}),
                                        payload = {data: response};
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
                        },
                        on_shippingcontact_change: function (shippingContact, updatedData) {
                            var serviceUrl = urlBuilder.build('/rest/V1/novalnet/payment/estimateShippingMethod', {}),
                                payload = {address : shippingContact};

                               new Promise(function(resolve, reject) {
                                    storage.post( serviceUrl, JSON.stringify(payload)).done(function(response) {
                                                var result = JSON.parse(response);
                                                    resolve(result);
                                    }).fail(function(xhr, textStatus, errorThrown) {
                                                var errMsg = JSON.parse(xhr.responseText);
                                                    self.throwError(errMsg.message);
                                    });

                                }).then(function(info) {
                                        var updatedInfo = {
                                                amount: info.total.amount,
                                                order_info: info.displayItems
                                            };

                                        if(info.isVirtual != '1') {
                                               updatedInfo.shipping_methods = info.methods;
                                            }

                                        updatedData(updatedInfo);
                                });
                        },
                        on_shippingmethod_change: function (choosenShippingMethod, updatedData) {
                            var serviceUrl = urlBuilder.build('/rest/V1/novalnet/payment/applyShippingMethod', {}),
                                payload = {shippingMethod : choosenShippingMethod};

                               new Promise(function(resolve, reject) {
                                    storage.post( serviceUrl, JSON.stringify(payload)).done(function(response) {
                                                var result = JSON.parse(response);
                                                    resolve(result);
                                    }).fail(function(xhr, textStatus, errorThrown) {
                                                var errMsg = JSON.parse(xhr.responseText);
                                                    self.throwError(errMsg.message);
                                    });
                                }).then(function(info) {
                                        var updatedInfo = {
                                                amount: info.total.amount,
                                                order_info: info.displayItems
                                            };

                                        updatedData(updatedInfo);
                                });
                        }
                    },
                    merchant: {
                        country_code: configurations.countryCode,
                    },
                    transaction: {
                        amount: self.getGrandTotal(),
                        currency: configurations.currencyCode,
                        set_pending: configurations.is_pending
                    },
                    custom: {
                        lang: configurations.langCode,
                    },
                    wallet: {
                        shop_name: configurations.sellerName,
                        shipping_configuration: {
                            type: 'shipping',
                            calc_final_amount_from_shipping: '0',
                        },
                        required_fields: {
                            shipping: ['postalAddress', 'email', 'name', 'phone'],
                            contact: ['postalAddress'],
                        }
                    }
                },

                virtualRequestData = {
                    callback: {
                        on_completion: function(response, callback) {
                            if(response && response.result.status) {
                                if(response.result.status == 'SUCCESS') {
                                    var serviceUrl = urlBuilder.build('/rest/V1/novalnet/payment/placeOrder', {}),
                                        payload = {data: response};
                                    storage.post( serviceUrl, JSON.stringify(payload)).done(function(response){
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
                        country_code: configurations.countryCode,
                    },
                    transaction: {
                        amount: self.getGrandTotal(),
                        currency: configurations.currencyCode,
                        set_pending: '1'
                    },
                    custom: {
                        lang: configurations.langCode,
                    },
                    wallet: {
                        shop_name: configurations.sellerName,
                        order_info: self.getLineItems(),
                        required_fields: {
                            shipping: ['email', 'phone'],
                            contact: ['postalAddress'],
                        }
                    }
                };

                if(quote.isVirtual()) {
                    NovalnetUtility.processApplePay(virtualRequestData);
                } else {
                     NovalnetUtility.processApplePay(requestData);
                }
            },

            /**
             * Init guest checkout applepay button
             */
            initGuestCheckoutPage: function() {
                var self = this,
                    currentURL = window.location.href,
                    pattern = new RegExp('/checkout/#payment'),
                    result = pattern.test(currentURL);

                if(NovalnetUtility.isApplePayAllowed() && result !== true) {
                    NovalnetUtility.setClientKey(window.checkoutConfig.payment[self.getCode()].clientKey);
                    self.initApplepayButton();
                    $('#novalnet_applepay_guest_checkoutBtn').click(function() {
                        self.applepayPaymentRequest();
                    });
                }
            }
        };

        return Component.extend(nnApplepay);
    }
);

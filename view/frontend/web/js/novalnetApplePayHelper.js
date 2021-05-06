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
        'Magento_Ui/js/modal/alert',
        'mage/translate',
        'Magento_Catalog/product/view/validation',
        'Magento_Customer/js/customer-data',
        'novalnetCheckout'
    ],
    function( $, urlBuilder, storage, mageAlert, $t, validation, customerData)
    {
        'use strict';
         return {
            /**
             * Throw's Alert on Payment Error
             */
            throwError: function(message) {
                mageAlert({
                    title: $t('Error'),
                    content: message
                }); 
            },

            /**
             * Returns cart items from quote
             */
             getCartItems: function(callback) {
                var getCartUrl =  urlBuilder.build('/rest/V1/novalnet/payment/getCart', {}),
                    self = this;
                storage.post( getCartUrl, null , false).done(function(response) {
                                var quoteValues = JSON.parse(response);
                                    callback(quoteValues);
                    }).fail(function(xhr, textStatus, errorThrown) {
                                var errMsg = JSON.parse(xhr.responseText);
                                    self.throwError(errMsg.message);
                    });
             },

            /**
             * Add product to cart
             */
            addToCart: function() {
                var addToCartUrl =  urlBuilder.build('/rest/V1/novalnet/payment/addToCart', {}),
                    form = $('#product_addtocart_form'),
                    request = form.serialize(),
                    payload = {data : request},
                    self = this;

                storage.post( addToCartUrl, JSON.stringify(payload)).done(function(response) {
                    customerData.reload(['cart'], true);
                }).fail(function(xhr, textStatus, errorThrown) {
                    var errMsg = JSON.parse(xhr.responseText);
                        self.throwError(errMsg.message);
                });
            },

            /**
             * Get Initial Params
             */
            getQuoteValues: function(product, callback) {
                var serviceUrl = urlBuilder.build('/rest/V1/novalnet/payment/getProductPageParams', {}),
                    payload = {productId : product},
                    self = this;

                    storage.post(serviceUrl, JSON.stringify(payload)).done(function(response) {
                        if (typeof response === 'string') {
                            response = JSON.parse(response);
                        }
                        callback(response);
                    }).fail(function(xhr, textStatus, errorThrown) {
                        var errMsg = JSON.parse(xhr.responseText);
                            self.throwError(errMsg.message);
                    });
            },

            /**
             * Applies ApplePay button styles and theme
             */
            initApplepayButton: function(buttonConfig, applepayBtn, applepayDiv) {
                applepayBtn.addClass(`${buttonConfig.btnStyle} ${buttonConfig.btnTheme}`)
                            .css({'height': `${buttonConfig.btnHeight}px`, 'border-radius':  `${buttonConfig.btnRadius}px`})
                            .attr({'lang': buttonConfig.langCode});
                applepayDiv.css({'display' : 'block'});
            },

            /**
             * Built ApplePay Payment Request
             */
            applepayPaymentRequest: function(quote) {
                var self = this,
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
                        country_code: quote.sheetConfig.countryCode,
                    },
                    transaction: {
                        amount: quote.total.amount,
                        currency: quote.sheetConfig.currencyCode,
                        set_pending: quote.is_pending
                    },
                    custom: {
                        lang: quote.sheetConfig.langCode,
                    },
                    wallet: {
                        shop_name: quote.sheetConfig.sellerName,
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
                        country_code: quote.sheetConfig.countryCode,
                    },
                    transaction: {
                        amount: quote.total.amount,
                        currency: quote.sheetConfig.currencyCode,
                        set_pending: '1'
                    },
                    custom: {
                        lang: quote.sheetConfig.langCode,
                    },
                    wallet: {
                        shop_name: quote.sheetConfig.sellerName,
                        order_info: quote.displayItems,
                        required_fields: {
                            shipping: ['email', 'phone'],
                            contact: ['postalAddress'],
                        }
                    }
                };

                if (quote.isVirtual) {
                    NovalnetUtility.processApplePay(virtualRequestData);
                } else {
                    NovalnetUtility.processApplePay(requestData);
                }
            },

           /**
             * Init Mini Cart Apple Pay Button
             */
            initMiniCart: function(miniCartQuote) {
                var self = this,
                    minicartBtn = $('#novalnet_applepay_minicartbtn'),
                    minicartDiv = $('#novalnet_applepay_minicartdiv'),
                    ispaymentPage = ($('#novalnet_applepay_checkoutdiv').length) ? true : false;

                if (!miniCartQuote.total.amount) {
                    customerData.reload(['cart'], true);
                }

                if (NovalnetUtility.isApplePayAllowed() && miniCartQuote.isEnabled && !ispaymentPage && miniCartQuote.total.amount) {
                    NovalnetUtility.setClientKey(miniCartQuote.sheetConfig.clientKey);
                    self.initApplepayButton(miniCartQuote.sheetConfig, minicartBtn, minicartDiv);
                    $('#novalnet_applepay_minicartbtn').off('click').on('click', function() {
                        self.applepayPaymentRequest(miniCartQuote);
                    });
                }
            },

            /**
             * Init Product Page Apple Pay Button
             */
            initProductPage: function(isProductVirtual, productPageQuote) {
                var self = this,
                    productPageBtn = $('#novalnet_applepay_productbtn'),
                    productPageBtnDiv = $('#novalnet_applepay_productdiv'),
                    isRequestVirtual = ((isProductVirtual == true && productPageQuote.isVirtual == null) || (isProductVirtual == true && productPageQuote.isVirtual == true) ) ? true : false;

                productPageQuote.isVirtual = isRequestVirtual;
                if (NovalnetUtility.isApplePayAllowed() && productPageQuote.isEnabled) {
                    NovalnetUtility.setClientKey(productPageQuote.sheetConfig.clientKey);
                    self.initApplepayButton(productPageQuote.sheetConfig, productPageBtn, productPageBtnDiv);
                    $('#novalnet_applepay_productbtn').off('click').on('click', function(event) {
                        var form = $('#product_addtocart_form'),
                        validator = form.validation({radioCheckboxClosest: '.nested'});
                        if (!validator.valid()) {
                            event.preventDefault();
                            return;
                        } else {
                            self.addToCart();
                            self.applepayPaymentRequest(productPageQuote);
                        }
                    });
                }
            },

            /**
             * Init Apple Pay Button on Cart Page
             */
            initCartPage: function(cartPageQuote) {
                var self = this,
                    cartPageBtn = $('#novalnet_applepay_cartbtn'),
                    cartPagebtnDiv = $('#novalnet_applepay_cartdiv');

                if (NovalnetUtility.isApplePayAllowed()) {
                    NovalnetUtility.setClientKey(cartPageQuote.sheetConfig.clientKey);
                    self.initApplepayButton(cartPageQuote.sheetConfig, cartPageBtn, cartPagebtnDiv);
                    $('#novalnet_applepay_cartbtn').off('click').on('click', function() {
                        self.applepayPaymentRequest(cartPageQuote);
                    });
                }
            }
        };
    }
);

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
        'novalnetUtilityJs',
        'novalnetPaymentJs'
    ],
    function($, Component, urlBuilder, storage, alert, quote, $t, totals, customerData, totalsDefaultProvider)
    {
        "use strict";
        return Component.extend({
            /**
             * Default's
             */
            defaults: {
                template: {
                    name: "Novalnet_Payment/checkout/novalnetGuestCheckout"
                },
                novalnetApplepay: "APPLEPAY",
                novalnetGooglepay: "GOOGLEPAY"
            },

            /**
             * Initialize function
             */
            initObservable: function () {
                this._super();
                window.addEventListener("hashchange", _.bind(this.handleCheckoutVisibility,this));
                let cart = customerData.get("cart");
                cart.subscribe(this.refreshCheckout, this);
                return this;
            },

            /**
             * Handle's Guest checkout button visibility
             */
            handleCheckoutVisibility: function() {
                this.initGuestCheckoutPage();
            },

            /**
             * Refresh quote grand total on cart change
             */
            refreshCheckout: function() {
                const cart = customerData.get("cart");
                if (cart().summary_count && cart().summary_count > 0) {
                    totalsDefaultProvider.estimateTotals(quote.shippingAddress());
                }
            },

            /**
             * To check the data is JSON
             *
             * @return bool
             */
            isJson: function(data) {
                try {
                    JSON.parse(data);
                    return true;
                } catch (e) {
                    return false;
                }
            },

            /**
             * Returns Grand Total
             *
             * @return mixed
             */
            getGrandTotal: function() {
                if (quote.totals()) {
                    return Math.round(parseFloat(quote.totals().base_grand_total) * 100);
                }
            },

            /**
             * Returns Languge Code
             *
             * @return string
             */
            getLangCode: function() {
                return window.checkoutConfig.payment[this.getCode()].langCode;
            },

            /**
             * Returns Method Code
             *
             * @return string
             */
            getCode: function() {
                return "novalnetApplepay";
            },


            /**
             * Throw's alert on payment error
             */
            throwError: function(message) {
                alert({
                    title: $t("Error"),
                    content: message
                });
            },

            /**
             * Returns display items for payment sheet
             *
             * @return array
             */
            getLineItems: function() {
                let items = totals.totals().items,
                    currencyRate = window.checkoutConfig.quoteData.base_to_quote_rate,
                    lineItem = [],
                    i;

                if (items.length) {
                    for( i = 0; i < items.length; i++ ) {
                        lineItem[i] = {
                                label: items[i].name + " (" + Math.round(parseFloat(items[i].qty)) + " x " + Number.parseFloat(items[i].base_price).toFixed(2) + ")",
                                type: "SUBTOTAL",
                                amount: Math.round((parseFloat(items[i].base_row_total)) * 100) + ""
                            };
                    }
                }

                if (totals.totals().hasOwnProperty("tax_amount")) {
                    let tax = Math.round( (parseFloat(totals.totals().tax_amount) / parseFloat(currencyRate)) * 100);

                    if(parseInt(tax) > 0) {
                        lineItem.push({label: "Tax", type: "SUBTOTAL", amount: tax + ""});
                    }
                }

                if (totals.totals().hasOwnProperty("discount_amount")) {
                    let discountTotal = (Math.round( (parseFloat(totals.totals().discount_amount) / parseFloat(currencyRate)) * 100)).toString(),
                        discount = ((Math.sign(discountTotal)) == -1 ) ? discountTotal.substr(1) : discountTotal;

                    if(parseInt(discount) > 0) {
                        lineItem.push({label: "Discount", type: "SUBTOTAL", amount: "-" + discount});
                    }
                }

                if (totals.totals().hasOwnProperty("shipping_amount") && !quote.isVirtual()) {
                    let shippingTitle = "Shipping";
                    if (totals.totals().hasOwnProperty("total_segments")) {
                        let segments = totals.totals().total_segments;
                        $.each(segments, function (index, value) {
                            if (value.code == "shipping") {
                                shippingTitle = value.title
                                shippingTitle = shippingTitle.replace("Shipping & Handling", "")
                            }
                        });
                    }

                    if (shippingTitle == "") {
                        shippingTitle = "Shipping";
                    }

                    let Shipping = Math.round( (parseFloat(totals.totals().shipping_amount) / parseFloat(currencyRate)) * 100),
                        ShippingItem = {label: shippingTitle, type: "SUBTOTAL", amount: Shipping + ''};
                        lineItem.push(ShippingItem);
                }

                return lineItem;
            },

            /**
             * Built Applepay Payment Request
             *
             * @return object
             */
            applepayPaymentRequest: function() {
                const requestData = window.checkoutConfig.payment.novalnetApplepay,
                    self = this,
                    requestObj = {
                        clientKey: requestData.clientKey,
                        paymentIntent: {
                            merchant: {
                                countryCode: requestData.countryCode,
                                paymentDataPresent: false
                            },
                            transaction: {
                                amount: self.getGrandTotal(),
                                currency: requestData.currencyCode,
                                paymentMethod: self.novalnetApplepay,
                                environment: (requestData.testmode == 1) ? "SANDBOX" : "PRODUCTION",
                                setPendingPayment: requestData.is_pending
                            },
                            order: {
                                paymentDataPresent: false,
                                merchantName: requestData.sellerName,
                                lineItems: self.getLineItems(),
                                billing: {
                                    requiredFields: ["postalAddress"]
                                },
                                shipping: {
                                    requiredFields: ["postalAddress", "phone", "email"],
                                    methodsUpdatedLater: true
                                }
                            },
                            button: {
                                style: requestData.btnTheme,
                                locale: requestData.langCode,
                                type: requestData.btnType,
                                boxSizing: "border-box",
                                dimensions: {
                                    width: 240,
                                    cornerRadius: parseInt(requestData.btnRadius),
                                    height: parseInt(requestData.btnHeight)
                                }
                            },
                            callbacks: {
                                onProcessCompletion: function (response, bookingResult) {
                                    if (response.result.status == "SUCCESS") {
                                    let serviceUrl = urlBuilder.build("/rest/V1/novalnet/payment/placeOrder", {}),
                                        payload = {
                                            paymentData: {
                                                methodCode: "novalnetApplepay",
                                                amount: response.transaction.amount,
                                                doRedirect: response.transaction.doRedirect,
                                                token: response.transaction.token,
                                                cardBrand: response.transaction.paymentData.cardBrand,
                                                lastFour: response.transaction.paymentData.lastFour
                                            },
                                            billingAddress: {},
                                            shippingAddress: {},
                                            shippingMethod: {}
                                        };

                                    if (response.order.hasOwnProperty("billing") && response.order.billing.hasOwnProperty("contact")) {
                                        payload.billingAddress = response.order.billing.contact;
                                    }

                                    if (response.order.hasOwnProperty("shipping") && response.order.shipping.hasOwnProperty("contact")) {
                                        payload.shippingAddress = response.order.shipping.contact;
                                    }

                                    if (response.order.hasOwnProperty("shipping") && response.order.shipping.hasOwnProperty("method")) {
                                        payload.shippingMethod = response.order.shipping.method;
                                    }

                                        storage.post( serviceUrl, JSON.stringify(payload)).done(function(response) {
                                                if (self.isJson(response)) {
                                                    response = JSON.parse(response);
                                                }
                                                $('body').trigger('processStart');
                                                window.location.replace(response.redirectUrl);
                                                bookingResult({status: "SUCCESS", statusText: ""});
                                        }).fail(function(xhr, textStatus, errorThrown) {
                                                $('body').trigger('processStop');
                                                const errMsg = (xhr.responseJSON.message) ? xhr.responseJSON.message : errorThrown;
                                                self.throwError(errMsg);
                                                bookingResult({status: "FAILURE", statusText: errMsg});
                                        });
                                    } else {
                                        $('body').trigger('processStop');
                                        bookingResult({status: "FAILURE", statusText: ""});
                                    }
                                },
                                onShippingContactChange: function(choosenShippingAddress, updatedRequestData) {
                                    let serviceUrl = urlBuilder.build("/rest/V1/novalnet/payment/estimateShippingMethod", {}),
                                        payload = {address : choosenShippingAddress};

                                    new Promise(function(resolve, reject) {
                                        storage.post( serviceUrl, JSON.stringify(payload)).done(function(response) {
                                            response = JSON.parse(response);
                                            resolve(response);
                                        }).fail(function(xhr, textStatus, errorThrown) {
                                            const errMsg = (xhr.responseJSON.message) ? xhr.responseJSON.message : errorThrown;
                                            self.throwError(errMsg);
                                        });
                                    }).then(function (result) {
                                        if (!result.methods.length) {
                                            updatedRequestData({methodsNotFound :"No Shipping Contact Available, please enter a valid contact"});
                                        } else {
                                            updatedRequestData({
                                                amount: result.total.amount,
                                                lineItems: result.displayItems,
                                                methods: result.methods,
                                                defaultIdentifier: result.methods[0].identifier
                                            });
                                        }
                                    });
                                },
                                onShippingMethodChange: function(choosenShippingMethod, updatedRequestData) {
                                    let serviceUrl = urlBuilder.build("/rest/V1/novalnet/payment/applyShippingMethod", {}),
                                        payload = {shippingMethod : choosenShippingMethod};

                                       new Promise(function(resolve, reject) {
                                            storage.post( serviceUrl, JSON.stringify(payload)).done(function(response) {
                                                response = JSON.parse(response);
                                                resolve(response);
                                            }).fail(function(xhr, textStatus, errorThrown) {
                                                const errMsg = (xhr.responseJSON.message) ? xhr.responseJSON.message : errorThrown;
                                                self.throwError(errMsg);
                                            });
                                        }).then(function(result) {
                                            updatedRequestData({
                                                amount: result.total.amount,
                                                lineItems: result.displayItems
                                            });
                                        });
                                },
                                onPaymentButtonClicked: function(clickResult) {
                                    clickResult({status: "SUCCESS"});
                                }
                            }
                        }
                    };

                if (requestData.isVirtual) {
                    requestObj.paymentIntent.transaction.setPendingPayment = true;
                    requestObj.paymentIntent.order.shipping = {requiredFields: ["phone", "email"]};
                }

                return requestObj;
            },

            /**
             * Built Googlepay payment request
             *
             * @return object
             */
            googlePayPaymentRequest: function(btnWidth = 0) {
                let requestData = window.checkoutConfig.payment.novalnetGooglepay,
                    self = this,
                    requestObj = {
                        clientKey: requestData.clientKey,
                        paymentIntent: {
                            merchant: {
                                countryCode: requestData.countryCode,
                                paymentDataPresent: false,
                                partnerId: requestData.partnerId
                            },
                            transaction: {
                                amount: self.getGrandTotal(),
                                currency: requestData.currencyCode,
                                paymentMethod: self.novalnetGooglepay,
                                environment: (requestData.testmode == 1) ? "SANDBOX" : "PRODUCTION",
                                setPendingPayment: requestData.is_pending,
                                enforce3d: requestData.enforce3d
                            },
                            order: {
                                paymentDataPresent: false,
                                merchantName: requestData.sellerName,
                                lineItems: self.getLineItems(),
                                billing: {
                                    requiredFields: ["postalAddress", "phone", "email"]
                                },
                                shipping: {
                                    requiredFields: ["postalAddress", "phone"],
                                    methodsUpdatedLater: true
                                }
                            },
                            button: {
                                locale: requestData.langCode,
                                type: requestData.btnType,
                                boxSizing: "fill",
                                dimensions: {
                                    width: btnWidth,
                                    height: parseInt(requestData.btnHeight)
                                }
                            },
                            callbacks: {
                                onProcessCompletion: function (response, bookingResult) {
                                    if (response.result.status == "SUCCESS") {
                                        let serviceUrl = urlBuilder.build("/rest/V1/novalnet/payment/placeOrder", {}),
                                            payload = {
                                                paymentData: {
                                                    methodCode: "novalnetGooglepay",
                                                    amount: response.transaction.amount,
                                                    doRedirect: response.transaction.doRedirect,
                                                    token: response.transaction.token,
                                                    cardBrand: response.transaction.paymentData.cardBrand,
                                                    lastFour: response.transaction.paymentData.lastFour
                                                },
                                                billingAddress: {},
                                                shippingAddress: {},
                                                shippingMethod: {}
                                            };

                                    if (response.order.hasOwnProperty("billing") && response.order.billing.hasOwnProperty("contact")) {
                                        payload.billingAddress = response.order.billing.contact;
                                    }

                                    if (response.order.hasOwnProperty("shipping") && response.order.shipping.hasOwnProperty("contact")) {
                                        payload.shippingAddress = response.order.shipping.contact;
                                    }

                                    if (response.order.hasOwnProperty("shipping") && response.order.shipping.hasOwnProperty("method")) {
                                        payload.shippingMethod = response.order.shipping.method;
                                    }

                                        storage.post( serviceUrl, JSON.stringify(payload)).done(function(response) {
                                                if (self.isJson(response)) {
                                                    response = JSON.parse(response);
                                                }
                                                $('body').trigger('processStart');
                                                window.location.replace(response.redirectUrl);
                                                bookingResult({status: "SUCCESS", statusText: ""});
                                        }).fail(function(xhr, textStatus, errorThrown) {
                                            $('body').trigger('processStop');
                                            const errMsg = (xhr.responseJSON.message) ? xhr.responseJSON.message : errorThrown;
                                            self.throwError(errMsg);
                                            bookingResult({status: "FAILURE", statusText: errMsg});
                                        });
                                    } else {
                                        $('body').trigger('processStop');
                                        bookingResult({status: "FAILURE", statusText: ""});
                                    }
                                },
                                onShippingContactChange: function(choosenShippingAddress, updatedRequestData) {
                                    let serviceUrl = urlBuilder.build("/rest/V1/novalnet/payment/estimateShippingMethod", {}),
                                        payload = {address : choosenShippingAddress};

                                    new Promise(function(resolve, reject) {
                                        storage.post( serviceUrl, JSON.stringify(payload)).done(function(response) {
                                            response = JSON.parse(response);
                                            resolve(response);
                                        }).fail(function(xhr, textStatus, errorThrown) {
                                            const errMsg = (xhr.responseJSON.message) ? xhr.responseJSON.message : errorThrown;
                                            self.throwError(errMsg);
                                        });
                                    }).then(function (result) {
                                        if (!result.methods.length) {
                                            updatedRequestData({methodsNotFound :"No Shipping Contact Available, please enter a valid contact"});
                                        } else {
                                            updatedRequestData({
                                                amount: result.total.amount,
                                                lineItems: result.displayItems,
                                                methods: result.methods,
                                                defaultIdentifier: result.methods[0].identifier
                                            });
                                        }
                                    });
                                },
                                onShippingMethodChange: function(choosenShippingMethod, updatedRequestData) {
                                    let serviceUrl = urlBuilder.build("/rest/V1/novalnet/payment/applyShippingMethod", {}),
                                        payload = {shippingMethod : choosenShippingMethod};

                                       new Promise(function(resolve, reject) {
                                            storage.post( serviceUrl, JSON.stringify(payload)).done(function(response) {
                                                response = JSON.parse(response);
                                                resolve(response);
                                            }).fail(function(xhr, textStatus, errorThrown) {
                                                const errMsg = (xhr.responseJSON.message) ? xhr.responseJSON.message : errorThrown;
                                                self.throwError(errMsg);
                                            });
                                        }).then(function(result) {
                                            updatedRequestData({
                                                amount: result.total.amount,
                                                lineItems: result.displayItems
                                            });
                                        });
                                },
                                onPaymentButtonClicked: function(clickResult) {
                                    clickResult({status: "SUCCESS"});
                                }
                            }
                        }
                    };

                if (quote.isVirtual()) {
                    requestObj.paymentIntent.transaction.setPendingPayment = true;
                    delete requestObj.paymentIntent.order.shipping;
                }

                return requestObj;
            },

            /**
             * Init guest checkout payment buttons
             *
             * @see Novalnet_Payment/checkout/novalnetGuestCheckout
             */
            initGuestCheckoutPage: function() {
                const self = this,
                    currentURL = window.location.href,
                    pattern = new RegExp("/checkout/#payment"),
                    paymentPage = pattern.test(currentURL),
                    googlePayInstance = NovalnetPayment().createPaymentObject(),
                    applePayInstance = NovalnetPayment().createPaymentObject();

                googlePayInstance.setPaymentIntent(self.googlePayPaymentRequest());
                googlePayInstance.isPaymentMethodAvailable(function(canShowGooglepay) {
                    if (canShowGooglepay && paymentPage !== true && window.checkoutConfig.payment.novalnetGooglepay.guest_page.novalnetGooglepay) {
                        $("#novalnet_googlepay_guest_checkout").empty();
                        googlePayInstance.addPaymentButton("#novalnet_googlepay_guest_checkout");
                        $("#novalnet_guest_checkoutdiv").css({"display" : "block"});
                        const btnWidth = ($(window).width() > 768) ? "63%" : "100%";
                        $("#novalnet_googlepay_guest_checkout").find("button").css({'width': btnWidth});
                        $(window).resize(function() {
                            if ($(window).width() > 768) {
                                $("#novalnet_googlepay_guest_checkout").find("button").css({'width': "63%"});
                            } else {
                                $("#novalnet_googlepay_guest_checkout").find("button").css({'width': "100%"});
                            }
                        });
                    } else {
                        $("#novalnet_guest_checkoutdiv").css({"display" : "none"});
                    }
                });

                applePayInstance.setPaymentIntent(self.applepayPaymentRequest());
                applePayInstance.isPaymentMethodAvailable(function(canShowApplepay) {
                    if (canShowApplepay && paymentPage !== true && window.checkoutConfig.payment.novalnetGooglepay.guest_page.novalnetApplepay) {
                        $("#novalnet_applepay_guest_checkout").empty();
                        applePayInstance.addPaymentButton('#novalnet_applepay_guest_checkout');
                        $("#novalnet_guest_checkoutdiv").css({"display" : "block"});
                        const btnWidth = ($(window).width() > 768) ? "63%" : "100%";
                        $("#novalnet_applepay_guest_checkout").find("apple-pay-button").css({'width': btnWidth});
                        $(window).resize(function() {
                            if ($(window).width() > 768) {
                                $("#novalnet_applepay_guest_checkout").find("apple-pay-button").css({'width': "63%"});
                            } else {
                                $("#novalnet_applepay_guest_checkout").find("apple-pay-button").css({'width': "100%"});
                            }
                        });

                    } else {
                        $("#novalnet_guest_checkoutdiv").css({"display" : "none"});
                    }
                });
            }
        });
    }
);

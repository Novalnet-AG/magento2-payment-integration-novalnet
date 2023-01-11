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
        'novalnetUtilityJs',
        'novalnetPaymentJs'
    ], function(
        $,
        urlBuilder,
        storage,
        mageAlert,
        $t,
        validation,
        customerData
    ) {
        "use strict";
         return {
             defaults: {
                novalnetApplepay: "APPLEPAY",
                novalnetGooglepay: "GOOGLEPAY",
                displayBlock: {"display" : "block"},
                displayNone: {"display" : "none"},
             },

            /**
             * Throw's Alert on Ajax Error
             */
            throwError: function(message) {
                mageAlert({
                    title: $t("Error"),
                    content: message
                });
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
             * Returns cart items from quote
             *
             * @param callback
             * @see Novalnet/Payment/view/frontend/templates/checkout/ProductPageShortcut.phtml
             * @see Novalnet/Payment/view/frontend/templates/checkout/CartPageShortcut.phtml
             * @see Novalnet/Payment/view/frontend/templates/checkout/MinicartShortcut.phtml
             */
             getCartItems: function(callback) {
                const getCartUrl =  urlBuilder.build("/rest/V1/novalnet/payment/getCart", {}),
                    self = this;

                storage.post( getCartUrl, null , false).done(function(response) {
                    if (self.isJson(response)) {
                        response = JSON.parse(response);
                    }

                    callback(response);
                }).fail(function(xhr, textStatus, errorThrown) {
                    const errMsg = (xhr.responseJSON.message) ? xhr.responseJSON.message : errorThrown;
                    self.throwError(errMsg);
                });
             },

            /**
             * Add product to cart
             *
             * @param form
             * @return void
             */
            addToCart: function(form) {
                const addToCartUrl =  urlBuilder.build("/rest/V1/novalnet/payment/addToCart", {}),
                    request = form.serialize(),
                    payload = {data : request},
                    self = this;

                storage.post( addToCartUrl, JSON.stringify(payload)).done(function(response) {
                    customerData.reload(["cart"], true);
                }).fail(function(xhr, textStatus, errorThrown) {
                    const errMsg = (xhr.responseJSON.message) ? xhr.responseJSON.message : errorThrown;
                    self.throwError(errMsg);
                });
            },

            /**
             * Get Initial Params
             *
             * @param productId
             * @param callback
             * @see Novalnet/Payment/view/frontend/templates/checkout/ProductPageShortcut.phtml
             */
            getQuoteValues: function(productId, callback) {
                const serviceUrl = urlBuilder.build("/rest/V1/novalnet/payment/getProductPageParams", {}),
                    payload = {data : {product_id: productId}},
                    self = this;

                storage.post(serviceUrl, JSON.stringify(payload)).done(function(response) {
                    if (self.isJson(response)) {
                        response = JSON.parse(response);
                    }

                    callback(response);
                }).fail(function(xhr, textStatus, errorThrown) {
                    const errMsg = (xhr.responseJSON.message) ? xhr.responseJSON.message : errorThrown;
                    self.throwError(errMsg);
                });
            },

            /**
             * Built ApplePay Payment Request
             *
             * @param requestData
             * @return object
             */
            applepayPaymentRequest: function(requestData, btnWidth = 0, productPage = false) {
                let self = this,
                    requestObj = {
                    clientKey: requestData.sheetConfig.clientKey,
                    paymentIntent: {
                        merchant: {
                            countryCode: requestData.sheetConfig.countryCode,
                            paymentDataPresent: false
                        },
                        transaction: {
                            amount: requestData.total.amount,
                            currency: requestData.sheetConfig.currencyCode,
                            paymentMethod: self.defaults.novalnetApplepay,
                            environment: requestData.sheetConfig.novalnetApplepay.environment,
                            setPendingPayment: requestData.is_pending
                        },
                        order: {
                            paymentDataPresent: false,
                            merchantName: requestData.sheetConfig.novalnetApplepay.sellerName,
                            lineItems: requestData.displayItems,
                            billing: {
                                 requiredFields: ["postalAddress"]
                            },
                            shipping: {
                                requiredFields: ["postalAddress", "phone", "email"],
                                methodsUpdatedLater: true
                            }
                        },
                        button: {
                            style: requestData.sheetConfig.novalnetApplepay.btnTheme,
                            locale: requestData.sheetConfig.langCode,
                            type: requestData.sheetConfig.novalnetApplepay.btnType,
                            boxSizing: "border-box",
                            dimensions: {
                                width: btnWidth,
                                cornerRadius: parseInt(requestData.sheetConfig.novalnetApplepay.btnRadius),
                                height: parseInt(requestData.sheetConfig.novalnetApplepay.btnHeight)
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
                                            billingAddress: response.order.billing.contact,
                                            shippingAddress: (response.order.shipping.contact) ? response.order.billing.contact : {},
                                            shippingMethod: (response.order.shipping.method) ? response.order.shipping.method : {}
                                        };

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
                                if (productPage) {
                                    const productForm = $("#product_addtocart_form"),
                                        validator = productForm.validation({radioCheckboxClosest: ".nested"});

                                    if (!validator.valid()) {
                                        clickResult({status: "FAILURE"});
                                    } else {
                                        clickResult({status: "SUCCESS"});
                                        self.addToCart(productForm);
                                    }
                                } else {
                                    clickResult({status: "SUCCESS"});
                                }
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
             * GooglePay request data
             *
             * @param requestData
             * @param buttonWidth
             * @param productPage
             * @return object
             */
            googlePayPaymentRequest: function(requestData, buttonWidth = 0, productPage = false) {
                let self = this,
                    requestObj = {
                    clientKey: requestData.sheetConfig.clientKey,
                    paymentIntent: {
                        merchant: {
                            countryCode: requestData.sheetConfig.countryCode,
                            paymentDataPresent: false,
                            partnerId: requestData.sheetConfig.novalnetGooglepay.partnerId
                        },
                        transaction: {
                            amount: requestData.total.amount,
                            currency: requestData.sheetConfig.currencyCode,
                            paymentMethod: self.defaults.novalnetGooglepay,
                            environment: requestData.sheetConfig.novalnetGooglepay.environment,
                            setPendingPayment: requestData.is_pending,
                            enforce3d: requestData.sheetConfig.novalnetGooglepay.enforce3d
                        },
                        order: {
                            paymentDataPresent: false,
                            merchantName: requestData.sheetConfig.novalnetGooglepay.sellerName,
                            lineItems: requestData.displayItems,
                            billing: {
                                requiredFields: ["postalAddress", "phone", "email"]
                            },
                            shipping: {
                                requiredFields: ["postalAddress", "phone"],
                                methodsUpdatedLater: true
                            }
                        },
                        button: {
                            style: requestData.sheetConfig.novalnetGooglepay.btnTheme,
                            locale: requestData.sheetConfig.langCode,
                            type: requestData.sheetConfig.novalnetGooglepay.btnType,
                            boxSizing: "fill",
                            dimensions: {
                                width: buttonWidth,
                                height: parseInt(requestData.sheetConfig.novalnetGooglepay.btnHeight)
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
                                            billingAddress: response.order.billing.contact,
                                            shippingAddress: (response.order.shipping.contact) ? response.order.billing.contact : {},
                                            shippingMethod: (response.order.shipping.method) ? response.order.shipping.method : {}
                                        };

                                    storage.post( serviceUrl, JSON.stringify(payload)).done(function(response) {
                                            if (self.isJson(response)) {
                                                response = JSON.parse(response);
                                            }
                                            $('body').trigger('processStart');
                                            window.location.replace(response.redirectUrl);
                                            bookingResult({status: "SUCCESS", statusText: ''});
                                    }).fail(function(xhr, textStatus, errorThrown) {
                                            $('body').trigger('processStop');
                                            const errMsg = (xhr.responseJSON.message) ? xhr.responseJSON.message : errorThrown;
                                            self.throwError(errMsg);
                                            bookingResult({status: "FAILURE", statusText: errMsg});
                                    });
                                } else {
                                    $('body').trigger('processStop');
                                    bookingResult({status: "FAILURE", statusText: ''});
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
                                if (productPage) {
                                    const productForm = $("#product_addtocart_form"),
                                        validator = productForm.validation({radioCheckboxClosest: ".nested"});

                                    if (!validator.valid()) {
                                        clickResult({status: "FAILURE"});
                                    } else {
                                        self.addToCart(productForm);
                                        clickResult({status: "SUCCESS"});
                                    }
                                } else {
                                    clickResult({status: "SUCCESS"});
                                }
                            }
                        }
                    }
                };

                if (requestData.isVirtual) {
                    requestObj.paymentIntent.transaction.setPendingPayment = true;
                    delete requestObj.paymentIntent.order.shipping;
                }

                return requestObj;
            },

           /**
             * Init Mini Cart express checkout Button
             *
             * @param miniCartQuote
             * @see Novalnet/Payment/view/frontend/templates/checkout/MinicartShortcut.phtml
             */
            initMiniCart: function(miniCartQuote) {
                const self = this,
                    googlePayMinicartDiv = $("#novalnet_googlepay_minicartdiv"),
                    googlePayMinicartContainer = $("#nn_googlepay_minicart"),
                    applePayMinicartDiv = $("#novalnet_applepay_minicartdiv"),
                    applePayMinicartContainer = $("#nn_applepay_minicart"),
                    googlePayInstance = NovalnetPayment().createPaymentObject(),
                    applePayInstance = NovalnetPayment().createPaymentObject();

                googlePayInstance.setPaymentIntent(self.googlePayPaymentRequest(miniCartQuote));
                googlePayInstance.isPaymentMethodAvailable(function(canShowGooglePay) {
                    if (canShowGooglePay && miniCartQuote.isEnabled.novalnetGooglepay) {
                        googlePayMinicartContainer.empty();
                        googlePayInstance.addPaymentButton("#nn_googlepay_minicart");
                        googlePayMinicartDiv.css(self.defaults.displayBlock);
                        $("#nn_googlepay_cart").find("button").css({'width': '100%'});
                    } else {
                        googlePayMinicartDiv.css(self.defaults.displayNone);
                    }
                });

                applePayInstance.setPaymentIntent(self.applepayPaymentRequest(miniCartQuote));
                applePayInstance.isPaymentMethodAvailable(function(canShowApplePay) {
                    if (canShowApplePay && miniCartQuote.isEnabled.novalnetApplepay) {
                        applePayMinicartContainer.empty();
                        applePayInstance.addPaymentButton("#nn_applepay_minicart");
                        applePayMinicartDiv.css(self.defaults.displayBlock);
                        $("#nn_applepay_minicart").find("apple-pay-button").css({'width': '100%'});
                    } else {
                        applePayMinicartDiv.css(self.defaults.displayNone);
                    }
                });
            },

            /**
             * Init Product Page express checkout Button
             *
             * @param isProductVirtual
             * @param productPageQuote
             * @see Novalnet/Payment/view/frontend/templates/checkout/ProductPageShortcut.phtml
             */
            initProductPage: function(isProductVirtual, productPageQuote) {
                const self = this,
                    isRequestVirtual = ((isProductVirtual == true && productPageQuote.isVirtual == null) || (isProductVirtual == true && productPageQuote.isVirtual == true) ) ? true : false,
                    googlePayProductDiv = $("#novalnet_googlepay_productdiv"),
                    googlePayProductContainer = $("#nn_googlepay_product"),
                    applePayProductDiv = $("#novalnet_applepay_productdiv"),
                    applePayProductContainer = $("#nn_applepay_product"),
                    googlePayInstance = NovalnetPayment().createPaymentObject(),
                    applePayInstance = NovalnetPayment().createPaymentObject();

                productPageQuote.isVirtual = isRequestVirtual;
                googlePayInstance.setPaymentIntent(self.googlePayPaymentRequest(productPageQuote, 0, true));
                googlePayInstance.isPaymentMethodAvailable(function(canShowGooglePay) {
                    if (canShowGooglePay && productPageQuote.isEnabled.novalnetGooglepay) {
                        googlePayProductContainer.empty();
                        googlePayInstance.addPaymentButton("#nn_googlepay_product");
                        googlePayProductDiv.css(self.defaults.displayBlock);
                        const btnWidth = ($(window).width() <= 768) ? "100%" : "49%";
                        $("#nn_googlepay_product").find("button").css({'width': btnWidth});
                        $( window ).resize(function() {
                            if ($(window).width() <= 768) {
                                $("#nn_googlepay_product").find("button").css({'width': "100%"});
                            } else {
                                $("#nn_googlepay_product").find("button").css({'width': "49%"});
                            }
                        });
                    } else {
                        googlePayProductDiv.css(self.defaults.displayNone);
                    }
                });

                applePayInstance.setPaymentIntent(self.applepayPaymentRequest(productPageQuote, 0, true));
                applePayInstance.isPaymentMethodAvailable(function(canShowApplePay) {
                    if (canShowApplePay && productPageQuote.isEnabled.novalnetApplepay) {
                        applePayProductContainer.empty();
                        applePayInstance.addPaymentButton("#nn_applepay_product", true);
                        applePayProductDiv.css(self.defaults.displayBlock);
                        const btnWidth = ($(window).width() <= 768) ? "100%" : "49%";
                        $("#nn_applepay_product").find("apple-pay-button").css({'width': btnWidth});
                        $( window ).resize(function() {
                            if ($(window).width() <= 768) {
                                $("#nn_applepay_product").find("apple-pay-button").css({'width': "100%"});
                            } else {
                                $("#nn_applepay_product").find("apple-pay-button").css({'width': "49%"});
                            }
                        });

                    } else {
                        applePayProductDiv.css(self.defaults.displayNone);
                    }
                });
            },

            /**
             * Init cart page express checkout button
             *
             * @param cartPageQuote
             * @see Novalnet/Payment/view/frontend/templates/checkout/CartPageShortcut.phtml
             */
            initCartPage: function(cartPageQuote) {
                const self = this,
                    googlePayCartDiv = $("#novalnet_googlepay_cartdiv"),
                    googlePayCartContainer = $("#nn_googlepay_cart"),
                    applePayCartDiv = $("#novalnet_applepay_cartdiv"),
                    applePayCartContainer = $("#nn_applepay_cart"),
                    googlePayInstance = NovalnetPayment().createPaymentObject(),
                    applePayInstance = NovalnetPayment().createPaymentObject();

                googlePayInstance.setPaymentIntent(self.googlePayPaymentRequest(cartPageQuote));
                googlePayInstance.isPaymentMethodAvailable(function(canShowGooglePay) {
                    if (canShowGooglePay) {
                        googlePayCartContainer.empty();
                        googlePayInstance.addPaymentButton("#nn_googlepay_cart");
                        googlePayCartDiv.css(self.defaults.displayBlock);
                        $("#nn_googlepay_cart").find("button").css({'width': '100%'});
                    } else {
                        googlePayCartDiv.css(self.defaults.displayNone);
                    }
                });

                applePayInstance.setPaymentIntent(self.applepayPaymentRequest(cartPageQuote));
                applePayInstance.isPaymentMethodAvailable(function(canShowApplePay) {
                    if (canShowApplePay) {
                        applePayCartContainer.empty();
                        applePayInstance.addPaymentButton("#nn_applepay_cart");
                        applePayCartDiv.css(self.defaults.displayBlock);
                        $("#nn_applepay_cart").find("apple-pay-button").css({'width': '100%'});
                    } else {
                        applePayCartDiv.css(self.defaults.displayNone);
                    }
                });
            }
        };
    }
);

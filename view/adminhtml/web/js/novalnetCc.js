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
    "jquery",
    "Magento_Ui/js/modal/alert",
    "mage/translate",
    "jquery/ui",
    "novalnetUtilityJs"
    ],
    function ($, alert, $t) {
        'use strict';
        var nnButton = false;

        $.widget('mage.novalnetCcFormJs', {
            /**
             * Returns payment method signature key
             */
            initIframe: function () {
                var paymentCode = this.options.code;
                var iframeParams = this.options.iframeParams;
                NovalnetUtility.setClientKey(iframeParams.client_key);
                var request = {
                    callback: {
                        on_success: function (result) {
                            if (result) {
                                if (result['do_redirect'] != 0) {
                                    alert({
                                        content: $t('Card type not accepted, try using another card type')
                                    });
                                } else {
                                    $('#' + paymentCode + '_pan_hash').val(result['hash']);
                                    $('#' + paymentCode + '_unique_id').val(result['unique_id']);
                                    eval($('#nn_chk_button').val());
                                    $('button[onclick="getHash()"]').each(function () {
                                        var nnButtonContent = $(this).attr('onclick');
                                        $('#nn_chk_button').val(nnButtonContent);
                                        this.removeAttribute('onclick');
                                        $(this).attr('onclick', 'order.submit()');
                                        $(this).trigger('onclick');
                                    });
                                }
                            }
                        },
                        on_error: function (result) {
                            alert({
                                content: result['error_message']
                            });
                        },
                        on_show_overlay:  function () {
                            $('#novalnet_iframe').addClass("novalnet-challenge-window-overlay");
                        },
                        on_hide_overlay:  function () {
                            $('#novalnet_iframe').removeClass("novalnet-challenge-window-overlay");
                        },
                    },
                    iframe: {
                        id: "novalnet_iframe",
                        inline: this.options.inlineForm,
                        style: {
                            container: $('#nn_cc_standard_style_css').val(),
                            input: $('#nn_cc_standard_style_input').val(),
                            label: $('#nn_cc_standard_style_label').val()
                        },
                        text: {
                            lang : iframeParams.lang,
                            error: $t("Your credit card details are invalid"),
                            card_holder : {
                                label: $t("Card holder name"),
                                place_holder: $t("Name on card"),
                                error: $t("Please enter the valid card holder name")
                            },
                            card_number : {
                                label: $t("Card number"),
                                place_holder: $t("XXXX XXXX XXXX XXXX"),
                                error: $t("Please enter the valid card number")
                            },
                            expiry_date : {
                                label: $t("Expiry date"),
                                error: $t("Please enter the valid expiry month / year in the given format")
                            },
                            cvc : {
                                label: $t("CVC/CVV/CID"),
                                place_holder: $t("XXX"),
                                error: $t("Please enter the valid CVC/CVV/CID")
                            }
                        }
                    },
                    customer: {
                        first_name: this.options.billing.firstname,
                        last_name: this.options.billing.lastname,
                        email: this.options.billing.email,
                        billing: {
                            street: this.getStreet(this.options.billing.street),
                            city: this.options.billing.city,
                            zip: this.options.billing.postcode,
                            country_code: this.options.billing.country_id
                        },
                        shipping: this.getShipping(this.options)
                    },
                    transaction: {
                        amount: this.options.amount,
                        currency: this.options.config.currencyCode,
                        test_mode: this.options.config.testmode,
                        enforce_3d: this.options.config.enforce_3d
                    },
                    custom: {
                        lang : iframeParams.lang
                    }
                };
                if ($('#novalnet_iframe').length) {
                    NovalnetUtility.createCreditCardForm(request);
                }

                this.setButtonAttr();
            },

            setButtonAttr:function () {
                $('#submit_order_top_button').attr('onclick', 'getHash()');
                $('.order-totals .actions button').attr('onclick', 'getHash()');
                if ($('input[name="payment[method]"]:checked').val() === 'novalnetCc') {
                    $('button[onclick="order.submit()"]').each(function () {
                        var nnButtonContent = $(this).attr('onclick');
                        $('#nn_chk_button').val(nnButtonContent);
                        this.removeAttribute('onclick');
                        this.stopObserving('click');
                        $(this).attr('onclick', 'getHash()');
                    });
                }
            },

            getShipping:function(options) {
                if (options.billing.country_id == options.shipping.country_id &&
                    this.getStreet(options.billing.street) == this.getStreet(options.shipping.street) &&
                    options.billing.city == options.shipping.city &&
                    options.billing.postcode == options.shipping.postcode) {
                        var shipping = {same_as_billing: 1};
                } else {
                    var shipping = {
                        first_name: options.shipping.firstname,
                        last_name: options.shipping.lastname,
                        street: this.getStreet(options.shipping.street),
                        city: options.shipping.city,
                        zip: options.shipping.postcode,
                        country_code: options.shipping.country_id
                    };
                }
                return shipping;
            },

            getStreet: function(streetArray) {
                var i, street = '';
                for(i=0; i<streetArray.length; i++) {
                    if(streetArray[i] != '') {
                        street += streetArray[i] + ' ';
                    }
                }
                return street.trim();
            },

            _create:function () {
                var self = this;
                $('#nniframe').ready(function () {
                    setTimeout(function () {
                        self.initIframe();
                    }, 500);
                });
            }
        });

        function getHash()
        {
            if ($('input[name="payment[method]"]:checked').val() === 'novalnetCc') {
                if ($('#novalnetCc_pan_hash').val() == '') {
                    NovalnetUtility.getPanHash();
                }
            } else {
                eval($('#nn_chk_button').val());
                $('button[onclick="getHash()"]').each(function () {
                    var nnButtonContent = $(this).attr('onclick');
                    $('#nn_chk_button').val(nnButtonContent);
                    this.removeAttribute('onclick');
                    $(this).attr('onclick', 'order.submit()');
                    $(this).trigger('onclick');
                });
            }
        }

        window.getHash = getHash;
        $('input[name="payment[method]"], #p_method_novalnetCc').on('change', function () {
            if (this.value === 'novalnetCc') {
                $('#submit_order_top_button').attr('onclick', 'getHash()');
                $('.order-totals .actions button').attr('onclick', 'getHash()');
                $('button[onclick="order.submit()"]').each(function () {
                    var nnButtonContent = $(this).attr('onclick');
                    $('#nn_chk_button').val(nnButtonContent);
                    this.removeAttribute('onclick');
                    this.stopObserving('click');
                    $(this).attr('onclick', 'getHash()');
                });
            }
        });

        return $.mage.novalnetCcFormJs;
    }
);

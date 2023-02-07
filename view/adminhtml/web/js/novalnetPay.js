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
    'mage/url',
    'mage/storage',
    "jquery/ui",
    "novalnetPaymentFormJs",
    "novalnetUtilityJs"
    ],
    function ($, alert, $t, url, storage) {
        'use strict';
        let novalnetPaymentForm = new NovalnetPaymentForm();

        $.widget('mage.novalnetPayJs', {
            initPaymentForm: function () {
                const self = this,
                    paymentFormRequestObj = {
                    iframe : '#novalnetPaymentIFrame',
                    initForm: {
                        creditCard: {
                            text : {
                                error: "Your credit card details are invalid",
                                card_holder: {
                                    label: "Card holder name",
                                    place_holder: "Name on CreditCard from post",
                                    error: "Please enter the valid card holder name"
                                },
                                card_number: {
                                    label: "Card number",
                                    place_holder: "XXXX XXXX XXXX XXXX",
                                    error: "Please enter the valid card number"
                                },
                                expiry_date: {
                                    label: "Expiry date",
                                    error: "Please enter the valid expiry month / year in the given format"
                                },
                                cvc: {
                                    label: "CVC/CVV/CID",
                                    place_holder: "XXX",
                                    error: "Please enter the valid CVC/CVV/CID"
                                }
                            }
                        },
                        uncheckPayments: true,
                        showButton: false,
                    }
                };

                novalnetPaymentForm.selectedPayment((data) => {
                    $("#p_method_novalnetPay").prop("checked", true);
                    self.setButtonAttr();
                });

                novalnetPaymentForm.validationResponse((data) => {
                    novalnetPaymentForm.initiate(paymentFormRequestObj);
                });

                novalnetPaymentForm.getMPaymentResponse( (response) => {
                    response = self.isJson(response) ? JSON.parse(response) : response;
                    if (response.result.status == "SUCCESS") {
                        if (response.booking_details.do_redirect && response.booking_details.do_redirect == 1) {
                            alert({
                                title: $t('Error'),
                                content: $t('Card type not accepted, try using another card type')
                            });

                            return false;
                        }

                        $('#novalnetPay_payment_data').val(JSON.stringify(response));
                        $('#nn_can_submit_form').val(1);
                        eval($('#nn_chk_button').val());
                        $('button[onclick="getNnPaymentData()"]').each(function () {
                            var nnButtonContent = $(this).attr('onclick');
                            $('#nn_chk_button').val(nnButtonContent);
                            this.removeAttribute('onclick');
                            $(this).attr('onclick', 'order.submit()');
                            $(this).trigger('onclick');
                        });
                    } else {
                        $('#nn_can_submit_form').val(0);
                        $('#novalnetPay_payment_data').val("");
                        alert({
                            title: $t('Error'),
                            content: $t(response.result.message)
                        });
                    }
                });
            },

            isJson: (data) => {
                try {
                    JSON.parse(data);
                    return true;
                } catch (e) {
                    return false;
                }
            },

            setButtonAttr:function () {
                $('#submit_order_top_button').attr('onclick', 'getNnPaymentData()');
                $('.order-totals .actions button').attr('onclick', 'getNnPaymentData()');
                if ($('input[name="payment[method]"]:checked').val() === 'novalnetPay') {
                    $('button[onclick="order.submit()"]').each( function () {
                        var nnButtonContent = $(this).attr('onclick');
                        $('#nn_chk_button').val(nnButtonContent);
                        this.removeAttribute('onclick');
                        this.stopObserving('click');
                        $(this).attr('onclick', 'getNnPaymentData()');
                    });
                }
            },

            _create:function () {
                var self = this;

                if ($('input[name="payment[method]"]').length) {
                    $('input[name="payment[method]"]').on('click', function () {
                        if (this.id !== "p_method_novalnetPay") {
                            novalnetPaymentForm.uncheckPayment();
                        }
                        $("#payment_form_novalnetPay").css({"display" : "block"});
                    });

                    $('input[name="payment[method]"]').on('change', function () {
                        if (this.id !== "p_method_novalnetPay") {
                            novalnetPaymentForm.uncheckPayment();
                        }
                        $("#payment_form_novalnetPay").css({"display" : "block"});
                    });
                }

                $("#p_method_novalnetPay").closest(".admin__field-option").css({"display" : "none"});

                setTimeout( () => {
                    self.initPaymentForm();
                }, 500);
            }
        });

        function getNnPaymentData()
        {
            if ($('input[name="payment[method]"]:checked').val() === 'novalnetPay') {
                if ($('#nn_can_submit_form').val() != 1) {
                    novalnetPaymentForm.getMPaymentRequest();
                } else {
                    $('#nn_can_submit_form').val(0);
                }
            } else {
                eval($('#nn_chk_button').val());
                $('button[onclick="getNnPaymentData()"]').each( function () {
                    var nnButtonContent = $(this).attr('onclick');
                    $('#nn_chk_button').val(nnButtonContent);
                    this.removeAttribute('onclick');
                    $(this).attr('onclick', 'order.submit()');
                    $(this).trigger('onclick');
                });
            }
        }

        window.getNnPaymentData = getNnPaymentData;
        $('input[name="payment[method]"], #p_method_novalnetPay').on('change', function() {
            if (this.value === 'novalnetPay') {
                $('#submit_order_top_button').attr('onclick', 'getNnPaymentData()');
                $('.order-totals .actions button').attr('onclick', 'getNnPaymentData()');
                $('button[onclick="order.submit()"]').each( function () {
                    var nnButtonContent = $(this).attr('onclick');
                    $('#nn_chk_button').val(nnButtonContent);
                    this.removeAttribute('onclick');
                    this.stopObserving('click');
                    $(this).attr('onclick', 'getNnPaymentData()');
                });
            }
        });

        return $.mage.novalnetPayJs;
    }
);

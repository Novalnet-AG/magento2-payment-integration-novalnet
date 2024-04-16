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
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote'
    ],
    function (jQuery, urlBuilder, storage, errorProcessor, fullScreenLoader, quote) {
        'use strict';

        return function () {
            fullScreenLoader.startLoader();

            let serviceUrl = urlBuilder.build("/rest/V1/novalnet/payment/getRedirectURL", {}),
                payLoad = {data: {quote_id: quote.getQuoteId()}};

            return storage.post(serviceUrl, JSON.stringify(payLoad));
        };
    }
);
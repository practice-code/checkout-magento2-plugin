define([
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'CheckoutCom_Magento2/js/view/payment/utilities',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/translate',
        'jquery/ui'
    ],
    function ($, Component, Utilities, FullScreenLoader, AdditionalValidators, __) {

        'use strict';

        // Fix billing address missing.
        window.checkoutConfig.reloadOnBillingAddress = true;

        const METHOD_ID = 'checkoutcom_apm';

        return Component.extend(
            {
                defaults: {
                    template: 'CheckoutCom_Magento2/payment/' + METHOD_ID + '.phtml'
                },

                /**
                 * @returns {exports}
                 */
                initialize: function () {
                    this._super();
                    this.getApmList();
                },

                /**
                 * @returns {string}
                 */
                getCode: function () {
                    return METHOD_ID;
                },

                /**
                 * @returns {string}
                 */
                getValue: function(field) {
                    return Utilities.getValue(METHOD_ID, field);
                },

                /**
                 * @returns {void}
                 */
                initWidget: function () {
                    $.ajax({
                        type: "POST",
                        url: Utilities.getUrl('apm/display'),
                        success: function(data) {
                            $('#apm-container')
                            .append(data.html)
                            .accordion({
                                heightStyle: 'content',
                                animate: {
                                    duration: 200
                                }
                            })
                            .show();
                        },
                        error: function (request, status, error) {
                            console.log(error);
                        }
                    });
                },

                /**
                 * @returns {array}
                 */
                getApmList: function () {
                    this.apmList = this.getValue('apm').split(',');
                },

                /**
                 * @returns {void}
                 */
                placeOrder: function () {
                    var $form = $('#cko-apm-form'),
                        data = {};

                    // Start the loader
                    FullScreenLoader.startLoader();

                    // Validate before submission
                    if ($form.valid() && AdditionalValidators.validate()) {

                        // Serialize form.
                        $form.serializeArray().forEach(function (e) {
                            data[e.name] = e.value;
                        });

                        Utilities.placeOrder(data, this.handleSuccess, this.handleFail);

                    } else {
                        this.handleFail(data); //@todo: imrpove needed
                        FullScreenLoader.stopLoader();
                    }
                }
            }
        );
    }
);
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'mage/url',
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Payment/js/model/credit-card-validation/validator'
    ],
    function ($, url, Component) {
        'use strict';

        //TODO make decision here to switch which view to load

        return Component.extend({
            redirectAfterPlaceOrder: false,

            defaults: {
                template: 'Cardinity_Payment/payment/cardinity-external-form'
            },

            getCode: function () {
                return 'cardinity';
            },

            isActive: function () {
                return true;
            },

            validate: function () {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },

            afterPlaceOrder: function () {
                location.replace(url.build('cardinity/payment/redirect'));
            }
        });
    }
);

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

        
        /*console.log("TEST Method renderer");
        console.log(window.checkoutConfig.customData);
        console.log(window.checkoutConfig.externalEnabled);
        console.log("TEST END");*/

        var displayTemplte;

        if(window.checkoutConfig.externalEnabled == 1){
            displayTemplte = 'Cardinity_Payment/payment/cardinity-external-form';
        }else{
            displayTemplte = 'Cardinity_Payment/payment/cardinity-form';
        }
        

        return Component.extend({
            redirectAfterPlaceOrder: false,

            defaults: {
                template: displayTemplte
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

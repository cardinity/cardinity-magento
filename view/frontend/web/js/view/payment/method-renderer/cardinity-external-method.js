/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'mage/url',
    ],
    function ($, url, Component) {
        'use strict';

        return Component.extend({
            redirectAfterPlaceOrder: false,

            defaults: {
                template: 'Cardinity_Payment/payment/cardinity-external-form'
            },

            getValue: {
                amount:71.00,
                lastName:"Doe", 
                age:46
            },

            getCode: function () {
                return 'cardinity';
            },
            
            isActive: function () {
                return true;
            },

            validate: function () {
        
                //var $form = $('#' + this.getCode() + '-form');
                //return $form.validation() && $form.validation('isValid');
                return true; 
            },

            afterPlaceOrder: function () {
                location.replace(url.build('cardinity/payment/redirect'));
            }
        });
    }
);

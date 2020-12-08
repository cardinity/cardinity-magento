/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';

        rendererList.push(
            {
                type: 'cardinity',
                component: 'Cardinity_Payment/js/view/payment/method-renderer/cardinity-external-method'
            }
        );

        return Component.extend({});
    }
);

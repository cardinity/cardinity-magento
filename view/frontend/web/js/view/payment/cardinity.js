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
                component: 'Cardinity_Magento/js/view/payment/method-renderer/cardinity-method'
            }
        );

        return Component.extend({});
    }
);

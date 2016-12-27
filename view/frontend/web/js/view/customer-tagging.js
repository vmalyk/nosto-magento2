/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'nostojs',
], function (Component, customerData, nostojs) {
    'use strict';

    return Component.extend({
        initialize: function () {
            this._super();
            this.customerTagging = customerData.get('customer-tagging');
        },
        sendTagging: function() {
            if (typeof nostojs === 'function') {
                nostojs(function(api){
                    api.sendTagging("nosto_customer_tagging");
                });
            }
        }
    });
});
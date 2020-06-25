function getLastPart(url) {
    var parts = url.split("/");
    return (url.lastIndexOf('/') !== url.length - 1
            ? parts[parts.length - 1]
            : parts[parts.length - 2]);
}

(function ($) {
    'use strict';
    $(function () {
        $("#faw_checkout").data()
        var mode = null
        var orderDesc = null;

        $("#faw_checkout").click(function () {
			//V1 --> deprecated calling
            /*loadFawryPluginPopup(merchant, locale, merchantRefNum,
                    productsJSON, customerName, mobile, email, mode, customerId, orderDesc,
                    orderExpiry);*/
			//V2
			var mkkChargeRequest = {};
			mkkChargeRequest.customerName = customerName;
			mkkChargeRequest.order = {};	
			mkkChargeRequest.customer = {};	
			mkkChargeRequest.customer = customer;	
			mkkChargeRequest.customerMobile = mkkChargeRequest.customer.customerMobile;
			mkkChargeRequest.customerEmail = mkkChargeRequest.customer.customerEmail;
			mkkChargeRequest.language= 'en-gb';
			mkkChargeRequest.merchantCode= merchant;
			mkkChargeRequest.merchantRefNumber= merchantRefNum; 	
			mkkChargeRequest.order = productsJSON; 
			mkkChargeRequest.order.orderExpiry = orderExpiry;
			mkkChargeRequest.locale = locale;
			console.log(mkkChargeRequest);
			FawryPay.checkout(mkkChargeRequest,callBack,failCallBack);
        });

        $("#faw_checkout").trigger('click');

    }); //end $(function() {
})(jQuery);

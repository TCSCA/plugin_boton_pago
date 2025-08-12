jQuery(document).ready(($) => {
    var enabled = $('#woocommerce_pago_c2p_enabled');

    if (!config_admin.consumer_key || !config_admin.account_number || !config_admin.commerce_name || !config_admin.consumer_secret || !config_admin.confirmation_key || !config_admin.phone_p2c || !config_admin.document || !config_admin.bank) {
    
        var currentURL = window.location.href;
        var newURL = currentURL.replace(/page=wc-settings&tab=checkout&section=pago_c2p$/, 'page=payment_gateway_menu');
        $('.woo-nav-tab-wrapper').after(`<div class="notice notice-warning"><p>Por favor, termine la configuración del plugin para poder configurar el método de pago, <a href="${newURL}">puede hacerlo aquí</a>.</p></div>`);
        
        enabled.prop('disabled', true);
        // title.prop('disabled', true);
        // bank.prop('disabled', true);
        // document_type.prop('disabled', true);
        // document.prop('disabled', true);
        // phone_number.prop('disabled', true);
        $("[name='save']").prop('disabled', true);

    } else {
        
        // validateFields();
    
        // $('#mainform :input').on('input', function() {
        //     validateFields();
        // });
    
        // function validateFields(){
        //     if(!title.val() || !bank.val() || !document_type.val() || !document.val() || !phone_number.val()){
        //         enabled.prop('disabled', true);
        //         enabled.prop('checked', false);
        //     } else if (enabled.prop('disabled')) {
        //         enabled.prop('disabled', false);
        //     }
        // }
    }
});
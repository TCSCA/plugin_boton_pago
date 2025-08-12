jQuery(document).ready(function($) {
    function jqueryAfterAjax_pagoManual() {
        //Documento
        var documento_pago_manual = $('#documento_pago_manual');
        documento_pago_manual.attr('maxlength', '10');
        documento_pago_manual.attr('minlength', '6');
        var documento_pago_manual_pattern = /^[0-9]+$/;
        var documento_pago_manual_regex = new RegExp(documento_pago_manual_pattern);
        documento_pago_manual.on('keypress', function(event) {
            if (documento_pago_manual.val().match(/[^0-9]/)) {
                console.log('Input already contains non-numeric characters. Keypress allowed.');
                return true; 
            }
            const isNumeric = documento_pago_manual_regex.test(event.key);
            console.log('Keypress:', event.key, 'isNumeric:', isNumeric);
            return isNumeric;
        });

        //telefono
        var tlf_client_pago_manual = $('#tlf_client_pago_manual');
        tlf_client_pago_manual.attr('maxlength', '7');
        tlf_client_pago_manual.attr('minlength', '7');
        var tlf_client_pago_manual_pattern = /^[0-9]+$/;
        var tlf_client_pago_manual_regex = new RegExp(tlf_client_pago_manual_pattern);
        tlf_client_pago_manual.on('keypress', function(event) {
            if (tlf_client_pago_manual.val().match(/[^0-9]/)) {
                console.log('Input already contains non-numeric characters. Keypress allowed.');
                return true; 
            }
            const isNumeric = tlf_client_pago_manual_regex.test(event.key);
            console.log('Keypress:', event.key, 'isNumeric:', isNumeric);
            return isNumeric;
        });

        //ref_value
        var ref_value = $('#ref_value_pago_manual');
        ref_value.attr('maxlength', '12');
        // ref_value.attr('minlength', '6');
        var ref_value_pattern = /^[0-9]+$/;
        var ref_value_regex = new RegExp(ref_value_pattern);
        ref_value.on('keypress', function(event) {
            if (ref_value.val().match(/[^0-9]/)) {
                console.log('Input already contains non-numeric characters. Keypress allowed.');
                return true; 
            }
            const isNumeric = ref_value_regex.test(event.key);
            console.log('Keypress:', event.key, 'isNumeric:', isNumeric);
            return isNumeric;
        });

        //monto
        var monto_pago_manual = $('#amount_pago_manual');
        monto_pago_manual.attr('maxlength', '12');
        monto_pago_manual.attr('disabled', 'disabled');
    }
  
    // Attach the event handler to the AJAX success callback
    $(document).on('ajaxSuccess', jqueryAfterAjax_pagoManual);
  
    // Call the function initially to handle the initial page load
    jqueryAfterAjax_pagoManual();
  });
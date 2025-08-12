jQuery(document).ready(function($) {
    function jqueryAfterAjax_p2c() {
        //Documento
        var documento_p2c = $('#documento_p2c');
        documento_p2c.attr('maxlength', '10');
        documento_p2c.attr('minlength', '6');
        var documento_p2c_pattern = /^[0-9]+$/;
        var documento_p2c_regex = new RegExp(documento_p2c_pattern);
        documento_p2c.on('keypress', function(event) {
            if (documento_p2c.val().match(/[^0-9]/)) {
                console.log('Input already contains non-numeric characters. Keypress allowed.');
                return true; 
            }
            const isNumeric = documento_p2c_regex.test(event.key);
            console.log('Keypress:', event.key, 'isNumeric:', isNumeric);
            return isNumeric;
        });

        //telefono
        var tlf_client_p2c = $('#tlf_client_p2c');
        tlf_client_p2c.attr('maxlength', '7');
        tlf_client_p2c.attr('minlength', '7');
        var tlf_client_p2c_pattern = /^[0-9]+$/;
        var tlf_client_p2c_regex = new RegExp(tlf_client_p2c_pattern);
        tlf_client_p2c.on('keypress', function(event) {
            if (tlf_client_p2c.val().match(/[^0-9]/)) {
                console.log('Input already contains non-numeric characters. Keypress allowed.');
                return true; 
            }
            const isNumeric = tlf_client_p2c_regex.test(event.key);
            console.log('Keypress:', event.key, 'isNumeric:', isNumeric);
            return isNumeric;
        });

        //ref_value
        var ref_value = $('#ref_value');
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
        var monto_p2c = $('#amount_p2c');
        monto_p2c.attr('maxlength', '12');
        monto_p2c.attr('disabled', 'disabled');
    }
  
    // Attach the event handler to the AJAX success callback
    $(document).on('ajaxSuccess', jqueryAfterAjax_p2c);
  
    // Call the function initially to handle the initial page load
    jqueryAfterAjax_p2c();
  });
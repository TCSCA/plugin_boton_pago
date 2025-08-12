jQuery(document).ready(function($) {
    function jqueryAfterAjax_transfer() {
        //Documento
        var documento_trf = $('#documento_trf');
        documento_trf.attr('maxlength', '10');
        documento_trf.attr('minlength', '6');
        var documento_trf_pattern = /^[0-9]+$/;
        var documento_trf_regex = new RegExp(documento_trf_pattern);
        documento_trf.on('keypress', function(event) {
            if (documento_trf.val().match(/[^0-9]/)) {
                console.log('Input already contains non-numeric characters. Keypress allowed.');
                return true; 
            }
            const isNumeric = documento_trf_regex.test(event.key);
            console.log('Keypress:', event.key, 'isNumeric:', isNumeric);
            return isNumeric;
        });

        //telefono
        var tlf_client_trf = $('#tlf_client_trf');
        tlf_client_trf.attr('maxlength', '7');
        tlf_client_trf.attr('minlength', '7');
        var tlf_client_trf_pattern = /^[0-9]+$/;
        var tlf_client_trf_regex = new RegExp(tlf_client_trf_pattern);
        tlf_client_trf.on('keypress', function(event) {
            if (tlf_client_trf.val().match(/[^0-9]/)) {
                console.log('Input already contains non-numeric characters. Keypress allowed.');
                return true; 
            }
            const isNumeric = tlf_client_trf_regex.test(event.key);
            console.log('Keypress:', event.key, 'isNumeric:', isNumeric);
            return isNumeric;
        });

        //ref_value_trf
        var ref_value_trf = $('#ref_value_trf');
        ref_value_trf.attr('maxlength', '12');
        // ref_value_trf.attr('minlength', '6');
        var ref_value_trf_pattern = /^[0-9]+$/;
        var ref_value_trf_regex = new RegExp(ref_value_trf_pattern);
        ref_value_trf.on('keypress', function(event) {
            if (ref_value_trf.val().match(/[^0-9]/)) {
                console.log('Input already contains non-numeric characters. Keypress allowed.');
                return true; 
            }
            const isNumeric = ref_value_trf_regex.test(event.key);
            console.log('Keypress:', event.key, 'isNumeric:', isNumeric);
            return isNumeric;
        });

        //monto
        var amount_trf = $('#amount_trf');
        amount_trf.attr('maxlength', '12');
        amount_trf.attr('disabled', 'disabled');
    }
  
    $(document).on('ajaxSuccess', jqueryAfterAjax_transfer);
  
    jqueryAfterAjax_transfer();
  });
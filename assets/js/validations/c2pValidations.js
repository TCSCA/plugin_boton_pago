jQuery(document).ready(function($) {
    function jqueryAfterAjax_c2p() {
        //Documento
        var documento_c2p = $('#documento_c2p');
        documento_c2p.attr('maxlength', '10');
        documento_c2p.attr('minlength', '6');
        var documento_c2p_pattern = /^[0-9]+$/;
        var documento_c2p_regex = new RegExp(documento_c2p_pattern);
        documento_c2p.on('keypress', function(event) {
            if (documento_c2p.val().match(/[^0-9]/)) {
                console.log('Input already contains non-numeric characters. Keypress allowed.');
                return true; 
            }
            const isNumeric = documento_c2p_regex.test(event.key);
            console.log('Keypress:', event.key, 'isNumeric:', isNumeric);
            return isNumeric;
        });

        //telefono
        var tlf_client_c2p = $('#tlf_client_c2p');
        tlf_client_c2p.attr('maxlength', '7');
        tlf_client_c2p.attr('minlength', '7');
        var tlf_client_c2p_pattern = /^[0-9]+$/;
        var tlf_client_c2p_regex = new RegExp(tlf_client_c2p_pattern);
        tlf_client_c2p.on('keypress', function(event) {
            if (tlf_client_c2p.val().match(/[^0-9]/)) {
                console.log('Input already contains non-numeric characters. Keypress allowed.');
                return true; 
            }
            const isNumeric = tlf_client_c2p_regex.test(event.key);
            console.log('Keypress:', event.key, 'isNumeric:', isNumeric);
            return isNumeric;
        });

        //otp
        var otp_c2p = $('#otp_c2p');
        otp_c2p.attr('maxlength', '8');
        otp_c2p.attr('minlength', '6');
        var otp_c2p_pattern = /^[0-9]+$/;
        var otp_c2p_regex = new RegExp(otp_c2p_pattern);
        otp_c2p.on('keypress', function(event) {
            if (otp_c2p.val().match(/[^0-9]/)) {
                console.log('Input already contains non-numeric characters. Keypress allowed.');
                return true; 
            }
            const isNumeric = otp_c2p_regex.test(event.key);
            console.log('Keypress:', event.key, 'isNumeric:', isNumeric);
            return isNumeric;
        });

        //motivo
        var motivo_c2p = $('#motivo_c2p');
        motivo_c2p.attr('maxlength','30');
        motivo_c2p.attr('minlength','3');
        var motivo_c2p_pattern = /^[a-zA-ZñÑáÁéÉíÍóÓúÚ0-9 ]+$/;
        var motivo_c2p_regex = new RegExp(motivo_c2p_pattern);
        motivo_c2p.on('keypress', (event) => {
            return motivo_c2p_regex.test(event.key);
        });
        motivo_c2p.on('input', (event) => {
            var value = motivo_c2p.val();
            if(value.length > 0){
                if(value[0] === ' '){
                    motivo_c2p.val(value.trim());
                    document.getElementById(motivo_c2p).setSelectionRange(0,0);
                } else {
                    value = value.replace(/  +/g, ' ');
                    motivo_c2p.val(value);
                }
            }
        });
        motivo_c2p.on('focusout', (event) => {
            var value = motivo_c2p.val();
            motivo_c2p.val(value.trim());
        });
        motivo_c2p.on('paste', (event) => {
            return motivo_c2p_regex.test(event.originalEvent.clipboardData.getData('text'));
        });

        //monto
        var monto_c2p = $('#monto_c2p');
        monto_c2p.attr('maxlength', '12');
        monto_c2p.attr('disabled', 'disabled');

		// $('#monto_c2p').val('0,00');
	
		// $('#monto_c2p').mask('#.##0,00', {
		// 	reverse: true,
		// 	onChange: function(value, event) {
		// 		if (value === '0' || value === '' || value.length < 3) {
		// 			$('#monto_c2p').val('0,00');
		// 		}
		// 	},
		// 	onKeyPress: function(value, event, nextInsertPosition) {
		// 		console.log($('#monto_c2p').val());
		// 		console.log('value -> ' + value);
		// 		console.log('value.length -> ' + value.length);
		// 		if (value.length > 4 && value !== '0') {
		// 			var processedValue = value.replace(/^0+(?=\d)/, '');
		// 			$('#monto_c2p').val(processedValue);
		// 			console.log($('#monto_c2p').val());
		// 		}
		// 	}
		// });

        // $orderTotalDiv = $('div.order-total'); // Select the "order-total" div using jQuery
        // $priceSpan = $orderTotalDiv.find('span.woocommerce-Price-amount'); // Find the "woocommerce-Price-amount" span within the div
        // $priceText = $priceSpan.text();

        // console.log($priceText);

        // monto_c2p.val($priceText);

    }
  
    // Attach the event handler to the AJAX success callback
    $(document).on('ajaxSuccess', jqueryAfterAjax_c2p);
  
    // Call the function initially to handle the initial page load
    jqueryAfterAjax_c2p();
  });
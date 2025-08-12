jQuery(document).ready(function($) {
    var realCardNumber = '';
    function jqueryAfterAjax_tarjeta() {

        var type = $('#card');
        type.prop('selectedIndex', 0);
        type.trigger('change');

        var tipoCuenta = $('#account_type');
        var pinCampo = $('#pin');
        var label_tipo = $('label[for="' + tipoCuenta.attr('id') + '"]');
        var label_pin = $('label[for="' + pinCampo.attr('id') + '"]');

        function toggleFields() {
            if (type.val() === 'C') {
                label_tipo.hide();
                label_pin.hide();
                tipoCuenta.hide();
                tipoCuenta.removeAttr('required');
                pinCampo.hide();
                pinCampo.removeAttr('required');
                pinCampo.val('0000');
            } else {
                label_tipo.show();
                label_pin.show();
                tipoCuenta.show();
                tipoCuenta.attr('required', 'required');
                pinCampo.show();
                pinCampo.attr('required', 'required');
                pinCampo.val('');
            }
        }

        type.on('change', toggleFields);

        toggleFields();

        //nombre
        var account_owner = $('#account_owner');
        account_owner.attr('maxlength','100');
        account_owner.attr('minlength','3');
        var account_owner_pattern = /^[a-zA-ZñÑáÁéÉíÍóÓúÚ ]+$/;
        var account_owner_regex = new RegExp(account_owner_pattern);
        account_owner.on('keypress', (event) => {
            return account_owner_regex.test(event.key);
        });
        account_owner.on('input', (event) => {
            var value = account_owner.val();
            if(value.length > 0){
                if(value[0] === ' '){
                    account_owner.val(value.trim());
                    document.getElementById(account_owner).setSelectionRange(0,0);
                } else {
                    value = value.replace(/  +/g, ' ');
                    account_owner.val(value);
                }
            }
        });
        account_owner.on('focusout', (event) => {
            var value = account_owner.val();
            account_owner.val(value.trim());
        });
        account_owner.on('paste', (event) => {
            return account_owner_regex.test(event.originalEvent.clipboardData.getData('text'));
        });

        //Numero de tarjeta
        var real_card_number = $('<input>', {
            type: 'hidden',
            id: 'real_card_number',
            name: 'real_card_number'
        });
        $('form[name="checkout"]').append(real_card_number);

        var card_number = $('#card_number');
        card_number.attr('maxlength', '19');
        card_number.attr('minlength', '15');
        var card_number_pattern = /^[0-9]+$/;
        var card_number_regex = new RegExp(card_number_pattern);

        card_number.on('keypress', function(event) {
            if (card_number.val().match(/[^0-9]/)) {
                return true; 
            }
            const isNumeric = card_number_regex.test(event.key);
            return isNumeric;
        });
        card_number.on("focusout", function() {
            let cardValue = card_number.val();
            if(!cardValue.includes('●')){
                real_card_number.val(cardValue);
                realCardNumber = cardValue;
                if (cardValue.length >= 15) {
                    let maskedCardNumberText = cardValue.slice(0, 6) + '●'.repeat(cardValue.length - 10) + cardValue.slice(-4);
                    card_number.val(maskedCardNumberText);
                }
            }
        });

        card_number.on("focusin", function() {
            card_number.val(realCardNumber);
        });

        var documentSelect = $('#document');
        var typeSelect = $('#document_type');

        documentSelect.on('change', function() {
            const selectedOption = $(this).val();

            if (selectedOption === 'CI') {
                typeSelect.find('option[value="J"]').hide();
                typeSelect.find('option[value="C"]').hide();
                typeSelect.find('option[value="G"]').hide();

            } else {
                typeSelect.find('option[value="J"]').show();
                typeSelect.find('option[value="C"]').show();
                typeSelect.find('option[value="G"]').show();            
            }
        });

        //Documento
        var identification_document = $('#identification_document');
        identification_document.attr('maxlength', '9');
        identification_document.attr('minlength', '6');
        var identification_document_pattern = /^[0-9]+$/;
        var identification_document_regex = new RegExp(identification_document_pattern);
        identification_document.on('keypress', function(event) {
            if (identification_document.val().match(/[^0-9]/)) {
                console.log('Input already contains non-numeric characters. Keypress allowed.');
                return true; 
            }
            const isNumeric = identification_document_regex.test(event.key);
            console.log('Keypress:', event.key, 'isNumeric:', isNumeric);
            return isNumeric;
        });

        //cvc
        var cvc = $('#cvc');
        cvc.attr('maxlength', '4');
        cvc.attr('minlength', '3');
        var cvc_pattern = /^[0-9]+$/;
        var cvc_regex = new RegExp(cvc_pattern);
        cvc.on('keypress', function(event) {
            if (cvc.val().match(/[^0-9]/)) {
                console.log('Input already contains non-numeric characters. Keypress allowed.');
                return true; 
            }
            const isNumeric = cvc_regex.test(event.key);
            console.log('Keypress:', event.key, 'isNumeric:', isNumeric);
            return isNumeric;
        });

        //pin
        var pin = $('#pin');
        pin.attr('maxlength', '6');
        pin.attr('minlength', '4');
        var pin_pattern = /^[0-9]+$/;
        var pin_regex = new RegExp(pin_pattern);
        pin.on('keypress', function(event) {
            if (pin.val().match(/[^0-9]/)) {
                console.log('Input already contains non-numeric characters. Keypress allowed.');
                return true; 
            }
            const isNumeric = pin_regex.test(event.key);
            console.log('Keypress:', event.key, 'isNumeric:', isNumeric);
            return isNumeric;
        });

        //expiration_date
        // var expiration_date = $('#expiration_date');
        // expiration_date.attr('maxlength', '5');
        // var expiration_date_pattern = /^[0-9]+$/;
        // var expiration_date_regex = new RegExp(expiration_date_pattern);
        // expiration_date.on('keypress', function(event) {
        //     if (expiration_date.val().match(/[^0-9]/)) {
        //         console.log('Input already contains non-numeric characters. Keypress allowed.');
        //         return true; 
        //     }
        //     const isNumeric = expiration_date_regex.test(event.key);
        //     console.log('Keypress:', event.key, 'isNumeric:', isNumeric);
        //     return isNumeric;
        // });

        //expiration_date
        var expiration_date = $('#expiration_date');
        expiration_date.attr('maxlength', '5');

        expiration_date.on('input', function(event) {
            var numericValue = expiration_date.val().replace(/[^0-9]/g, '');
            var formattedValue = numericValue.slice(0, 2) + (numericValue.length > 2 ? "/" + numericValue.slice(2) : '');
            formattedValue = formattedValue.slice(0, 5);
            expiration_date.val(formattedValue);
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
        var amount_tarjeta = $('#amount_tarjeta');
        amount_tarjeta.attr('maxlength', '12');
        amount_tarjeta.attr('disabled', 'disabled');

		// $('#amount_tarjeta').val('0,00');
	
		// $('#amount_tarjeta').mask('#.##0,00', {
		// 	reverse: true,
		// 	onChange: function(value, event) {
		// 		if (value === '0' || value === '' || value.length < 3) {
		// 			$('#amount_tarjeta').val('0,00');
		// 		}
		// 	},
		// 	onKeyPress: function(value, event, nextInsertPosition) {
		// 		console.log($('#amount_tarjeta').val());
		// 		console.log('value -> ' + value);
		// 		console.log('value.length -> ' + value.length);
		// 		if (value.length > 4 && value !== '0') {
		// 			var processedValue = value.replace(/^0+(?=\d)/, '');
		// 			$('#amount_tarjeta').val(processedValue);
		// 			console.log($('#amount_tarjeta').val());
		// 		}
		// 	}
		// });

    }
  
    // Attach the event handler to the AJAX success callback
    $(document).on('ajaxSuccess', jqueryAfterAjax_tarjeta);
  
    // Call the function initially to handle the initial page load
    jqueryAfterAjax_tarjeta();
  });
jQuery(document).ready(function($) {
    var validate_exceptions = [
        "Backspace",
        "Tab"
    ]

    var consumer_key_bancaribe = $('#payment_gateway_bancaribe_consumer_key');
    var consumer_secret_bancaribe = $('#payment_gateway_bancaribe_consumer_secret');
    var consumer_key_creditcard = $('#payment_gateway_creditcard_consumer_key');
    var consumer_secret_creditcard = $('#payment_gateway_creditcard_consumer_secret');
    var confirmation_key = $('#payment_gateway_confirmation_key');
    var phone_p2c = $('#payment_gateway_phone_p2c');
    var document_number = $('#payment_gateway_document');
    var document_type = $('#payment_gateway_document_type');
    var bank = $('#payment_gateway_bank');
    var commerce_name = $('#payment_gateway_commerce_name');
    var account_number = $('#payment_gateway_account_number');
    var hash = $('#payment_gateway_hash');
    var ccCheck = $("#payment_gateway_creditcard_checkbox");
    var sending = false;

    var tableBody = consumer_key_bancaribe.closest('tbody');
    var tableRowKey = consumer_key_bancaribe.closest('tr');
    var tableRowSecret = consumer_secret_bancaribe.closest('tr');
    var tableRowHash = hash.closest('tr');
    var tableRowP2c = phone_p2c.closest('tr');
    var tableRowAccountNumber = account_number.closest('tr');
    var rowIndexKey = tableRowKey.index();
    var rowIndexSecret = tableRowSecret.index();
    var rowIndexHash = tableRowHash.index();
    var rowIndexP2c = tableRowP2c.index();
    var rowIndexAccountNumber = tableRowAccountNumber.index();

    var tableRowKeyCc = consumer_key_creditcard.closest('tr');
    var tableRowSecretCc = consumer_secret_creditcard.closest('tr');
    var rowIndexKeyCc = tableRowKeyCc.index();
    var rowIndexSecretCc = tableRowSecretCc.index();

    consumer_key_creditcard.hide();
    consumer_secret_creditcard.hide();
    consumer_key_creditcard.val('');
    consumer_secret_creditcard.val('');
    tableBody.children('tr').eq(rowIndexKeyCc).hide();
    tableBody.children('tr').eq(rowIndexSecretCc).hide();

    function toggleBank() {
        if (bank.find('option:selected').text().includes('0114')) {
            tableBody.children('tr').eq(rowIndexKey).show();
            tableBody.children('tr').eq(rowIndexSecret).show();
            tableBody.children('tr').eq(rowIndexHash).show();
            tableBody.children('tr').eq(rowIndexP2c).show();
            tableBody.children('tr').eq(rowIndexAccountNumber).show();
            phone_p2c.show();
            phone_p2c.attr('required', 'required');
            account_number.show();
            account_number.attr('required', 'required');
            hash.show();
            hash.attr('required', 'required');
            hash.val('');
            consumer_key_bancaribe.show();
            consumer_key_bancaribe.attr('required', 'required');
            consumer_key_bancaribe.val('');
            consumer_secret_bancaribe.show();
            consumer_secret_bancaribe.attr('required', 'required');
            consumer_secret_bancaribe.val('');
        } else if(!bank.val()){
            tableBody.children('tr').eq(rowIndexKey).hide();
            tableBody.children('tr').eq(rowIndexSecret).hide();
            tableBody.children('tr').eq(rowIndexHash).hide();
            hash.hide();
            hash.removeAttr('required');
            consumer_key_bancaribe.hide();
            consumer_key_bancaribe.removeAttr('required');
            consumer_secret_bancaribe.hide();
            consumer_secret_bancaribe.removeAttr('required');
        } else {
            tableBody.children('tr').eq(rowIndexKey).hide();
            tableBody.children('tr').eq(rowIndexSecret).hide();
            tableBody.children('tr').eq(rowIndexHash).hide();
            tableBody.children('tr').eq(rowIndexP2c).show();
            tableBody.children('tr').eq(rowIndexAccountNumber).show();
            phone_p2c.show();
            phone_p2c.attr('required', 'required');
            account_number.show();
            account_number.attr('required', 'required');
            hash.hide();
            hash.removeAttr('required');
            consumer_key_bancaribe.hide();
            consumer_key_bancaribe.removeAttr('required');
            consumer_secret_bancaribe.hide();
            consumer_secret_bancaribe.removeAttr('required');
        }
    }

    bank.on('change', toggleBank);

    toggleBank();

    ccCheck.prop('checked', false);
    ccCheck.removeAttr('checked');

    ccCheck.on('change', function(e){
        if($(this).is(':checked')){
            tableBody.children('tr').eq(rowIndexKeyCc).show();
            tableBody.children('tr').eq(rowIndexSecretCc).show();
            consumer_key_creditcard.show();
            consumer_secret_creditcard.show();
        }else{
            tableBody.children('tr').eq(rowIndexKeyCc).hide();
            tableBody.children('tr').eq(rowIndexSecretCc).hide();
            consumer_key_creditcard.hide();
            consumer_secret_creditcard.hide();
            consumer_key_creditcard.val('');
            consumer_secret_creditcard.val('');
        }
    });

    // Función para verificar los campos de entrada

    account_number.attr('maxlength', '20');
    var account_number_pattern = /^[0-9]+$/;
    var account_number_regex = new RegExp(account_number_pattern);
    account_number.on('keypress', function(event) {
        if (account_number.val().match(/[^0-9]/)) {
            console.log('Input already contains non-numeric characters. Keypress allowed.');
            return true; 
        }
        const isNumeric = account_number_regex.test(event.key);
        console.log('Keypress:', event.key, 'isNumeric:', isNumeric);
        return isNumeric;
    });

    phone_p2c.attr('maxlength', '11');
    phone_p2c.attr('minlength', '11');
    var phone_p2c_pattern = /^[0-9]+$/;
    var phone_p2c_regex = new RegExp(phone_p2c_pattern);
    phone_p2c.on('keypress', function(event) {
        if (phone_p2c.val().match(/[^0-9]/)) {
            console.log('Input already contains non-numeric characters. Keypress allowed.');
            return true; 
        }
        const isNumeric = phone_p2c_regex.test(event.key);
        console.log('Keypress:', event.key, 'isNumeric:', isNumeric);
        return isNumeric;
    });

    verificarInputs();

    function verificarInputs() {
        if(bank.find('option:selected').text().includes('0114')){
            if (!phone_p2c.val() || !bank.val() || !confirmation_key.val() || !document_number.val() || !account_number.val() || !commerce_name.val() 
                || !hash.val() || !consumer_key_bancaribe.val() || !consumer_secret_bancaribe.val()) {

                $("[name='submit']").prop('disabled', true);

            } else {
                if(!sending) $("[name='submit']").prop('disabled', false);
            }
        }else{
            if (!phone_p2c.val() || !bank.val() || !confirmation_key.val() || !document_number.val() || !account_number.val() || !commerce_name.val()) {

                $("[name='submit']").prop('disabled', true);

            } else {
                if(!sending) $("[name='submit']").prop('disabled', false);
            }
        }
    }

    // Evento input para detectar cambios en los inputs
    $('#payment_gateway_bancaribe_consumer_key, #payment_gateway_bancaribe_consumer_secret, #payment_gateway_creditcard_consumer_key, #payment_gateway_creditcard_consumer_secret, #payment_gateway_confirmation_key, #payment_gateway_phone_p2c, #payment_gateway_document, #payment_gateway_commerce_name, #payment_gateway_account_number, #payment_gateway_hash').on('input', function() {
        verificarInputs();
    });

    // Evento click para enviar los datos mediante AJAX
    $('.submit').click(function(e) {
        sending = true;
        $("[name='submit']").prop('disabled', true);
        e.preventDefault();
        var data = {
            check: ccCheck.val(),
            consumer_key: consumer_key_bancaribe.val(),
            consumer_secret: consumer_secret_bancaribe.val(),
            consumer_key_creditcard: consumer_key_creditcard.val(),
            consumer_secret_creditcard: consumer_secret_creditcard.val(),
            confirmation_key: confirmation_key.val(),
            phone_p2c: phone_p2c.val(),
            document_type: document_type.val(),
            document: document_number.val(),
            bank: bank.val(),
            account_number: account_number.val(),
            commerce_name: commerce_name.val(),
            hash: hash.val(),
        }

        // Hacer la solicitud AJAX
        var url = SolicitudesAjax.url;
        $.ajax({
            type: "POST",
            url: url,
            data: {
                action: "saveAdminData",
                nonce: SolicitudesAjax.seguridad,
                data: data,
            },
            success: function(data){
                if(data.success){
                    console.log(data);
                    alert("Datos actualizados");
                    location.reload();
                }else{
                    alert(data.data);
                    location.reload();
                }
                
            },
            error: function(error) {
                console.log('error');
                console.log(error);
                console.log('Error:', error.statusText);
                if(error.statusText === "Unauthorized"){
                    alert("Error de conexión");
                }
                sending = false;
                $("[name='submit']").prop('disabled', false);
            }
        });
    });

});
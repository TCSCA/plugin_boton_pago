jQuery(document).ready(($) => {
    var namePatternNoSpaces = /^[a-zA-ZñÑáÁéÉíÍóÓúÚ]+(\s[a-zA-ZñÑáÁéÉíÍóÓúÚ]+)*$/;
    
    //company name
    var company_name = $('#wpforms-1183-field_1');
    company_name.attr('maxlength','30');
    var company_name_pattern = /^[a-zA-ZñÑáÁéÉíÍóÓúÚ ]+$/;
    var company_name_regex = new RegExp(company_name_pattern);
    company_name.on('keypress', (event) => {
        return company_name_regex.test(event.key);
    });
    company_name.on('input', (event) => {
        var value = company_name.val();
        if(value.length > 0){
            if(value[0] === ' '){
                company_name.val(value.trim());  
                document.getElementById('wpforms-1183-field_1').setSelectionRange(0,0);
            } else {
                value = value.replace(/  +/g, ' ');
                company_name.val(value);
            }
        }
    });
    company_name.on('focusout', (event) => {
        var value = company_name.val();
        company_name.val(value.trim());
    });
    company_name.on('paste', (event) => {
        return company_name_regex.test(event.originalEvent.clipboardData.getData('text'));
    });

    //rif
    var rif = $('#wpforms-1183-field_2');
    rif.attr('maxlength','10');
    var rif_pattern = /^[0-9]+$/;
    var rif_regex = new RegExp(rif_pattern);
    rif.on('keypress', (event) => {
        return rif_regex.test(event.key);
    });

    //name
    var name = $('#wpforms-1183-field_3');
    name.attr('maxlength','30');
    var name_pattern = /^[a-zA-ZñÑáÁéÉíÍóÓúÚ ]+$/;
    var name_regex = new RegExp(name_pattern);
    name.on('keypress', (event) => {
        return name_regex.test(event.key);
    });
    name.on('input', (event) => {
        var value = name.val();
        if(value.length > 0){
            if(value[0] === ' '){
                name.val(value.trim());  
                document.getElementById('wpforms-1183-field_3').setSelectionRange(0,0);
            } else {
                value = value.replace(/  +/g, ' ');
                name.val(value);
            }
        }
    });
    name.on('focusout', (event) => {
        var value = name.val();
        name.val(value.trim());
    });
    name.on('paste', (event) => {
        return name_regex.test(event.originalEvent.clipboardData.getData('text'));
    });

    //last_name
    var last_name = $('#wpforms-1183-field_4');
    last_name.attr('maxlength','30');
    var last_name_pattern = /^[a-zA-ZñÑáÁéÉíÍóÓúÚ ]+$/;
    var last_name_regex = new RegExp(last_name_pattern);
    last_name.on('keypress', (event) => {
        return last_name_regex.test(event.key);
    });
    last_name.on('input', (event) => {
        var value = last_name.val();
        if(value.length > 0){
            if(value[0] === ' '){
                last_name.val(value.trim());  
                document.getElementById('wpforms-1183-field_4').setSelectionRange(0,0);
            } else {
                value = value.replace(/  +/g, ' ');
                last_name.val(value);
            }
        }
    });
    last_name.on('focusout', (event) => {
        var value = last_name.val();
        last_name.val(value.trim());
    });
    last_name.on('paste', (event) => {
        return last_name_regex.test(event.originalEvent.clipboardData.getData('text'));
    });

    //email
    var email = $('#wpforms-1183-field_5');
    email.attr('maxlength','150');
    var email_pattern = /^[a-zA-Z0-9@._-]+$/;
    var email_regex = new RegExp(email_pattern);
    email.on('keypress', (event) => {
        return email_regex.test(event.key);
    });

    //personal_email
    var personal_email = $('#wpforms-1183-field_10');
    personal_email.attr('maxlength','150');
    var personal_email_pattern = /^[a-zA-Z0-9@._-]+$/;
    var personal_email_regex = new RegExp(personal_email_pattern);
    personal_email.on('keypress', (event) => {
        return personal_email_regex.test(event.key);
    });

    //phone
    var phone = $('#wpforms-1183-field_11');
    phone.attr('maxlength','11');
    var phone_pattern = /^[0-9]+$/;
    var phone_regex = new RegExp(phone_pattern);
    phone.on('keypress', (event) => {
        return phone_regex.test(event.key);
    });

    //state
    $("#wpforms-1183-field_8").append('<option value=null hidden disabled selected>Seleccione</option>');
});
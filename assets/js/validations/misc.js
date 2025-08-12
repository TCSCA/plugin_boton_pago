jQuery(document).ready(function($) {
   
    var billing_phone = $('#billing_phone');
    billing_phone.attr('maxlength', '11');
    billing_phone.attr('minlength', '7');
    var billing_phone_pattern = /^[0-9]+$/;
    var billing_phone_regex = new RegExp(billing_phone_pattern);
    billing_phone.on('keypress', function(event) {
        if (billing_phone.val().match(/[^0-9]/)) {
            console.log('Input already contains non-numeric characters. Keypress allowed.');
            return true; 
        }
        const isNumeric = billing_phone_regex.test(event.key);
        console.log('Keypress:', event.key, 'isNumeric:', isNumeric);
        return isNumeric;
    });

    $('input').on('change', () => {

        $('body').off('update_checkout');
  
    });

});
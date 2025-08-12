<?php

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action('plugins_loaded', 'pago_tarjeta_init');

function enqueuePagoTarjetaJS($hook){
    if($hook != 'woocommerce_page_wc-settings'){
        return;
    }
    if(!isset($_GET["section"])){
        return;
    }
    else {
        //El valor de este section es el mismo que el id del payment gateway
        if($_GET["section"] != 'pago_tarjeta') return;
    }
    wp_enqueue_script(
        'pagoTarjetaJS',
        plugins_url(
            '../assets/js/pagoTarjetaJS.js',
            __FILE__,
        ),
        array('jquery'),
        time()
    );

    $config = array(
        'consumer_key' => get_option('payment_gateway_bancaribe_consumer_key'),
        'account_number' => get_option('payment_gateway_account_number'),
        'commerce_name' => get_option('payment_gateway_commerce_name'),
        'consumer_secret' => get_option('payment_gateway_bancaribe_consumer_secret'),
        'confirmation_key' => get_option('payment_gateway_confirmation_key'),
        'phone_p2c' => get_option('payment_gateway_phone_p2c'),
        'document' => get_option('payment_gateway_document'),
        'bank' => get_option('payment_gateway_bank')
    );
    wp_localize_script('pagoTarjetaJS', 'config_admin', $config);
}

add_action('admin_enqueue_scripts', 'enqueuePagoTarjetaJS');

function pago_tarjeta_init() {
    class PagoTarjeta extends WC_Payment_Gateway {
        public function __construct()
        {
            $this->id = "pago_tarjeta";
            $this->method_title = __( 'Pago Tarjeta', 'payment-gateway-woo' );
            $this->method_description = "Método que permite configurar la información para registrar un Pago Tarjeta";
            $this->description = "Introduzca sus Datos:";
            $this->method_description = __( 'Método que permite configurar la información para registrar un Pago Tarjeta', 'payment-gateway-woo' );
            $this->supports = array(
                'products'
            );
            $this->has_fields = true;

            $image_url = plugins_url( '../assets/credicard.png', __FILE__ );

            $this->title = '
                <div class="report-title">
                    <h2>Pago Tarjeta</h2>
                    <img id="logo-credicard" src="' . $image_url . '" alt="Logo Bancaribe" >
                </div>
            ';

            $this->init_form_fields_tarjeta();
            $this->init_settings();
            $this->enabled = $this->get_option( 'enabled' );

            //Acciones y enlaces
            if ( version_compare( WC_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
            }
            else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        }

        public function needs_setup(){
            if (
                !empty(get_option('payment_gateway_bancaribe_consumer_key')) &&
                !empty(get_option('payment_gateway_account_number')) &&
                !empty(get_option('payment_gateway_commerce_name')) &&
                !empty(get_option('payment_gateway_bancaribe_consumer_secret')) &&
                !empty(get_option('payment_gateway_confirmation_key')) &&
                !empty(get_option('payment_gateway_phone_p2c')) &&
                !empty(get_option('payment_gateway_document')) &&
                !empty(get_option('payment_gateway_bank'))
            ) {
                return false;
            }
            return true;
        }


        public function init_form_fields_tarjeta() {

            $this->form_fields = array(
                'enabled' => array(
                    'title'   => 'Habilitar/Deshabilitar',
                    'type'    => 'checkbox',
                    'label'   => 'Habilitar Pago Tarjeta',
                    'default' => 'no',
                ),
            );

        }

        public function payment_fields(){
            if ($description = $this->get_description()) {
                echo wpautop(wptexturize($description));
            }

            global $woocommerce;
            $total = $woocommerce->cart->total;

            $entidades_bancarias = InfoBancaria::obtenerEntidadesBancarias();

            $formattedTotal = number_format($total, 2, ',', '.');

            woocommerce_form_field('card', array(
                'type'          => 'select',
                'options'       => array(
                    'D' => 'Débito',
                    'C' => 'Crédito'
                ),
                'label'         => "<strong>" . __("Tarjeta", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), '');

            woocommerce_form_field('amount_tarjeta', array(
                'type'          => 'text',
                'label'         => "<strong>" . __("Monto", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-first'),
                'required'      => true,
            ), $formattedTotal);

            woocommerce_form_field('account_owner', array(
                'type'          => 'text',
                'label'         => "<strong>" . __("Titular de la cuenta", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), '');

            woocommerce_form_field('account_type', array(
                'type'          => 'select',
                'options'       => array(
                    'AHORRO' => 'Ahorro',
                    'CORRIENTE' => 'Corriente',
                    'PRINCIPAL' => 'Principal',
                ),
                'label'         => "<strong>" . __("Tipo de Cuenta", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-first'),
                'required'      => true,
            ), '');

            woocommerce_form_field('banco_tarjeta', array(
                'type'    => 'select',
                'options' => $entidades_bancarias,
                'label'         => "<strong>" . __("Banco", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), '');

            woocommerce_form_field('card_number', array(
                'type'          => 'text',
                'label'         => "<strong>" . __("Nro. Tarjeta", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), '');

            woocommerce_form_field('document', array(
                'type'          => 'select',
                'options'       => array(
                    'RIF' => 'RIF',
                    'CI' => 'Cédula de Identidad',
                ),
                'label'         => "<strong>" . __("Tipo de Documento", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), '');

            woocommerce_form_field('document_type', array(
                'type'          => 'select',
                'options'       => array(
                    'V' => 'V - Venezolano',
                    'J' => 'J - Jurídico',
                    'C' => 'C - Comunal',
                    'E' => 'E - Extranjero',
                    'G' => 'G - Gobierno',
                    'P' => 'P - Pasaporte'
                ),
                'label'         => "<strong>" . __("Cédula / RIF", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-first'),
                'required'      => true,
            ), '');

            woocommerce_form_field('identification_document', array(
                'type'          => 'text',
                'label'         => "<strong>" . __("", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-last'),
            ), '');

            woocommerce_form_field('cvc', array(
                'type'          => 'password',
                'label'         => "<strong>" . __("CVC", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), '');

            woocommerce_form_field('expiration_date', array(
                'type'          => 'text',
                'label'         => "<strong>" . __("Fecha de expiración (MM/AA)", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-first'),
                'required'      => true,
            ), '');

            woocommerce_form_field('pin', array(
                'type'          => 'password',
                'label'         => "<strong>" . __("PIN/Clave secreta", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), '');

            woocommerce_form_field('motivo_tarjeta', array(
                'type'          => 'text',
                'label'         => "<strong>" . __("Concepto", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), '');
        }

        public function validate_fields(){
            return false;
        }

        public function process_payment($order_id) {

            //////////////////////////////////////VERIFICACION DE FECHAS EN COMERCIO INICIO/////////////////////////////////////////

            error_log('verificacion comercio inicio');

            // Load configuration
            require_once plugin_dir_path(dirname(__FILE__)) . 'config/config.php';
            
            // Get the complete API URL for verification
            $api_url_verificacion_card = get_api_url('validateCommerceLicence');

            $headers_verificacion_card = array(
                'Content-Type: application/json',
                'User-Agent: Mozilla/5.0',
                'KEY: key12345'
            );

            error_log(get_option( 'payment_gateway_document_type') . get_option( 'payment_gateway_document'));

            $request_data_verificacion_card = array(
                'rif' => get_option( 'payment_gateway_document_type') . get_option( 'payment_gateway_document')
            );

            $json_data_verificacion_card = json_encode($request_data_verificacion_card);

            $ch_verificacion_card = curl_init();
            curl_setopt($ch_verificacion_card, CURLOPT_URL, $api_url_verificacion_card);
            curl_setopt($ch_verificacion_card, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch_verificacion_card, CURLOPT_POST, 1);
            curl_setopt($ch_verificacion_card, CURLOPT_POSTFIELDS, $json_data_verificacion_card);
            curl_setopt($ch_verificacion_card, CURLOPT_HTTPHEADER, $headers_verificacion_card);

            $response_verificacion_card = curl_exec($ch_verificacion_card);

            if (curl_errno($ch_verificacion_card)) {
                error_log(json_encode(curl_error($ch_verificacion_card)));
                
                wc_add_notice('Error al realizar la solicitud', 'error');

                curl_close($ch_verificacion_card);

                return;
            } else {
                $encoding_verificacion_card = mb_detect_encoding($response_verificacion_card, 'UTF-8, ISO-8859-1, Windows-1252', true);
                $response_verificada = mb_convert_encoding($response_verificacion_card, 'UTF-8', $encoding_verificacion_card);
                $decoded_response_verificacion_card = json_decode($response_verificada, true);

                // Procesar la respuesta
                error_log(json_encode($response_verificada));
                error_log(json_encode($decoded_response_verificacion_card));

                curl_close($ch_verificacion_card);

                if($decoded_response_verificacion_card['status'] === 'ERROR'){
                    wc_add_notice('ERROR - ' . $decoded_response_verificacion_card['data'], 'error');
                    return;
                } 
            }

            error_log('verificacion comercio fin');

            /////////////////////////////////VERIFICACION DE FECHAS EN COMERCIO FINAL////////////////////////////////////////////
            
            $this->init_form_fields_tarjeta();

            if(empty($_POST['account_owner']) || strlen($_POST['account_owner']) < 3){

                wc_add_notice('Titular de la cuenta inválido, no puede ser vacío y debe tener al menos 3 caracteres.', 'error');
				return;

            } else if(empty($_POST['card_number']) || strlen($_POST['card_number']) < 15){

                wc_add_notice('Número de cuenta inválido, no puede ser vacío ni menor a 15 dígitos.', 'error');
				return;

            } else if(empty($_POST['identification_document']) || strlen($_POST['identification_document']) < 6){

                wc_add_notice('Documento inválido, no puede ser vacío ni menor a 6 dígitos.', 'error');
				return;

            } else if(empty($_POST['cvc']) || strlen($_POST['cvc']) < 3){

                wc_add_notice('CVC inválido, no puede ser vacío ni menor a 3 dígitos.', 'error');
				return;

            } else if(empty($_POST['expiration_date']) || strlen($_POST['expiration_date']) < 5){

                wc_add_notice('Fecha de expiración inválida, no puede estar vacía y debe tener el formato correcto (MM/AA).', 'error');
				return;

            } else if(empty($_POST['pin']) || strlen($_POST['pin']) < 4){

                wc_add_notice('PIN inválido, no puede ser vacío ni menor a 4 dígitos.', 'error');
				return;

            } else if(empty($_POST['motivo_tarjeta']) || strlen($_POST['motivo_tarjeta']) < 3){

                wc_add_notice('Concepto inválido, no puede ser vacío ni menor a 3 dígitos.', 'error');
				return;

            } 

            else {

                global $woocommerce;
                $moneda_actual = get_woocommerce_currency();
                echo 'La moneda actual es: ' . $moneda_actual;
                $order = new WC_Order( $order_id );

                // Procesar el pago aquí
                
                // Obtener el endpoint correcto según el tipo de tarjeta
                $endpoint = ($_POST['card'] === "D") ? 'debitCardPayment' : 'creditCardPayment';
                $api_url = get_api_url($endpoint);

                $total = $woocommerce->cart->total;

                $idBanco = InfoBancaria::obtenerIdEntidadBancaria($_POST['banco_tarjeta']);

                $billing_email  = $order->get_billing_email();

                $montoSinComa = $total;

                $date = $_POST['expiration_date'];

                $dateParts = explode('/', $date);

                $month = $dateParts[0];
                $year = $dateParts[1];

                if($month < 1 || $month > 12){
                    wc_add_notice('Mes de expiración inválido, debe ser entre 1 y 12', 'error');
                    return;
                }

                $identificationDocumentOG = $_POST['identification_document'];
                if (strlen($identificationDocumentOG) < 9) {
                    $paddedDocument = str_pad($identificationDocumentOG, 9, '0', STR_PAD_LEFT);
                } else {
                    $paddedDocument = $identificationDocumentOG;
                }
    
                if($_POST['card'] === "D"){
                    $request_data = array(
                        'rif' => get_option( 'payment_gateway_document_type') . get_option('payment_gateway_document'),
                        'currency' => 'VED',
                        'amount' => $montoSinComa,
                        'paymentChannel' => 1,
                        'reason' => $_POST['motivo_tarjeta'],
                        'bankPayment' => $idBanco,
                        'debitCard' => array(
                            'holderName' => $_POST['account_owner'],
                            'holderId' => $_POST['document_type']  . $paddedDocument,
                            'holderIdDoc' => $_POST['document'],
                            'cardNumber' => $_POST['real_card_number'],
                            'cvc' => $_POST['cvc'],
                            'expirationMonth' => $month,
                            'expirationYear' => $year,
                            'cardType' => 'DEBIT',
                            'accountType' => $_POST['account_type'],
                            'pin' => $_POST['pin'],
                        ),
                        'email' => $billing_email
                    );
                } else {
                    $request_data = array(
                        'rif' => get_option( 'payment_gateway_document_type') . get_option('payment_gateway_document'),
                        'currency' => 'VED',
                        'amount' => $montoSinComa,
                        'reason' => $_POST['motivo_tarjeta'],
                        'paymentChannel' => 1,
                        'bankPayment' => $idBanco,
                        'creditCard' => array(
                            'holderName' => $_POST['account_owner'],
                            'holderId' => $_POST['document_type']  . $paddedDocument,
                            'holderIdDoc' => $_POST['document'],
                            'cardNumber' => $_POST['real_card_number'],
                            'cvc' => $_POST['cvc'],
                            'expirationMonth' => $month,
                            'expirationYear' => $year,
                            'cardType' => 'CREDIT',
                        ),
                        'email' => $billing_email
                    );
                }
                //Parametros de Entrada del Servicio a Invocar en formato JSON
    
                //Convirtiendo los parametros a formato JSON
                $json_data = json_encode($request_data);

                error_log(json_encode($json_data));

                //Armando Cabecera HTTP
                $headers = array(
                    'Content-Type: application/json',
                    'User-Agent: Mozilla/5.0',
                    'KEY: key12345'
                );

                //Configurar la solicitud cURL
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                // Ejecutar la solicitud cURL y obtener la respuesta
                $response = curl_exec($ch);

                error_log(json_encode($response));

                $encoding = mb_detect_encoding($response, 'UTF-8, ISO-8859-1, Windows-1252', true);
                $response2 = mb_convert_encoding($response, 'UTF-8', $encoding);
                $decoded_response = json_decode($response2, true);
                error_log(json_encode($response2));
                error_log(json_encode($decoded_response));

                // $decoded_response = json_decode($response, true);
                
                // // Verificar si hay errores en la solicitud
                if ($decoded_response['status'] === 500) {
                    error_log(json_encode(curl_error($ch)));
                    
                    wc_add_notice('Error de servidor.', 'error');

                    // Cerrar la sesión cURL
                    curl_close($ch);

                    // return;
                } else {
                    // Procesar la respuesta
                    // $decoded_response = json_decode($response, true);
                    // error_log(json_encode($response));
                    // error_log(json_encode($decoded_response));
                    //Retorno del pago exitoso

                    // Cerrar la sesión cURL
                    curl_close($ch);

                    if($decoded_response['status'] === 'ERROR'){
                        if($decoded_response['data'] === 'Transaccion rechazada'){
                            wc_add_notice('ERROR - ' . 'Transacción rechazada', 'error');
                        }
                        else if($decoded_response['data'] === 'Error al conectar con el banco'){
                            wc_add_notice('ERROR - ' . 'Error al hacer la conexión con el banco', 'error');
                        }
                        else if($decoded_response['data'] === 'UNAUTHORIZED'){
                            wc_add_notice('ERROR - ' . 'RIF del comercio no está autorizado.', 'error');
                        }
                        else{
                            if (isset($decoded_response['properties'])) {
                                if (isset($decoded_response['properties']['message'])) {
                                    wc_add_notice('ERROR - ' . $decoded_response['properties']['message'], 'error');
                                } else {
                                    wc_add_notice('ERROR - Error general del método', 'error');
                                }
                            } else {
                                wc_add_notice('ERROR - Error general del método', 'error');
                            }
                        }
                        // if($decoded_response['data']['cause'] === '05, NEGADA'){
                        //     wc_add_notice('ERROR - ' . 'Tarjeta Negada', 'error');
                        // }
                        // else if($decoded_response['data']['cause'] === '51, RECHAZADA'){
                        //     wc_add_notice('ERROR - ' . 'Tarjeta rechazada', 'error');
                        // }
                        // else if($decoded_response['data']['cause'] === 'holderId INVALID_HOLDER_ID'){
                        //     wc_add_notice('ERROR - ' . 'Documento de identidad inválido', 'error');
                        // }
                        // else if($decoded_response['data']['cause'] === 'cvc INVALID_CCV'){
                        //     wc_add_notice('ERROR - ' . 'CVC inválido', 'error');
                        // }
                        // else if($decoded_response['data']['cause'] === '' || $decoded_response['data']['cause'] === '14, TARJETA INVALIDA'){
                        //     wc_add_notice('ERROR - ' . 'Número de tarjeta inválido', 'error');
                        // }
                        // else if($decoded_response['data']['cause'] === '55, CLAVE INVALIDA'){
                        //     wc_add_notice('ERROR - ' . 'Clave inválida', 'error');
                        // }
                        // else if($decoded_response['data']['cause'] === '78, CUENTA INVALIDA/TARJETA BLOQUEADA'){
                        //     wc_add_notice('ERROR - ' . 'Tarjeta bloqueada', 'error');
                        // }
                        // else{
                        //     if (isset($decoded_response['properties'])) {
                        //         if (isset($decoded_response['properties']['message'])) {
                        //             wc_add_notice('ERROR - ' . $decoded_response['properties']['message'], 'error');
                        //         } else {
                        //             wc_add_notice('ERROR - Error general del método', 'error');
                        //         }
                        //     } else {
                        //         wc_add_notice('ERROR - Error general del método', 'error');
                        //     }
                        // }
                        return;
                    } else if($decoded_response['status'] === 'SUCCESS') {
                        // Mark as on-hold (we're awaiting the cheque)
                        $order->update_status('on-hold', __( 'Esperando verificación de pago', 'woocommerce' ));
                    
                        // Remove cart
                        $woocommerce->cart->empty_cart();
                    
                        // Return thankyou redirect
                        return array(
                            'result' => 'success',
                            'redirect' => $this->get_return_url( $order )
                        );
                    } else {
                        wc_add_notice('ERROR', 'error');
                        return;
                    }
                }
            } 
        }

        public function receipt_page($order_id) {
            echo '<p>Gracias por elegir Mi Método de Pago. Por favor, siga las instrucciones para completar su pago.</p>';
        }
    }
}
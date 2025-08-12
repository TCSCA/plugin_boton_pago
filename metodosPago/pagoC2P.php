<?php

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action('plugins_loaded', 'pago_c2p_init');

function enqueuePagoC2PJS($hook){
    if($hook != 'woocommerce_page_wc-settings'){
        return;
    }
    if(!isset($_GET["section"])){
        return;
    }
    else {
        //El valor de este section es el mismo que el id del payment gateway
        if($_GET["section"] != 'pago_c2p') return;
    }
    wp_enqueue_script(
        'pagoC2PJS',
        plugins_url(
            '../assets/js/pagoC2PScript.js',
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
    wp_localize_script('pagoC2PJS', 'config_admin', $config);
}

add_action('admin_enqueue_scripts', 'enqueuePagoC2PJS');

function pago_c2p_init() {
    class PagoC2P extends WC_Payment_Gateway {
        public function __construct()
        {
            $this->id = "pago_c2p";
            $this->method_title = __( 'Pago C2P', 'payment-gateway-woo' );
            $this->method_description = "Método que permite configurar la información para registrar un Pago C2P";
            $this->description = "Introduzca sus Datos:";
            $this->method_description = __( 'Método que permite configurar la información para registrar un Pago C2P', 'payment-gateway-woo' );
            $this->supports = array(
                'products'
            );
            $this->has_fields = true;

            $image_url = plugins_url( '../assets/bancaribe.png', __FILE__ );

            $this->title = '
                <div class="report-title">
                    <h2>Pago Móvil C2P</h2>
                    <img id="logo-bancaribe" src="' . $image_url . '" alt="Logo Bancaribe" >
                </div>
            ';

            $this->init_form_fields();
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


        public function init_form_fields() {
            $entidades_bancarias = InfoBancaria::obtenerEntidadesBancariasIntegradas();

            $this->form_fields = array(
                'enabled' => array(
                    'title'   => 'Habilitar/Deshabilitar',
                    'type'    => 'checkbox',
                    'label'   => 'Habilitar Pago C2P',
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

            $formattedTotal = number_format($total, 2, ',', '.');

            $entidades_bancarias = InfoBancaria::obtenerEntidadesBancarias();

            woocommerce_form_field('tipo_documento_c2p', array(
                'type'          => 'select',
                'options'       => array(
                    'V' => 'V - Venezolano',
                    'E' => 'E - Extranjero',
                    'P' => 'P - Pasaporte',
                    'J' => 'J - Jurídico',
                    'G' => 'G - Gubernamental'
                ),
                'label'         => "<strong>" . __("Tipo de Documento", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), '');

            woocommerce_form_field('documento_c2p', array(
                'type'          => 'text',
                'custom_attributes' => array(
                    'minlength'       =>  6,
                    'maxlength'       =>  10,
                ),
                'label'         => "<strong>" . __("Cédula / RIF", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), '');

            woocommerce_form_field('codigo_tlf_c2p', array(
                'type'          => 'select',
                'options'       => array(
                    '0414' => '0414',
                    '0424' => '0424',
                    '0412' => '0412',
                    '0416' => '0416',
                    '0426' => '0426'
                ),
                'label'         => "<strong>" . __("Teléfono", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-first'),
                'required'      => true,
            ), '');

            woocommerce_form_field('tlf_client_c2p', array(
                'type'          => 'text',
                'custom_attributes' => array(
                    'minlength'       =>  7,
                    'maxlength'       =>  7,
                ),
                'label'         => "<strong>" . __("", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-last'),
            ), '');

            
            woocommerce_form_field('banco_c2p', array(
                'type'    => 'select',
                'options' => $entidades_bancarias,
                'label'         => "<strong>" . __("Banco", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), '');

            woocommerce_form_field('otp_c2p', array(
                'type'          => 'text',
                'custom_attributes' => array(
                    'minlength'       =>  6,
                    'maxlength'       =>  12,
                ),
                'label'         => "<strong>" . __("Código OTP", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), '');

            woocommerce_form_field('motivo_c2p', array(
                'type'          => 'text',
                'custom_attributes' => array(
                    'minlength'       =>  5,
                    'maxlength'       =>  30,
                ),
                'label'         => "<strong>" . __("Concepto", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
            ), '');

            woocommerce_form_field('monto_c2p', array(
                'type'          => 'text',
                'custom_attributes' => array(
                    'minlength'       =>  4,
                    'maxlength'       =>  12,
                ),
                'label'         => "<strong>" . __("Monto", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), $formattedTotal);
        }

        public function validate_fields(){
            return false;
        }

        public function process_payment($order_id) {

            //////////////////////////////////////VERIFICACION DE FECHAS EN COMERCIO INICIO/////////////////////////////////////////

            error_log('verificacion comercio inicio');

            // Load configuration
            require_once plugin_dir_path(dirname(__FILE__)) . 'config/config.php';
            
            $api_url_verificacion_c2p = get_api_url('validateCommerceLicence');

            $headers_verificacion_c2p = array(
                'Content-Type: application/json',
                'User-Agent: Mozilla/5.0',
                'KEY: key12345'
            );

            error_log(get_option( 'payment_gateway_document_type') . get_option( 'payment_gateway_document'));

            $request_data_verificacion_c2p = array(
                'rif' => get_option( 'payment_gateway_document_type') . get_option( 'payment_gateway_document')
            );

            $json_data_verificacion_c2p = json_encode($request_data_verificacion_c2p);

            $ch_verificacion_c2p = curl_init();
            curl_setopt($ch_verificacion_c2p, CURLOPT_URL, $api_url_verificacion_c2p);
            curl_setopt($ch_verificacion_c2p, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch_verificacion_c2p, CURLOPT_POST, 1);
            curl_setopt($ch_verificacion_c2p, CURLOPT_POSTFIELDS, $json_data_verificacion_c2p);
            curl_setopt($ch_verificacion_c2p, CURLOPT_HTTPHEADER, $headers_verificacion_c2p);

            $response_verificacion_c2p = curl_exec($ch_verificacion_c2p);

            if (curl_errno($ch_verificacion_c2p)) {
                error_log(json_encode(curl_error($ch_verificacion_c2p)));
                
                wc_add_notice('Error al realizar la solicitud', 'error');

                curl_close($ch_verificacion_c2p);

                return;
            } else {
                $encoding_verificacion_c2p = mb_detect_encoding($response_verificacion_c2p, 'UTF-8, ISO-8859-1, Windows-1252', true);
                $response_verificada = mb_convert_encoding($response_verificacion_c2p, 'UTF-8', $encoding_verificacion_c2p);
                $decoded_response_verificacion_c2p = json_decode($response_verificada, true);

                // Procesar la respuesta
                error_log(json_encode($response_verificada));
                error_log(json_encode($decoded_response_verificacion_c2p));

                curl_close($ch_verificacion_c2p);

                if($decoded_response_verificacion_c2p['status'] === 'ERROR'){
                    wc_add_notice('ERROR - ' . $decoded_response_verificacion_c2p['data'], 'error');
                    return;
                } 
            }

            error_log('verificacion comercio fin');

            /////////////////////////////////VERIFICACION DE FECHAS EN COMERCIO FINAL////////////////////////////////////////////
            
            $this->init_form_fields();

            if(empty($_POST['documento_c2p']) || strlen($_POST['documento_c2p']) < 6){

                wc_add_notice('Documento inválido, no puede ser vacío ni menor a 6 dígitos.', 'error');
				return;

            } else if(empty($_POST['tlf_client_c2p']) || strlen($_POST['tlf_client_c2p']) < 7){

                wc_add_notice('Telefono inválido, no puede estar vacío ni menor a 7 dígitos.', 'error');
				return;

            }else if(empty($_POST['otp_c2p']) || strlen($_POST['otp_c2p']) < 8){

                wc_add_notice('Código OTP inválido, no puede ser vacío ni menor a 8 dígitos.', 'error');
				return;

            }

            else {
                
                global $woocommerce;
                $moneda_actual = get_woocommerce_currency();
                echo 'La moneda actual es: ' . $moneda_actual;
                $order = new WC_Order( $order_id );

                $idBanco = InfoBancaria::obtenerIdEntidadBancaria($_POST['banco_c2p']);

                // Procesar el pago aquí

                $api_url = get_api_url('purchaseC2P');

                $total = $woocommerce->cart->total;

                $billing_email  = $order->get_billing_email();

                $montoSinComa = $total;

                $concepto = "";

                error_log(json_encode($_POST['motivo_c2p']));

                if($_POST['motivo_c2p'] != ''){
                    $concepto = $_POST['motivo_c2p'];
                }else{
                    $concepto = "Pago C2P";
                }

                //Parametros de Entrada del Servicio a Invocar en formato JSON
                $request_data = array(
                    'rif' => get_option( 'payment_gateway_document_type') . get_option( 'payment_gateway_document'),
                    'phoneNumberCommerce' => get_option('payment_gateway_phone_p2c'),
                    'nameCommerce' => get_option('payment_gateway_commerce_name'),
                    'identificationDocument' => $_POST['tipo_documento_c2p']  . $_POST['documento_c2p'],
                    'phoneNumber' => $_POST['codigo_tlf_c2p'] . $_POST['tlf_client_c2p'],
                    'bankPayment' => $idBanco,
                    'transactionAmount' => $montoSinComa,
                    'concept' => $concepto,
                    'otp' => $_POST['otp_c2p'],
                    'paymentChannel' => 1,
                    'email' => $billing_email
                );

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

                error_log($response);
    
                // Verificar si hay errores en la solicitud
                if (curl_errno($ch)) {
                    error_log(json_encode(curl_error($ch)));
                    
                    wc_add_notice('Error al realizar la solicitud', 'error');

                    // Cerrar la sesión cURL
                    curl_close($ch);

                    return;
                } else {
                    $encoding = mb_detect_encoding($response, 'UTF-8, ISO-8859-1, Windows-1252', true);
                    $responsec2p = mb_convert_encoding($response, 'UTF-8', $encoding);
                    $decoded_response = json_decode($responsec2p, true);
                    // Procesar la respuesta
                    error_log(json_encode($responsec2p));
                    error_log(json_encode($decoded_response));

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

    // function add_to_woo_pm_gateway( $methods ) {
    //     $methods[] = 'GenericPagoMovil';
    //     return $methods;
    // }
}
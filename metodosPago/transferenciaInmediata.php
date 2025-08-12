<?php

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action('plugins_loaded', 'pago_trf_init');

//esta funcion agrega el js a la configuracion del gateway
function enqueueTransferenciaInmediataJS($hook){
    if($hook != 'woocommerce_page_wc-settings'){
        return;
    }
    if(!isset($_GET["section"])){
        return;
    }
    else {
        //El valor de este section es el mismo que el id del payment gateway
        if($_GET["section"] != 'trf_inmediata') return;
    }
    wp_enqueue_script(
        'transferenciaInmediataJS',
        plugins_url(
            '../assets/js/transferenciaInmediataScript.js',
            __FILE__,
        ),
        array('jquery'),
        time()
    );
    $config = array(
 );

    $config = array(
        'consumer_key' => get_option('payment_gateway_bancaribe_consumer_key'),
        'account_number' => get_option('payment_gateway_account_number'),
        'commerce_name' => get_option('payment_gateway_commerce_name'),
        'consumer_secret' => get_option('payment_gateway_bancaribe_consumer_secret'),
        'confirmation_key' => get_option('payment_gateway_confirmation_key'),
        'phone_p2c' => get_option('payment_gateway_phone_p2c'),
        'document' => get_option('payment_gateway_document'),
        'bank' => get_option('payment_gateway_bank'),
        'consumer_key_creditcard' => get_option('payment_gateway_creditcard_consumer_key'),
        'consumer_secret_creditcard' => get_option('payment_gateway_creditcard_consumer_secret')
    );
    wp_localize_script('transferenciaInmediataJS', 'config_admin', $config);
}

add_action('admin_enqueue_scripts', 'enqueueTransferenciaInmediataJS');

function pago_trf_init() {
    class TransferenciaInmediata extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = "trf_inmediata";
            $this->method_title = __( 'Transferencia Inmediata', 'payment-gateway-woo' );
            $this->method_description = "Método que permite configurar la información para registrar un Pago C2P";
            
            if(!empty(get_option('payment_gateway_bank'))){
                $entidad_bancaria = InfoBancaria::obtenerNombreEntidadBancaria(get_option('payment_gateway_bank'));

                $this->description = "Datos del Pago:\n" .
                "  - Banco: " . esc_html($entidad_bancaria) . "\n" .
                "  - Nombre de Comercio: " . get_option('payment_gateway_commerce_name') . "\n" .
                "  - Cédula / RIF: " . get_option( 'payment_gateway_document_type') . get_option('payment_gateway_document') . "\n" .
                "  - Número de Cuenta: " . get_option('payment_gateway_account_number');
            }
            
            $this->method_description = __( 'Método que permite ejecutar una transferencia inmediata', 'payment-gateway-woo' );
            $this->supports = array(
                'products'
            );
            $this->has_fields = true;

            $image_url = plugins_url( '../assets/bancaribe.png', __FILE__ );

            $this->title = '
                <div class="report-title">
                    <h2>Reporte de Transferencia</h2>
                    <img id="logo-bancaribe" src="' . $image_url . '" alt="Logo Bancaribe" >
                </div>
            ';

            $this->init_form_fields_transferencia();
            $this->init_settings();
            $this->enabled = $this->get_option( 'enabled' );
            
            if ( version_compare( WC_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
            }
            else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

        }

        public function needs_setup(){
            if(
                !empty(get_option('payment_gateway_bancaribe_consumer_key')) &&
                !empty(get_option('payment_gateway_account_number')) &&
                !empty(get_option('payment_gateway_commerce_name')) &&
                !empty(get_option('payment_gateway_bancaribe_consumer_secret')) &&
                !empty(get_option('payment_gateway_confirmation_key')) &&
                !empty(get_option('payment_gateway_phone_p2c')) &&
                !empty(get_option('payment_gateway_document')) &&
                !empty(get_option('payment_gateway_bank')) &&
                !empty(get_option('payment_gateway_creditcard_consumer_key')) &&
                !empty(get_option('payment_gateway_creditcard_consumer_secret'))
            ){
                return false;
            }
            return true;
        }

        public function init_form_fields_transferencia(){

            $this->form_fields = array(
                'enabled' => array(
                    'title'   => 'Habilitar/Deshabilitar',
                    'type'    => 'checkbox',
                    'label'   => 'Habilitar Transferencia Inmediata',
                    'default' => 'no',
                ),
            );

        }

        public function payment_fields(){
            if ($description = $this->get_description()) {
                echo wpautop(wptexturize($description));
            }

            global $woocommerce;
            $total_transfer = $woocommerce->cart->total;

            $entidades_bancarias = InfoBancaria::obtenerEntidadesBancarias();

            $formattedTotal = number_format($total_transfer, 2, ',', '.');

            woocommerce_form_field('tipo_documento_trf', array(
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

            woocommerce_form_field('documento_trf', array(
                'type'          => 'text',
                'custom_attributes' => array(
                    'minlength'       =>  6,
                    'maxlength'       =>  10,
                ),
                'label'         => "<strong>" . __("Cédula / RIF", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), '');

            woocommerce_form_field('banco_trf', array(
                'type'    => 'select',
                'options' => $entidades_bancarias,
                'label'         => "<strong>" . __("Banco", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), '');


            woocommerce_form_field('codigo_tlf_trf', array(
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

            woocommerce_form_field('tlf_client_trf', array(
                'type'          => 'text',
                'custom_attributes' => array(
                    'minlength'       =>  7,
                    'maxlength'       =>  7,
                ),
                'label'         => "<strong>" . __("", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-last'),
            ), '');

            woocommerce_form_field('amount_trf', array(
                'type'          => 'text',
                'label'         => "<strong>" . __("Monto", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), $formattedTotal);

            woocommerce_form_field('ref_value_trf', array(
                'type'          => 'text',
                'label'         => "<strong>" . __("Referencia Bancaria", "payment-gateway-woo") . "</strong>",
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

            //local
            // $api_url_verificacion_trf = 'http://172.16.90.117:8080/api/validateCommerceLicence';
            //pre-produccion
            // $api_url_verificacion_trf = 'http://172.30.145.250:4000/api/validateCommerceLicence';
            //desarrollo/produccion
            $api_url_verificacion_trf = 'http://localhost:4000/api/validateCommerceLicence';

            $headers_verificacion_trf = array(
                'Content-Type: application/json',
                'User-Agent: Mozilla/5.0',
                'KEY: key12345'
            );

            error_log(get_option( 'payment_gateway_document_type') . get_option( 'payment_gateway_document'));

            $request_data_verificacion_trf = array(
                'rif' => get_option( 'payment_gateway_document_type') . get_option( 'payment_gateway_document')
            );

            $json_data_verificacion_trf = json_encode($request_data_verificacion_trf);

            $ch_verificacion_trf = curl_init();
            curl_setopt($ch_verificacion_trf, CURLOPT_URL, $api_url_verificacion_trf);
            curl_setopt($ch_verificacion_trf, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch_verificacion_trf, CURLOPT_POST, 1);
            curl_setopt($ch_verificacion_trf, CURLOPT_POSTFIELDS, $json_data_verificacion_trf);
            curl_setopt($ch_verificacion_trf, CURLOPT_HTTPHEADER, $headers_verificacion_trf);

            $response_verificacion_trf = curl_exec($ch_verificacion_trf);

            if (curl_errno($ch_verificacion_trf)) {
                error_log(json_encode(curl_error($ch_verificacion_trf)));
                
                wc_add_notice('Error al realizar la solicitud', 'error');

                curl_close($ch_verificacion_trf);

                return;
            } else {
                $encoding_verificacion_trf = mb_detect_encoding($response_verificacion_trf, 'UTF-8, ISO-8859-1, Windows-1252', true);
                $response_verificada = mb_convert_encoding($response_verificacion_trf, 'UTF-8', $encoding_verificacion_trf);
                $decoded_response_verificacion_trf = json_decode($response_verificada, true);

                // Procesar la respuesta
                error_log(json_encode($response_verificada));
                error_log(json_encode($decoded_response_verificacion_trf));

                curl_close($ch_verificacion_trf);

                if($decoded_response_verificacion_trf['status'] === 'ERROR'){
                    wc_add_notice('ERROR - ' . $decoded_response_verificacion_trf['data'], 'error');
                    return;
                } 
            }

            error_log('verificacion comercio fin');

            /////////////////////////////////VERIFICACION DE FECHAS EN COMERCIO FINAL////////////////////////////////////////////
            
            $this->init_form_fields_transferencia();

            if(empty($_POST['documento_trf']) || strlen($_POST['documento_trf']) < 6){

                wc_add_notice('Documento inválido, no puede ser vacío ni menor a 6 dígitos.', 'error');
				return;

            } else if(empty($_POST['tlf_client_trf']) || strlen($_POST['tlf_client_trf']) < 7){

                wc_add_notice('Teléfono inválido, no puede estar vacío ni menor a 7 dígitos.', 'error');
				return;

            }
            else if(empty($_POST['ref_value_trf']) || strlen($_POST['ref_value_trf']) < 6){

                wc_add_notice('Referencia Bancaria inválida, no puede estar vacío ni menor a 6 dígitos.', 'error');
				return;

            }
            else {
                
                global $woocommerce;
                $moneda_actual = get_woocommerce_currency();
                echo 'La moneda actual es: ' . $moneda_actual;
                $order = new WC_Order( $order_id );

                $total = $woocommerce->cart->total;

                $billing_email  = $order->get_billing_email();

                $idBanco = InfoBancaria::obtenerIdEntidadBancaria($_POST['banco_trf']);

                // Procesar el pago aquí

                //local
                // $api_url = 'http://172.16.90.117:8080/api/validatePaymentP2c';
                //pre-produccion
                $api_url = 'http://172.30.145.250:4000/api/validatePaymentP2c';
                //desarrollo/produccion
                // $api_url = 'http://localhost:4000/api/validatePaymentP2c';

                $montoSinComa = $total;

                //Parametros de Entrada del Servicio a Invocar en formato JSON
                $request_data = array(
                    'rif' => get_option( 'payment_gateway_document_type') . get_option( 'payment_gateway_document'),
                    'payerDocument' => $_POST['tipo_documento_trf']  . $_POST['documento_trf'],
                    'debitPhone' => $_POST['codigo_tlf_trf'] . $_POST['tlf_client_trf'],
                    'referenceNumber' => $_POST['ref_value_trf'],
                    'typeTransaction' => 'TRF',
                    'bankPayment' => $idBanco,
                    'transactionAmount' => $montoSinComa,
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
    
                // Verificar si hay errores en la solicitud
                if (curl_errno($ch)) {
                    error_log(json_encode(curl_error($ch)));
                    
                    wc_add_notice('Error al realizar la solicitud', 'error');

                    // Cerrar la sesión cURL
                    curl_close($ch);

                    return;
                } else {
                    // Procesar la respuesta
                    $encoding = mb_detect_encoding($response, 'UTF-8, ISO-8859-1, Windows-1252', true);
                    $responseTrf = mb_convert_encoding($response, 'UTF-8', $encoding);
                    $decoded_response = json_decode($responseTrf, true);                    
                    error_log(json_encode($responseTrf));
                    error_log(json_encode($decoded_response));
                    //Retorno del pago exitoso

                    // Cerrar la sesión cURL
                    curl_close($ch);

                    if($decoded_response['status'] === 'ERROR'){
                        if($decoded_response['data'] === 'Transaccion rechazada'){
                            wc_add_notice('ERROR - ' . 'Transacción rechazada', 'error');
                        }
                        else if($decoded_response['data'] === 'Informacion bancaria no existe'){
                            wc_add_notice('ERROR - ' . 'Informacion bancaria no existe', 'error');
                        }
                        else if($decoded_response['data'] === 'Error al conectar con el banco'){
                            wc_add_notice('ERROR - ' . 'Error al conectar con el banco', 'error');
                        }
                        else if($decoded_response['data'] === 'Referencia de pago registrada'){
                            wc_add_notice('ERROR - ' . 'Referencia de pago registrada.', 'error');
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
}
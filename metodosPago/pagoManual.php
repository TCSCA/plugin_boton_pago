<?php

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action( 'plugins_loaded', 'pago_manual_init' );

//esta funcion agrega el js a la configuracion del gateway
function enqueuePagoManualJS($hook){
    if($hook != 'woocommerce_page_wc-settings'){
        return;
    }
    if(!isset($_GET["section"])){
        return;
    }
    else {
        //El valor de este section es el mismo que el id del payment gateway
        if($_GET["section"] != 'pago_manual') return;
    }
    wp_enqueue_script(
        'pagoManualJS',
        plugins_url(
            '../assets/js/pagoManualScript.js',
            __FILE__,
        ),
        array('jquery'),
        time()
    );

    $config = array(
        'account_number' => get_option('payment_gateway_account_number'),
        'commerce_name' => get_option('payment_gateway_commerce_name'),
        'confirmation_key' => get_option('payment_gateway_confirmation_key'),
        'phone_p2c' => get_option('payment_gateway_phone_p2c'),
        'document' => get_option('payment_gateway_document'),
        'bank' => get_option('payment_gateway_bank')
    );
    wp_localize_script('pagoManualJS', 'config_admin', $config);
}

add_action('admin_enqueue_scripts', 'enqueuePagoManualJS');

function pago_manual_init() {
    class PagoManual extends WC_Payment_Gateway {
        public function __construct()
        {
            $this->id = "pago_manual";
            $this->method_title = __( 'Reporte de Pago Manual', 'payment-gateway-woo' );
            $this->method_description = "Para poder validar el Pago Manual de manera inmediata, se necesita que configures los campos 
            asociados a la información bancaria:";

            if(!empty(get_option('payment_gateway_bank'))){
                $entidad_bancaria = InfoBancaria::obtenerNombreEntidadBancaria(get_option('payment_gateway_bank'));

                $this->description = "Datos del Pago:\n" .
                "  - Banco: " . esc_html($entidad_bancaria) . "\n" .
                "  - Número de Cuenta: " . get_option('payment_gateway_account_number') . "\n" .
                "  - Cédula / RIF: " . get_option( 'payment_gateway_document_type') . get_option('payment_gateway_document') . "\n" .
                "  - Número de Teléfono: " . get_option('payment_gateway_phone_p2c');
            }

            $this->method_description = __( 'Para poder validar el Pago Manual de manera inmediata, se necesita que configures los campos 
            asociados a la información bancaria:', 'payment-gateway-woo' );
            $this->supports = array(
                'products'
            );
            $this->has_fields = true;

            $this->title = "Pago Manual";

            $this->init_form_fields_manual();
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
            if (
                !empty(get_option('payment_gateway_account_number')) &&
                !empty(get_option('payment_gateway_commerce_name')) &&
                !empty(get_option('payment_gateway_confirmation_key')) &&
                !empty(get_option('payment_gateway_phone_p2c')) &&
                !empty(get_option('payment_gateway_document')) &&
                !empty(get_option('payment_gateway_bank'))
            ) {
                return false;
            }
            return true;
        }

        public function init_form_fields_manual(){

            $this->form_fields = array(
                'enabled' => array(
                    'title'   => 'Habilitar/Deshabilitar',
                    'type'    => 'checkbox',
                    'label'   => 'Habilitar Pago Manual',
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

            woocommerce_form_field('tipo_trans', array(
                'type'          => 'select',
                'options'       => array(
                    'TRF' => 'Transferencia',
                    'PM' => 'Pago Móvil'
                ),
                'label'         => "<strong>" . __("Tipo de Transacción", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), '');

            woocommerce_form_field('banco_manual', array(
                'type'    => 'select',
                'options' => $entidades_bancarias,
                'label'         => "<strong>" . __("Banco", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), '');

            woocommerce_form_field('tipo_documento_pago_manual', array(
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

            woocommerce_form_field('documento_pago_manual', array(
                'type'          => 'text',
                'custom_attributes' => array(
                    'minlength'       =>  6,
                    'maxlength'       =>  10,
                ),
                'label'         => "<strong>" . __("Cédula / RIF", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), '');

            woocommerce_form_field('codigo_tlf_pago_manual', array(
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

            woocommerce_form_field('tlf_client_pago_manual', array(
                'type'          => 'text',
                'custom_attributes' => array(
                    'minlength'       =>  7,
                    'maxlength'       =>  7,
                ),
                'label'         => "<strong>" . __("", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-last'),
            ), '');

            woocommerce_form_field('amount_pago_manual', array(
                'type'          => 'text',
                'label'         => "<strong>" . __("Monto", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), $formattedTotal);

            woocommerce_form_field('ref_value_pago_manual', array(
                'type'          => 'text',
                'label'         => "<strong>" . __("Referencia de Pago", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), '');
        }

        public function validate_fields(){
            return false;
        }

        public function process_payment( $order_id ) {

            //////////////////////////////////////VERIFICACION DE FECHAS EN COMERCIO INICIO/////////////////////////////////////////

            error_log('verificacion comercio inicio');

            // Load configuration
            require_once plugin_dir_path(dirname(__FILE__)) . 'config/config.php';
            
            $api_url_verificacion_manual = get_api_url('validateCommerceLicence');

            $headers_verificacion_manual = array(
                'Content-Type: application/json',
                'User-Agent: Mozilla/5.0',
                'KEY: key12345'
            );

            error_log(get_option( 'payment_gateway_document_type') . get_option( 'payment_gateway_document'));

            $request_data_verificacion_manual = array(
                'rif' => get_option( 'payment_gateway_document_type') . get_option( 'payment_gateway_document')
            );

            $json_data_verificacion_manual = json_encode($request_data_verificacion_manual);

            $ch_verificacion_manual = curl_init();
            curl_setopt($ch_verificacion_manual, CURLOPT_URL, $api_url_verificacion_manual);
            curl_setopt($ch_verificacion_manual, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch_verificacion_manual, CURLOPT_POST, 1);
            curl_setopt($ch_verificacion_manual, CURLOPT_POSTFIELDS, $json_data_verificacion_manual);
            curl_setopt($ch_verificacion_manual, CURLOPT_HTTPHEADER, $headers_verificacion_manual);

            $response_verificacion_manual = curl_exec($ch_verificacion_manual);

            if (curl_errno($ch_verificacion_manual)) {
                error_log(json_encode(curl_error($ch_verificacion_manual)));
                
                wc_add_notice('Error al realizar la solicitud', 'error');

                curl_close($ch_verificacion_manual);

                return;
            } else {
                $encoding_verificacion_manual = mb_detect_encoding($response_verificacion_manual, 'UTF-8, ISO-8859-1, Windows-1252', true);
                $response_verificada = mb_convert_encoding($response_verificacion_manual, 'UTF-8', $encoding_verificacion_manual);
                $decoded_response_verificacion_manual = json_decode($response_verificada, true);

                // Procesar la respuesta
                error_log(json_encode($response_verificada));
                error_log(json_encode($decoded_response_verificacion_manual));

                curl_close($ch_verificacion_manual);

                if($decoded_response_verificacion_manual['status'] === 'ERROR'){
                    wc_add_notice('ERROR - ' . $decoded_response_verificacion_manual['data'], 'error');
                    return;
                } 
            }

            error_log('verificacion comercio fin');

            /////////////////////////////////VERIFICACION DE FECHAS EN COMERCIO FINAL////////////////////////////////////////////
            
            $this->init_form_fields_manual();

            if(empty($_POST['documento_pago_manual']) || strlen($_POST['documento_pago_manual']) < 6){

                wc_add_notice('Documento inválido, no puede ser vacío ni menor a 6 dígitos.', 'error');
				return;

            } else if(empty($_POST['tlf_client_pago_manual']) || strlen($_POST['tlf_client_pago_manual']) < 7){

                wc_add_notice('Teléfono inválido, no puede estar vacío ni menor a 7 dígitos.', 'error');
				return;

            }
            else if(empty($_POST['ref_value_pago_manual']) || strlen($_POST['ref_value_pago_manual']) < 6){

                wc_add_notice('Número de referencia inválido, no puede estar vacío ni menor a 6 dígitos.', 'error');
				return;

            }
            else {
                
                global $woocommerce;
                $moneda_actual = get_woocommerce_currency();
                echo 'La moneda actual es: ' . $moneda_actual;
                $order = new WC_Order( $order_id );

                // Procesar el pago aquí

                $api_url = get_api_url('validateManualPayment');

                $total = $woocommerce->cart->total;

                $montoSinComa = $total;

                $billing_email  = $order->get_billing_email();

                $idBanco = InfoBancaria::obtenerIdEntidadBancaria($_POST['banco_manual']);

                //Parametros de Entrada del Servicio a Invocar en formato JSON
                $request_data = array(
                    'rif' => get_option( 'payment_gateway_document_type') . get_option( 'payment_gateway_document'),
                    'payerDocument' => $_POST['tipo_documento_pago_manual']  . $_POST['documento_pago_manual'],
                    'debitPhone' => $_POST['codigo_tlf_pago_manual'] . $_POST['tlf_client_pago_manual'],
                    'referenceNumber' => $_POST['ref_value_pago_manual'],
                    'typeTransaction' => $_POST['tipo_trans'],
                    "bankCode" => $idBanco,
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
                    $response2 = mb_convert_encoding($response, 'UTF-8', $encoding);
                    $decoded_response = json_decode($response2, true);
                    error_log(json_encode($response2));
                    error_log(json_encode($decoded_response));
                    //Retorno del pago exitoso

                    // Cerrar la sesión cURL
                    curl_close($ch);

                    if($decoded_response['status'] === 'ERROR'){
                        if($decoded_response['data'] === 'Informacion bancaria no existe'){
                            wc_add_notice('ERROR - ' . 'Informacion bancaria no existe', 'error');
                        }
                        else if($decoded_response['data'] === 'mensaje sistema'){
                            wc_add_notice('ERROR - ' . $decoded_response['properties']['message'], 'error');
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
                    } else if($decoded_response['status'] === '500') {
                        
                        wc_add_notice('ERROR - ' . 'Error al hacer la conexión con el banco', 'error');

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
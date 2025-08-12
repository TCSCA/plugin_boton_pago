<?php

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action( 'plugins_loaded', 'pago_p2c_init' );

function enqueuePagoMovilJS($hook){
    if($hook != 'woocommerce_page_wc-settings'){
        return;
    }
    if(!isset($_GET["section"])){
        return;
    }
    else {
        //El valor de este section es el mismo que el id del payment gateway
        if($_GET["section"] != 'pago_movil') return;
    }
    wp_enqueue_script(
        'pagoMovilJS',
        plugins_url(
            '../assets/js/pagoMovilScript.js',
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
    wp_localize_script('pagoMovilJS', 'config_admin', $config);
}

add_action('admin_enqueue_scripts', 'enqueuePagoMovilJS');

function pago_p2c_init() {
    class PagoMovil extends WC_Payment_Gateway {
        public function __construct()
        {
            $this->id = "pago_movil";
            
            $this->method_title = __( 'Pago Movil', 'payment-gateway-woo' );
            
            $this->method_description = "Para poder validar el Pago Móvil de manera inmediata, se necesita que configures los campos 
            asociados a la información bancaria:";

            if(!empty(get_option('payment_gateway_bank'))){
                $entidad_bancaria = InfoBancaria::obtenerNombreEntidadBancaria(get_option('payment_gateway_bank'));

                error_log(get_option( 'payment_gateway_document_type') . get_option( 'payment_gateway_document'));

                // Obtener la URL actual
                $url_actual = $_SERVER['REQUEST_URI'];
                $partes_url = parse_url($url_actual);
                $ruta = $partes_url['path'];

                error_log('rutas:');
                // error_log($url_actual);
                error_log($ruta);

                if (strpos($ruta, 'finalizar-compra') !== false || strpos($ruta, 'checkout') !== false ) {
                    error_log('entro a la condicional por ruta');
                    InfoBancaria::obtenerCodigoQR(get_option( 'payment_gateway_document_type') . get_option( 'payment_gateway_document'));
                }

                if(get_option('commerce_qr') && get_option('commerce_qr') != ''){
                    // $this->description = '
                    // <p>Datos del Pago:</>
                    // <ul>
                    //     <li><strong>Banco: </strong>' . $entidad_bancaria . '</li>
                    //     <li><strong>Cédula / RIF: </strong>' . get_option( 'payment_gateway_document_type') . get_option('payment_gateway_document') . '</li>
                    //     <li><strong>Número de Teléfono: </strong>' . get_option('payment_gateway_phone_p2c') . '</li>
                    // </ul>
                    // <br><br>
                    // <p>Puede escanear el código QR para mayor facilidad de pago con su app del banco:</p>
                    // <br><br>
                    // <img src="data:image/png;base64,' . get_option('commerce_qr') . '" width="200" />';

                    $this->description = "Datos del Pago:\n" .
                        "  - Banco: " . esc_html($entidad_bancaria) . "\n" .
                        "  - Cédula / RIF: " . get_option( 'payment_gateway_document_type') . get_option('payment_gateway_document') . "\n" .
                        "  - Número de Teléfono: " . get_option('payment_gateway_phone_p2c') . "\n\n" .
                        "Puede escanear el código QR para mayor facilidad de pago con su app del banco:\n\n\n\n";
                }else{
                    $this->description = "Datos del Pago:\n" .
                        "  - Banco: " . esc_html($entidad_bancaria) . "\n" .
                        "  - Cédula / RIF: " . get_option( 'payment_gateway_document_type') . get_option('payment_gateway_document') . "\n" .
                        "  - Número de Teléfono: " . get_option('payment_gateway_phone_p2c');
                }
            }

            // $this->description = "Introduzca sus Datos:";

            $this->method_description = __( 'Para poder validar el Pago Móvil de manera inmediata, se necesita que configures los campos 
            asociados a la información bancaria:', 'payment-gateway-woo' );

            $this->supports = array(
                'products'
            );

            $this->has_fields = true;

            $image_url = plugins_url( '../assets/bancaribe.png', __FILE__ );

            $this->title = '
                <div class="report-title">
                    <h2>Reporte de Pago Móvil P2C</h2>
                    <img id="logo-bancaribe" src="' . $image_url . '" alt="Logo Bancaribe" >
                </div>
            ';

            $this->init_form_fields_p2c();
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

        public function init_form_fields_p2c(){

            $this->form_fields = array(
                'enabled' => array(
                    'title'   => 'Habilitar/Deshabilitar',
                    'type'    => 'checkbox',
                    'label'   => 'Habilitar Pago P2C',
                    'default' => 'no',
                ),
            );

        }

        public function payment_fields(){

            if ($description = $this->get_description()) {
                echo wpautop(wp_kses_post($description));
            }

            if(get_option('commerce_qr') && get_option('commerce_qr') != ''){
				?>
                    <img src="data:image/png;base64,<?php echo esc_html(get_option('commerce_qr')) ?> " width="200" alt="Código QR para pago" />
				<?php 
            }

            global $woocommerce;
            $total = $woocommerce->cart->total;

            $formattedTotal = number_format($total, 2, ',', '.');

            $entidades_bancarias = InfoBancaria::obtenerEntidadesBancarias();

            woocommerce_form_field('tipo_documento_p2c', array(
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

            woocommerce_form_field('documento_p2c', array(
                'type'          => 'text',
                'custom_attributes' => array(
                    'minlength'       =>  6,
                    'maxlength'       =>  10,
                ),
                'label'         => "<strong>" . __("Cédula / RIF", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), '');

            woocommerce_form_field('banco_p2c', array(
                'type'    => 'select',
                'options' => $entidades_bancarias,
                'label'         => "<strong>" . __("Banco", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), '');

            woocommerce_form_field('codigo_tlf_p2c', array(
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

            woocommerce_form_field('tlf_client_p2c', array(
                'type'          => 'text',
                'custom_attributes' => array(
                    'minlength'       =>  7,
                    'maxlength'       =>  7,
                ),
                'label'         => "<strong>" . __("", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-last'),
            ), '');

            woocommerce_form_field('amount_p2c', array(
                'type'          => 'text',
                'label'         => "<strong>" . __("Monto", "payment-gateway-woo") . "</strong>",
                'class'         => array('form-row-wide'),
                'required'      => true,
            ), $formattedTotal);

            woocommerce_form_field('ref_value', array(
                'type'          => 'text',
                'label'         => "<strong>" . __("Referencia Pago Móvil", "payment-gateway-woo") . "</strong>",
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

            //local
            // $api_url_verificacion_p2c = 'http://172.16.90.117:8080/api/validateCommerceLicence';
            //pre-produccion
            // $api_url_verificacion_p2c = 'http://172.30.145.250:4000/api/validateCommerceLicence';
            //desarrollo/produccion
            $api_url_verificacion_p2c = 'http://localhost:4000/api/validateCommerceLicence';

            $headers_verificacion_p2c = array(
                'Content-Type: application/json',
                'User-Agent: Mozilla/5.0',
                'KEY: key12345'
            );

            error_log(get_option( 'payment_gateway_document_type') . get_option( 'payment_gateway_document'));

            $request_data_verificacion_p2c = array(
                'rif' => get_option( 'payment_gateway_document_type') . get_option( 'payment_gateway_document')
            );

            $json_data_verificacion_p2c = json_encode($request_data_verificacion_p2c);

            $ch_verificacion_p2c = curl_init();
            curl_setopt($ch_verificacion_p2c, CURLOPT_URL, $api_url_verificacion_p2c);
            curl_setopt($ch_verificacion_p2c, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch_verificacion_p2c, CURLOPT_POST, 1);
            curl_setopt($ch_verificacion_p2c, CURLOPT_POSTFIELDS, $json_data_verificacion_p2c);
            curl_setopt($ch_verificacion_p2c, CURLOPT_HTTPHEADER, $headers_verificacion_p2c);

            $response_verificacion_p2c = curl_exec($ch_verificacion_p2c);

            if (curl_errno($ch_verificacion_p2c)) {
                error_log(json_encode(curl_error($ch_verificacion_p2c)));
                
                wc_add_notice('Error al realizar la solicitud', 'error');

                curl_close($ch_verificacion_p2c);

                return;
            } else {
                $encoding_verificacion_p2c = mb_detect_encoding($response_verificacion_p2c, 'UTF-8, ISO-8859-1, Windows-1252', true);
                $response_verificada = mb_convert_encoding($response_verificacion_p2c, 'UTF-8', $encoding_verificacion_p2c);
                $decoded_response_verificacion_p2c = json_decode($response_verificada, true);

                // Procesar la respuesta
                error_log(json_encode($response_verificada));
                error_log(json_encode($decoded_response_verificacion_p2c));

                curl_close($ch_verificacion_p2c);

                if($decoded_response_verificacion_p2c['status'] === 'ERROR'){
                    wc_add_notice('ERROR - ' . $decoded_response_verificacion_p2c['data'], 'error');
                    return;
                } 
            }

            error_log('verificacion comercio fin');

            /////////////////////////////////VERIFICACION DE FECHAS EN COMERCIO FINAL////////////////////////////////////////////
            
            $this->init_form_fields_p2c();

            if(empty($_POST['documento_p2c']) || strlen($_POST['documento_p2c']) < 6){

                wc_add_notice('Documento inválido, no puede ser vacío ni menor a 6 dígitos.', 'error');
				return;

            } else if(empty($_POST['tlf_client_p2c']) || strlen($_POST['tlf_client_p2c']) < 7){

                wc_add_notice('Teléfono inválido, no puede estar vacío ni menor a 7 dígitos.', 'error');
				return;

            }
            else if(empty($_POST['ref_value']) || strlen($_POST['ref_value']) < 6){

                wc_add_notice('Número de referencia inválido, no puede estar vacío ni menor a 6 dígitos.', 'error');
				return;

            }
            else {
                
                global $woocommerce;
                $moneda_actual = get_woocommerce_currency();
                echo 'La moneda actual es: ' . $moneda_actual;
                $order = new WC_Order( $order_id );

                $idBanco = InfoBancaria::obtenerIdEntidadBancaria($_POST['banco_p2c']);

                // Procesar el pago aquí

                //local
                // $api_url = 'http://172.16.90.117:8099/api/validatePaymentP2c';
                //pre-produccion
                $api_url = 'http://172.30.145.250:4000/api/validatePaymentP2c';
                //desarrollo/produccion
                // $api_url = 'http://localhost:4000/api/validatePaymentP2c';

                $total = $woocommerce->cart->total;

                $billing_email  = $order->get_billing_email();

                $montoSinComa = $total;

                error_log($billing_email);

                //Parametros de Entrada del Servicio a Invocar en formato JSON
                $request_data = array(
                    'rif' => get_option( 'payment_gateway_document_type') . get_option( 'payment_gateway_document'),
                    'payerDocument' => $_POST['tipo_documento_p2c']  . $_POST['documento_p2c'],
                    'debitPhone' => $_POST['codigo_tlf_p2c'] . $_POST['tlf_client_p2c'],
                    'referenceNumber' => $_POST['ref_value'],
                    'transactionAmount' => $montoSinComa,
                    'bankPayment' => $idBanco,
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
                $chp2c = curl_init();
                curl_setopt($chp2c, CURLOPT_URL, $api_url);
                curl_setopt($chp2c, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($chp2c, CURLOPT_POST, 1);
                curl_setopt($chp2c, CURLOPT_POSTFIELDS, $json_data);
                curl_setopt($chp2c, CURLOPT_HTTPHEADER, $headers);

                // Ejecutar la solicitud cURL y obtener la respuesta
                $responsep2c = curl_exec($chp2c);
    
                // Verificar si hay errores en la solicitud
                if (curl_errno($chp2c)) {
                    error_log(json_encode(curl_error($chp2c)));
                    
                    wc_add_notice('Error al realizar la solicitud', 'error');

                    // Cerrar la sesión cURL
                    curl_close($chp2c);

                    return;
                } else {
                    // Procesar la respuesta

                    $encoding = mb_detect_encoding($responsep2c, 'UTF-8, ISO-8859-1, Windows-1252', true);
                    $responsep2c2 = mb_convert_encoding($responsep2c, 'UTF-8', $encoding);
                    $decoded_response = json_decode($responsep2c2, true);
                    error_log(json_encode($responsep2c2));
                    error_log(json_encode($decoded_response));

                    //Retorno del pago exitoso

                    // Cerrar la sesión cURL
                    curl_close($chp2c);

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

    // function add_to_woo_pm_gateway( $methods ) {
    //     $methods[] = 'GenericPagoMovil';
    //     return $methods;
    // }
}
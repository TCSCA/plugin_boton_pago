<?php
/*
Plugin Name: Payment Gateway
Plugin URI: http://localhost
Description: Plugin de métodos de pago para WooCommerce
Version: 1.7.9
Requires at least: 6
Requires PHP: 7
Author: Technology Consulting Solutions (T.C.S)
Author URI: https://tcs.com.ve/
text-domain: payment-gateway-woo
*/

include_once 'metodosPago/pagoMovil.php';
include_once 'metodosPago/pagoC2P.php';
include_once 'metodosPago/pagoManual.php';
include_once 'metodosPago/transferenciaInmediata.php';
include_once 'metodosPago/pagoTarjeta.php';
include_once 'config/config.php';
// include_once 'payment-qr-woo.php';

function activarPlugin(){
    crear_tablas_base_datos();
    agregar_registros_iniciales();
}

function desactivarPlugin(){
    flush_rewrite_rules();
}

function crear_tablas_base_datos() {
        //En caso de necesitar crear una nueva tabla con el plugins, al activarlo se crea la tabla
        global $wpdb;

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}entidad_bancaria(
            id INT NOT NULL AUTO_INCREMENT,
            nombre VARCHAR(45) NOT NULL,
            codigo_bin VARCHAR(4) NOT NULL,
            isIntegrado BOOLEAN NOT NULL,
            status BOOLEAN DEFAULT false,
            PRIMARY KEY (id));";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            
        $wpdb->query($sql);
}

function insertar_datos_configuracion($data){

    // Manejo de payment_gateway_account_number
    if (!get_option('payment_gateway_account_number')) {
        add_option('payment_gateway_account_number', $data['account_number']);
        update_option('payment_gateway_account_number', $data['account_number']);
    } else {
        update_option('payment_gateway_account_number', $data['account_number']);
    }

    // Manejo de payment_gateway_document_type
    if (!get_option('payment_gateway_document_type')) {
        add_option('payment_gateway_document_type', $data['document_type']);
        update_option('payment_gateway_document_type', $data['document_type']);
    } else {
        update_option('payment_gateway_document_type', $data['document_type']);
    }

    // Manejo de payment_gateway_commerce_name
    if (!get_option('payment_gateway_commerce_name')) {
        add_option('payment_gateway_commerce_name', $data['commerce_name']);
        update_option('payment_gateway_commerce_name', $data['commerce_name']);
    } else {
        update_option('payment_gateway_commerce_name', $data['commerce_name']);
    }

    // Manejo de payment_gateway_bancaribe_consumer_secret
    if (!get_option('payment_gateway_bancaribe_consumer_secret')) {
        add_option('payment_gateway_bancaribe_consumer_secret', $data['consumer_secret']);
        update_option('payment_gateway_bancaribe_consumer_secret', $data['consumer_secret']);
    } else {
        update_option('payment_gateway_bancaribe_consumer_secret', $data['consumer_secret']);
    }

    // Manejo de payment_gateway_bancaribe_consumer_key
    if (!get_option('payment_gateway_bancaribe_consumer_key')) {
        add_option('payment_gateway_bancaribe_consumer_key', $data['consumer_key']);
        update_option('payment_gateway_bancaribe_consumer_key', $data['consumer_key']);
    } else {
        update_option('payment_gateway_bancaribe_consumer_key', $data['consumer_key']);
    }

    // Manejo de payment_gateway_creditcard_consumer_secret
    if (!get_option('payment_gateway_creditcard_consumer_secret')) {
        add_option('payment_gateway_creditcard_consumer_secret', $data['consumer_secret_creditcard']);
        update_option('payment_gateway_creditcard_consumer_secret', $data['consumer_secret_creditcard']);
    } else {
        update_option('payment_gateway_creditcard_consumer_secret', $data['consumer_secret_creditcard']);
    }

    // Manejo de payment_gateway_creditcard_consumer_key
    if (!get_option('payment_gateway_creditcard_consumer_key')) {
        add_option('payment_gateway_creditcard_consumer_key', $data['consumer_key_creditcard']);
        update_option('payment_gateway_creditcard_consumer_key', $data['consumer_key_creditcard']);
    } else {
        update_option('payment_gateway_creditcard_consumer_key', $data['consumer_key_creditcard']);
    }

    // Manejo de payment_gateway_confirmation_key
    if (!get_option('payment_gateway_confirmation_key')) {
        add_option('payment_gateway_confirmation_key', $data['confirmation_key']);
        update_option('payment_gateway_confirmation_key', $data['confirmation_key']);
    } else {
        update_option('payment_gateway_confirmation_key', $data['confirmation_key']);
    }

    // Manejo de payment_gateway_phone_p2c
    if (!get_option('payment_gateway_phone_p2c')) {
        add_option('payment_gateway_phone_p2c', $data['phone_p2c']);
        update_option('payment_gateway_phone_p2c', $data['phone_p2c']);
    } else {
        update_option('payment_gateway_phone_p2c', $data['phone_p2c']);
    }

    // Manejo de payment_gateway_document
    if (!get_option('payment_gateway_document')) {
        add_option('payment_gateway_document', $data['document']);
        update_option('payment_gateway_document', $data['document']);
    } else {
        update_option('payment_gateway_document', $data['document']);
    }

    // Manejo de payment_gateway_bank
    if (!get_option('payment_gateway_bank')) {
        add_option('payment_gateway_bank', $data['bank']);
        update_option('payment_gateway_bank', $data['bank']);
    } else {
        update_option('payment_gateway_bank', $data['bank']);
    }

    // Manejo de payment_gateway_hash
    if (!get_option('payment_gateway_hash')) {
        add_option('payment_gateway_hash', $data['hash']);
        update_option('payment_gateway_hash', $data['hash']);
    } else {
        update_option('payment_gateway_hash', $data['hash']);
    }

    // Manejo de payment_gateway_creditcard_checkbox
    if (!get_option('payment_gateway_creditcard_checkbox')) {
        add_option('payment_gateway_creditcard_checkbox', $data['check']);
        update_option('payment_gateway_creditcard_checkbox', $data['check']);
    } else {
        update_option('payment_gateway_creditcard_checkbox', $data['check']);
    }
}

function obtener_datos_configuracion(){
    global $wpdb;

    $sql = "SELECT * FROM {$wpdb->prefix}configuracion_usuario WHERE id_config=1";
        
    $config = $wpdb->get_row($sql);
    
    if($config == null){
        return null;
    }

    return array(
        'id_config' => $config->id_config,
        'user' => $config->user,
        'user_password' => $config->user_password,
        'token' => $config->token,
        'activation_code' => $config->activation_code,
        'document' => $config->document,
    );
}

function agregar_registros_iniciales() {
    global $wpdb;

    $nombre_tabla = $wpdb->prefix . 'entidad_bancaria';

    $registros_existen = $wpdb->get_var("SELECT COUNT(*) FROM $nombre_tabla") > 0;

    if (!$registros_existen) {

        $datos = array(
            array(
                'nombre' => 'Banco de Venezuela, S.A. Banco Universal', 
                'codigo_bin' => '0102', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Banco Venezolano de Crédito, S.A. Banco Universal', 
                'codigo_bin' => '0104', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Banco Mercantil C.A., Banco Universal', 
                'codigo_bin' => '0105', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Banco Provincial, S.A. Banco Universal', 
                'codigo_bin' => '0108', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Bancaribe, C.A. Banco Universal', 
                'codigo_bin' => '0114', 
                'isIntegrado' => true, 
                'status' => true),
            array(
                'nombre' => 'Banco Exterior, C.A. Banco Universal', 
                'codigo_bin' => '0115', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Banco Caroní C.A., Banco Universal', 
                'codigo_bin' => '0128', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Banesco Banco Universal, C.A.', 
                'codigo_bin' => '0134', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Banco Sofitasa Banco Universal, C.A .', 
                'codigo_bin' => '0137', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Banco Plaza, Banco universal', 
                'codigo_bin' => '0138', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Banco de la Gente Emprendedora C.A', 
                'codigo_bin' => '0146', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Banco Fondo Común, C.A Banco Universal', 
                'codigo_bin' => '0151', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => '100% Banco, Banco Comercial, C.A', 
                'codigo_bin' => '0156', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'DelSur, Banco Universal C.A.', 
                'codigo_bin' => '0157', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Banco del Tesoro C.A., Banco Universal', 
                'codigo_bin' => '0163', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Banco Agrícola de Venezuela C.A., Banco Universal.', 
                'codigo_bin' => '0166', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Bancrecer S.A., Banco Microfinanciero.', 
                'codigo_bin' => '0168', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Mi Banco, Banco Microfinanciero, C.A.', 
                'codigo_bin' => '0169', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Banco Activo C.A., Banco Universal.', 
                'codigo_bin' => '0171', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Bancamiga Banco Universal, C.A.', 
                'codigo_bin' => '0172', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Banco Internacional de Desarrollo C.A., Banco Universal.', 
                'codigo_bin' => '0173', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Banplus Banco Universal, C.A.', 
                'codigo_bin' => '0174', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Banco Digital de los Trabajadores, Banco Universal C.A.', 
                'codigo_bin' => '0175', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Banco de la Fuerza Armada Nacional Bolivariana, B.U.', 
                'codigo_bin' => '0177', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'N58 Banco Digital, Banco Microfinanciero.', 
                'codigo_bin' => '0178', 
                'isIntegrado' => false, 
                'status' => true),
            array(
                'nombre' => 'Banco Nacional de Crédito C.A., Banco Universal.', 
                'codigo_bin' => '0191', 
                'isIntegrado' => false, 
                'status' => true),
        );

        foreach ($datos as $dato) {
            $wpdb->insert($nombre_tabla, $dato);
        }
    }
}

/* Vinculando los botones de wordpress con las funciones correspondientes */
register_activation_hook(__FILE__, 'activarPlugin');
register_deactivation_hook(__FILE__, 'desactivarPlugin');

add_action('plugins_loaded', 'woocommerce_ver_check');
function woocommerce_ver_check() {
    if (defined('WC_VERSION')) return WC_VERSION; 
}

// function declare_cart_checkout_blocks_compatibility() {
//     // Check if the required class exists
//     if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
//         // Declare compatibility for 'cart_checkout_blocks'
//         \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
//     }
// }
// // Hook the custom function to the 'before_woocommerce_init' action
// add_action('before_woocommerce_init', 'declare_cart_checkout_blocks_compatibility');

// // Hook the custom function to the 'woocommerce_blocks_loaded' action
// add_action( 'woocommerce_blocks_loaded', 'pm_register_order_approval_payment_method_type' );

// /**
//  * Custom function to register a payment method type

//  */
// function pm_register_order_approval_payment_method_type() {
//     // Check if the required class exists
//     if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
//         return;
//     }

//     // Include the custom Blocks Checkout class
//     require_once plugin_dir_path(__FILE__) . 'paymentGatewayBlock.php';

//     // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
//     add_action(
//         'woocommerce_blocks_payment_method_type_registration',
//         function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
//             // Register an instance of My_Custom_Gateway_Blocks
//             $payment_method_registry->register( new Pago_Movil_Blocks );
//         }
//     );
// }

function agregar_pago_c2p($methods) {
    $methods[] = 'PagoC2P';
    return $methods;
}

function agregar_pago_manual($methods) {
    $methods[] = 'PagoManual';
    return $methods;
}

function agregar_transf_inmediata($methods) {
    $methods[] = 'TransferenciaInmediata';
    return $methods;
}

function agregar_pago_tarjeta($methods) {
    $methods[] = 'PagoTarjeta';
    return $methods;
}

function agregar_pago_movil($methods) {
    $methods[] = 'PagoMovil';
    return $methods;
}

add_filter('woocommerce_payment_gateways', 'agregar_pago_movil');
add_filter('woocommerce_payment_gateways', 'agregar_pago_c2p');
add_filter('woocommerce_payment_gateways', 'agregar_pago_manual');
add_filter('woocommerce_payment_gateways', 'agregar_transf_inmediata');
add_filter('woocommerce_payment_gateways', 'agregar_pago_tarjeta');

class InfoBancaria {
    public static function obtenerEntidadesBancarias() {
        global $wpdb;
        
        $nombre_tabla = $wpdb->prefix . 'entidad_bancaria';
    
        // Consulta para obtener datos de la tabla
        $resultados = $wpdb->get_results("SELECT id, nombre, codigo_bin FROM $nombre_tabla 
            WHERE status = true", ARRAY_A);
    
        $opciones = array();
    
        foreach ($resultados as $fila) {
            $opciones[$fila['id']] = $fila['codigo_bin'] . ' - ' . $fila['nombre'];
        }
    
        return $opciones;
    }

    public static function obtenerNombreEntidadBancaria(int $numero) {
        global $wpdb;
        
        $nombre_tabla = $wpdb->prefix . 'entidad_bancaria';
    
        // Consulta para obtener datos de la tabla
        $resultados = $wpdb->get_results("SELECT nombre FROM $nombre_tabla 
            WHERE id = $numero", ARRAY_A);
    
        return $resultados[0]["nombre"];
    }

    public static function obtenerIdEntidadBancaria(int $numero) {
        global $wpdb;
        
        $nombre_tabla = $wpdb->prefix . 'entidad_bancaria';
    
        // Consulta para obtener datos de la tabla
        $resultados = $wpdb->get_results("SELECT codigo_bin FROM $nombre_tabla 
            WHERE id = $numero", ARRAY_A);

        error_log(json_encode($resultados));

    
        return $resultados[0]["codigo_bin"];
    }

    public static function obtenerEntidadesBancariasIntegradas() {
        global $wpdb;
        
        $nombre_tabla = $wpdb->prefix . 'entidad_bancaria';
    
        // Consulta para obtener datos de la tabla
        $resultados = $wpdb->get_results("SELECT id, nombre, codigo_bin FROM $nombre_tabla 
            WHERE isIntegrado = true and status = true", ARRAY_A);
    
        $opciones = array();
    
        foreach ($resultados as $fila) {
            $opciones[$fila['id']] = $fila['codigo_bin'] . ' - ' . $fila['nombre'];
        }
    
        return $opciones;
    }

    public static function obtenerCodigoQR(string $rif) {
        error_log('entro al metodo del qr');
        
        // Load configuration
        require_once plugin_dir_path(dirname(__FILE__)) . 'config/config.php';
        
        $api_url = get_api_url('downloadQrByCommerce');

        $request_data = array(
            'rif' => $rif
        );

        error_log(json_encode($request_data));

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
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Ejecutar la solicitud cURL y obtener la respuesta
        $response = curl_exec($ch);

        $encoding = mb_detect_encoding($response, 'UTF-8, ISO-8859-1, Windows-1252', true);
        $response2 = mb_convert_encoding($response, 'UTF-8', $encoding);
        $decoded_response = json_decode($response2, true);

        if (curl_errno($ch)) {
            error_log(json_encode(curl_error($ch)));
            
            wc_add_notice('Error al realizar la solicitud', 'error');

            // Cerrar la sesión cURL
            curl_close($ch);

            return;
        } else {
            // Procesar la respuesta

            error_log('respuestas del metodo qr');

            error_log(json_encode($response2));
            error_log(json_encode($decoded_response));

            // Cerrar la sesión cURL
            curl_close($ch);

            if($decoded_response['status'] === 'ERROR'){
                if (!get_option('commerce_qr')) {
                    add_option('commerce_qr', '');
                    update_option('commerce_qr', '');
                } else {
                    update_option('commerce_qr', '');
                }

                error_log('qr error');
                error_log(get_option('commerce_qr'));
                return;
            } else if($decoded_response['status'] === 'SUCCESS') {

                if (!get_option('commerce_qr')) {
                    add_option('commerce_qr', $decoded_response['data']);
                    update_option('commerce_qr', $decoded_response['data']);
                } else {
                    update_option('commerce_qr', $decoded_response['data']);
                }

                error_log('qr succ');
                error_log(get_option('commerce_qr'));
                return;
                
            } else {
                wc_add_notice('ERROR', 'error');
                error_log(get_option('commerce_qr'));
                return;
            }
        } 
    }

}

add_action( 'admin_menu', 'createMenu');

function createMenu(){
    add_menu_page(
        'Configuración de Pagos',
        'Métodos de Pago',
        'manage_options',
        'payment_gateway_menu',
        'showAdminContent',
        plugin_dir_url( __FILE__ ).'assets/img/icon.png',
        '1'
    );
}

add_action('admin_init', 'registerAndBuildFields');

function showAdminContent(){
    ?>
        <div class="wrap">
            <h1>Configuración de Métodos de Pago</h1>
            <form method="POST" action="#">
    <?php
        settings_fields( 'payment_gateway_settings' );
        do_settings_sections( 'payment_gateway_settings' );
        submit_button('Guardar Cambios');
    ?>
            </form>
        </div>
    <?php
}

function description(){
    echo "<p>Configura los datos de tu pasarela de pagos y métodos de pago disponibles.</p>";
}

function registerAndBuildFields() {

    $entidades_bancarias = InfoBancaria::obtenerEntidadesBancarias();

    add_settings_section(
        // ID used to identify this section and with which to register options
        'payment_gateway_general_section', 
        // Title to be displayed on the administration page
        '',  
        // Callback used to render the description of the section
        'description',    
        // Page on which to add this section of options
        'payment_gateway_settings'                   
    );

    add_settings_field(
        'payment_gateway_commerce_name',
        'Nombre del Comercio',
        'plugin_name_render_settings_field',
        'payment_gateway_settings',
        'payment_gateway_general_section',
        array (
            'type'      => 'input',
            'subtype'   => 'text',
            'id'    => 'payment_gateway_commerce_name',
            'name'      => 'payment_gateway_commerce_name',
            'required' => 'true',
            'get_options_list' => '',
            'value_type'=>'normal',
            'wp_data' => 'option'
        ),
    );


    register_setting(
        'payment_gateway_settings',
        'payment_gateway_commerce_name'
    );

    add_settings_field(
        'payment_gateway_document_type',
        'Tipo de documento',
        'plugin_name_render_settings_field',
        'payment_gateway_settings',
        'payment_gateway_general_section',
        array (
            'type'      => 'select',
            'id'    => 'payment_gateway_document_type',
            'name'      => 'payment_gateway_document_type',
            'required' => 'true',
            'get_options_list' => '',
            'value_type'=>'normal',
            'wp_data' => 'option',
            'options' => array(
                'V' => 'V - Venezolano',
                'E' => 'E - Extranjero',
                'J' => 'J - Jurídico',
            )
        ),
    );

    register_setting(
        'payment_gateway_settings',
        'payment_gateway_document_type'
    );

    add_settings_field(
        'payment_gateway_document',
        'RIF',
        'plugin_name_render_settings_field',
        'payment_gateway_settings',
        'payment_gateway_general_section',
        array (
            'type'      => 'input',
            'subtype'   => 'text',
            'id'    => 'payment_gateway_document',
            'name'      => 'payment_gateway_document',
            'required' => 'true',
            'get_options_list' => '',
            'value_type'=>'normal',
            'wp_data' => 'option',
        ),
    );

    register_setting(
        'payment_gateway_settings',
        'payment_gateway_document'
    );

    add_settings_field(
        'payment_gateway_confirmation_key',
        'Código Activación',
        'plugin_name_render_settings_field',
        'payment_gateway_settings',
        'payment_gateway_general_section',
        array (
            'type'      => 'input',
            'subtype'   => 'text',
            'id'    => 'payment_gateway_confirmation_key',
            'name'      => 'payment_gateway_confirmation_key',
            'required' => 'true',
            'get_options_list' => '',
            'value_type'=>'normal',
            'wp_data' => 'option'
        ),
    );

    register_setting(
        'payment_gateway_settings',
        'payment_gateway_confirmation_key'
    );

    add_settings_field(
        'payment_gateway_bank',
        'Banco',
        'plugin_name_render_settings_field',
        'payment_gateway_settings',
        'payment_gateway_general_section',
        array (
            'type'      => 'select',
            'id'    => 'payment_gateway_bank',
            'name'      => 'payment_gateway_bank',
            'required' => 'true',
            'get_options_list' => '',
            'value_type'=>'normal',
            'wp_data' => 'option',
            'options' => $entidades_bancarias
        ),
    );

    register_setting(
        'payment_gateway_settings',
        'payment_gateway_bank'
    );

    add_settings_field(
        'payment_gateway_account_number',
        'Número de cuenta',
        'plugin_name_render_settings_field',
        'payment_gateway_settings',
        'payment_gateway_general_section',
        array (
            'type'      => 'input',
            'subtype'   => 'text',
            'id'    => 'payment_gateway_account_number',
            'name'      => 'payment_gateway_account_number',
            'required' => 'true',
            'get_options_list' => '',
            'value_type'=>'normal',
            'wp_data' => 'option'
        ),
    );

    register_setting(
        'payment_gateway_settings',
        'payment_gateway_account_number'
    );

    add_settings_field(
        'payment_gateway_phone_p2c',
        'Teléfono (P2C)',
        'plugin_name_render_settings_field',
        'payment_gateway_settings',
        'payment_gateway_general_section',
        array (
            'type'      => 'input',
            'subtype'   => 'text',
            'id'    => 'payment_gateway_phone_p2c',
            'name'      => 'payment_gateway_phone_p2c',
            'required' => 'false',
            'get_options_list' => '',
            'value_type'=>'normal',
            'wp_data' => 'option'
        ),
    );

    register_setting(
        'payment_gateway_settings',
        'payment_gateway_phone_p2c'
    );

    add_settings_field(
        'payment_gateway_bancaribe_consumer_key',
        'Consumer Key Bancaribe',
        'plugin_name_render_settings_field',
        'payment_gateway_settings',
        'payment_gateway_general_section',
        array (
            'type'      => 'input',
            'subtype'   => 'password',
            'id'    => 'payment_gateway_bancaribe_consumer_key',
            'name'      => 'payment_gateway_bancaribe_consumer_key',
            'required' => 'false',
            'get_options_list' => '',
            'value_type'=>'normal',
            'wp_data' => 'option'
        ),
    );

    register_setting(
        'payment_gateway_settings',
        'payment_gateway_bancaribe_consumer_key'
    );

    add_settings_field(
        'payment_gateway_bancaribe_consumer_secret',
        'Consumer Secret Bancaribe',
        'plugin_name_render_settings_field',
        'payment_gateway_settings',
        'payment_gateway_general_section',
        array (
            'type'      => 'input',
            'subtype'   => 'password',
            'id'    => 'payment_gateway_bancaribe_consumer_secret',
            'name'      => 'payment_gateway_bancaribe_consumer_secret',
            'required' => 'false',
            'get_options_list' => '',
            'value_type'=>'normal',
            'wp_data' => 'option'
        ),
    );

    register_setting(
        'payment_gateway_settings',
        'payment_gateway_bancaribe_consumer_secret'
    );

    add_settings_field(
        'payment_gateway_hash',
        'Hash',
        'plugin_name_render_settings_field',
        'payment_gateway_settings',
        'payment_gateway_general_section',
        array (
            'type'      => 'input',
            'subtype'   => 'text',
            'id'    => 'payment_gateway_hash',
            'name'      => 'payment_gateway_hash',
            'required' => 'false',
            'get_options_list' => '',
            'value_type'=>'normal',
            'wp_data' => 'option'
        ),
    );


    register_setting(
        'payment_gateway_settings',
        'payment_gateway_hash'
    );

    add_settings_field(
        'payment_gateway_creditcard_checkbox',
        'CreditCard',
        'plugin_name_render_settings_field',
        'payment_gateway_settings',
        'payment_gateway_general_section',
        array (
            'type'      => 'input',
            'subtype'   => 'checkbox',
            'id'    => 'payment_gateway_creditcard_checkbox',
            'name'      => 'payment_gateway_creditcard_checkbox',
            'required' => 'false',
            'get_options_list' => '',
            'value_type'=>'normal',
            'wp_data' => 'option'
        ),
    );


    register_setting(
        'payment_gateway_settings',
        'payment_gateway_creditcard_checkbox'
    );

    add_settings_field(
        'payment_gateway_creditcard_consumer_key',
        'Consumer Key CreditCard',
        'plugin_name_render_settings_field',
        'payment_gateway_settings',
        'payment_gateway_general_section',
        array (
            'type'      => 'input',
            'subtype'   => 'password',
            'id'    => 'payment_gateway_creditcard_consumer_key',
            'name'      => 'payment_gateway_creditcard_consumer_key',
            'required' => 'false',
            'get_options_list' => '',
            'value_type'=>'normal',
            'wp_data' => 'option'
        ),
    );


    register_setting(
        'payment_gateway_settings',
        'payment_gateway_creditcard_consumer_key'
    );

    add_settings_field(
        'payment_gateway_creditcard_consumer_secret',
        'Consumer Secret CreditCard',
        'plugin_name_render_settings_field',
        'payment_gateway_settings',
        'payment_gateway_general_section',
        array (
            'type'      => 'input',
            'subtype'   => 'password',
            'id'    => 'payment_gateway_creditcard_consumer_secret',
            'name'      => 'payment_gateway_creditcard_consumer_secret',
            'required' => 'false',
            'get_options_list' => '',
            'value_type'=>'normal',
            'wp_data' => 'option'
        ),
    );


    register_setting(
        'payment_gateway_settings',
        'payment_gateway_creditcard_consumer_secret'
    );
}

function plugin_name_render_settings_field($args) {
    if($args['wp_data'] == 'option'){
        $wp_data_value = get_option($args['name']);
    } elseif($args['wp_data'] == 'post_meta'){
        $wp_data_value = get_post_meta($args['post_id'], $args['name'], true );
    }

    switch ($args['type']) {

        case 'input':
            $value = ($args['value_type'] == 'serialized') ? serialize($wp_data_value) : $wp_data_value;
            if($args['subtype'] != 'checkbox'){
                $prependStart = (isset($args['prepend_value'])) ? '<div class="input-prepend"> <span class="add-on">'.$args['prepend_value'].'</span>' : '';
                $prependEnd = (isset($args['prepend_value'])) ? '</div>' : '';
                $step = (isset($args['step'])) ? 'step="'.$args['step'].'"' : '';
                $min = (isset($args['min'])) ? 'min="'.$args['min'].'"' : '';
                $max = (isset($args['max'])) ? 'max="'.$args['max'].'"' : '';
                if(isset($args['disabled'])){
                    // hide the actual input bc if it was just a disabled input the informaiton saved in the database would be wrong - bc it would pass empty values and wipe the actual information
                    echo $prependStart.'<input type="'.$args['subtype'].'" id="'.$args['id'].'_disabled" '.$step.' '.$max.' '.$min.' name="'.$args['name'].'_disabled" size="40" disabled value="' . esc_attr($value) . '" /><input type="hidden" id="'.$args['id'].'" '.$step.' '.$max.' '.$min.' name="'.$args['name'].'" size="40" value="' . esc_attr($value) . '" />'.$prependEnd;
                } else {
                    echo $prependStart . (isset($args['prefix']) ? $args['prefix'] : '') . '<input type="'.$args['subtype'].'" id="'.$args['id'].'" "'.$args['required'].'" '.$step.' '.$max.' '.$min.' name="'.$args['name'].'" size="'. (isset($args['prefix_size']) ? $args['prefix_size'] : '40') . '" value="' . esc_attr($value) . '" />'.$prependEnd;
                }

            } else {
                $checked = ($value) ? 'checked' : '';
                echo '<input type="'.$args['subtype'].'" id="'.$args['id'].'" "'.$args['required'].'" name="'.$args['name'].'" size="40" value="1" '.$checked.' />';
            }
            break;
        case 'select':
            $prependStart = (isset($args['prepend_value'])) ? '<div class="input-prepend"> <span class="add-on">'.$args['prepend_value'].'</span>' : '';
            $prependEnd = (isset($args['prepend_value'])) ? '</div>' : '';
            $value = ($args['value_type'] == 'serialized') ? serialize($wp_data_value) : $wp_data_value;
            $options = '<option value="" hidden '. (empty($value) ? 'selected' : '') . '>Seleccione una opción</option>';
            if(isset($args['options'])){
                foreach ($args['options'] as $option_value => $option_label) {
                    $selected = ((int)$value === (int)$option_value) ? 'selected' : '';
                    $options .= '<option value="' . esc_attr($option_value) . '" ' . $selected . '>' . esc_html($option_label) . '</option>';
                }
            }
            echo $prependStart. '<select style="width: 305px;" id="' . $args['id'] . '" name="' . $args['name'] . '">' . $options . '</select>'. $prependEnd;
            break;
        default:
            break;
    }
}

function enqueueAdminJS($hook){
    if($hook != "toplevel_page_boton_de_pagos_menu"){
        return ;
    }
    wp_enqueue_script('AdminJS',plugins_url('assets/js/submitScript.js',__FILE__),array('jquery'),time());
    wp_localize_script('AdminJS','SolicitudesAjax',[
        'url' => admin_url('admin-ajax.php'),
        'seguridad' => wp_create_nonce('seg')
    ]);
}
add_action('admin_enqueue_scripts','enqueueAdminJS');


function generalStyles() {

    //Register CSS
    wp_register_style('generalStyles', plugins_url('assets/css/generalStyles.css', __FILE__));

    wp_enqueue_style ( 'generalStyles' );
}
add_action( 'wp_enqueue_scripts', 'generalStyles' );

function miscStyles() {

    //Register CSS
    wp_register_style('miscStyles', plugins_url('assets/css/misc.css', __FILE__));

    //Use it!
    wp_enqueue_style ( 'miscStyles' );
}
add_action( 'wp_enqueue_scripts', 'miscStyles' );

function enqueue_mask_script() {

    wp_enqueue_script('mask-js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js', array('jquery'), '1.14.16', true);

}

add_action('wp_enqueue_scripts', 'enqueue_mask_script');

function enqueueP2CValidations(){
    wp_enqueue_script( 'p2cValidations', plugins_url( 'assets/js/validations/p2cValidations.js' , __FILE__ ), array( 'jquery' ) ); 
}

add_action('wp_enqueue_scripts','enqueueP2CValidations');


function enqueueTarjetaValidations(){
    wp_enqueue_script( 'tarjetaValidations', plugins_url( 'assets/js/validations/tarjetaValidations.js' , __FILE__ ), array( 'jquery' ) ); 
}

add_action('wp_enqueue_scripts','enqueueTarjetaValidations');


function enqueueC2PValidations(){
    wp_enqueue_script( 'c2pValidations', plugins_url( 'assets/js/validations/c2pValidations.js' , __FILE__ ), array( 'jquery' ) ); 
}

add_action('wp_enqueue_scripts','enqueueC2PValidations');


function enqueuePagoManualValidations(){
    wp_enqueue_script( 'pagoManualValidations', plugins_url( 'assets/js/validations/pagoManualValidations.js' , __FILE__ ), array( 'jquery' ) ); 
}

add_action('wp_enqueue_scripts','enqueuePagoManualValidations');


function enqueueTransferValidations(){
    wp_enqueue_script( 'transferValidations', plugins_url( 'assets/js/validations/transferValidations.js' , __FILE__ ), array( 'jquery' ) ); 
}

add_action('wp_enqueue_scripts','enqueueTransferValidations');

function enqueueMiscValidations(){
    wp_enqueue_script( 'miscValidations', plugins_url( 'assets/js/validations/misc.js' , __FILE__ ), array( 'jquery' ) ); 
}

add_action('wp_enqueue_scripts','enqueueMiscValidations');

function saveAdminData(){
    $data = $_POST['data'];
    $result = saveCommerceConfig($data);
    if($result === 'fino'){
        insertar_datos_configuracion($data);
        wp_send_json_success( null, 200, 0 );
    } else {
        wp_send_json_error( null, 401, 0 );
    }
    
    wp_die();

}

function saveCommerceConfig(){

    $data = $_POST['data'];

    // Procesar el pago aquí

    // Load configuration
    require_once plugin_dir_path(dirname(__FILE__)) . 'config/config.php';
    
    $api_url = get_api_url('saveCommerceConfig');

    $idBanco = InfoBancaria::obtenerIdEntidadBancaria($data['bank']);

    //Parametros de Entrada del Servicio a Invocar en formato JSON
    $request_data = array(
        'idBank' => $idBanco,
        'bankAccount' => $data['account_number'],
        'consumerKey' => $data['consumer_key'],
        'consumerSecret' => $data['consumer_secret'],
        'consumerKeyCreditCard' => $data['consumer_key_creditcard'],
        'consumerSecretCreditCard' => $data['consumer_secret_creditcard'],
        'rif' => $data['document_type'] . $data['document'],
        'commercePhone' => $data['phone_p2c'],
        'hash' => $data['hash'],
    );

    //Convirtiendo los parametros a formato JSON
    $json_data = json_encode($request_data);

    error_log(json_encode($request_data));

    //Armando Cabecera HTTP
    $headers = array(
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0',
        'KEY: key12345',
        'CONFIRMATION_KEY: ' . $data['confirmation_key']
    );

    error_log(json_encode($headers));

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

    $decoded_response = json_decode($response, true);

    // Verificar si hay errores en la solicitud
    if ($decoded_response['status'] === 500) {
        error_log(strval(curl_errno($ch)));
        wp_send_json_error( 'Error de servidor' );
    } else {
        // Procesar la respuesta
        $decoded_response = json_decode($response, true);

        error_log(json_encode($decoded_response));

        if($decoded_response['status'] == 'SUCCESS') {

            error_log(json_encode($decoded_response));

            insertar_datos_configuracion($data);

            wp_send_json_success( null, 200, 0 );

        }else if($decoded_response['status'] == 'ERROR'){

            error_log(json_encode($decoded_response['data']));

            if($decoded_response['data'] === 'Rif no registrado'){
                wp_send_json_error( 'Rif no registrado' );
            }
            else if($decoded_response['data'] === 'ConfirmationKey no existe'){
                wp_send_json_error( 'ConfirmationKey no existe' );
            }
            else if($decoded_response['data'] === 'Licencia no encontrada'){
                wp_send_json_error( 'Licencia no encontrada' );
            }
        }
    }
    wp_die();
}

add_action('wp_ajax_saveAdminData','saveCommerceConfig');

add_action('wp_footer', 'woocommerce_custom_update_checkout', 50);

function woocommerce_custom_update_checkout()
{
  if (is_checkout()) {
?>
<script type="text/javascript">

  jQuery(document).ready($ => {

    $('input').on('change', () => {

      $('body').off('update_checkout');

    });

  });

</script>
<?php } }
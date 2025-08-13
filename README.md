## Resumen de la Arquitectura

El plugin implementa un sistema de métodos de pago para WooCommerce que sigue un patrón arquitectónico estandarizado <cite/>. Todos los métodos de pago extienden la clase `WC_Payment_Gateway` de WooCommerce y siguen patrones consistentes para configuración, validación y procesamiento de pagos a través de APIs externas <cite/>.

## Estructura Común de Clases

```mermaid
graph TB
    subgraph "WordPress/WooCommerce"
        WCG["WC_Payment_Gateway"]
    end
    
    subgraph "Implementaciones de Métodos de Pago"
        PM["PagoMovil"]
        PC2P["PagoC2P"] 
        PMAN["PagoManual"]
        PTR["TransferenciaInmediata"]
        PTAR["PagoTarjeta"]
    end
    
    subgraph "Componentes Compartidos"
        IB["InfoBancaria"]
        ADMIN_JS["Archivos JavaScript Admin"]
        FORM_FIELDS["Patrones de Campos de Formulario"]
        API_CLIENT["Integración API Externa"]
    end
    
    subgraph "Almacenamiento de Configuración"
        WP_OPTIONS["WordPress Options"]
        CUSTOM_DB["Tabla entidad_bancaria"]
    end
    
    WCG --> PM
    WCG --> PC2P
    WCG --> PMAN
    WCG --> PTR
    WCG --> PTAR
    
    PM --> IB
    PC2P --> IB
    PMAN --> IB
    PTR --> IB
    PTAR --> IB
    
    PM --> ADMIN_JS
    PC2P --> ADMIN_JS
    PMAN --> ADMIN_JS
    PTR --> ADMIN_JS
    PTAR --> ADMIN_JS
    
    PM --> FORM_FIELDS
    PC2P --> FORM_FIELDS
    PMAN --> FORM_FIELDS
    PTR --> FORM_FIELDS
    PTAR --> FORM_FIELDS
    
    PM --> API_CLIENT
    PC2P --> API_CLIENT
    PMAN --> API_CLIENT
    PTR --> API_CLIENT
    PTAR --> API_CLIENT
    
    IB --> WP_OPTIONS
    IB --> CUSTOM_DB
```

## Métodos de Pago Implementados

Actualmente se han implementado tres de los cinco métodos planificados <cite/>:

- **PagoMovil** (`metodosPago/pagoMovil.php`): Pagos móviles P2C con soporte para códigos QR [1](#0-0) 
- **PagoC2P** (`metodosPago/pagoC2P.php`): Pagos Cliente-a-Persona con validación OTP [2](#0-1) 
- **PagoManual** (`metodosPago/pagoManual.php`): Reporte y validación manual de transacciones <cite/>

## Flujo de Integración con API Externa

Todos los métodos de pago siguen un patrón de integración de dos fases consistente <cite/>:

```mermaid
sequenceDiagram
    participant USER as "Checkout Cliente"
    participant PM as "Clase Método de Pago"
    participant LICENSE_API as "API validateCommerceLicence"
    participant PAYMENT_API as "API Procesamiento Pago"
    participant WC as "Orden WooCommerce"
    
    USER->>PM: "Enviar formulario de pago"
    PM->>PM: "validate_fields()"
    
    Note over PM,LICENSE_API: Fase 1: Validación Licencia Comercio
    PM->>LICENSE_API: "POST /api/validateCommerceLicence"
    Note right of LICENSE_API: payload: {"rif": "tipo_documento + documento"}
    LICENSE_API->>PM: "Respuesta validación licencia"
    
    alt Licencia Inválida
        PM->>USER: "Mostrar error de licencia"
    else Licencia Válida
        Note over PM,PAYMENT_API: Fase 2: Procesamiento de Pago
        PM->>PAYMENT_API: "POST /api/{endpointPago}"
        Note right of PAYMENT_API: Datos de pago específicos del método
        PAYMENT_API->>PM: "Respuesta procesamiento pago"
        
        alt Error de Pago
            PM->>USER: "Mostrar error de pago"
        else Pago Exitoso  
            PM->>WC: "update_status('on-hold')"
            PM->>USER: "Redirigir a página de éxito"
        end
    end
```

## Sistema de Configuración Admin

Cada método de pago incluye JavaScript dedicado para la gestión de configuración admin [3](#0-2) :

```mermaid
graph LR
    subgraph "WordPress Admin"
        SETTINGS_PAGE["Página Configuración WooCommerce"]
        SECTION_CHECK{"section == gateway_id?"}
    end
    
    subgraph "Scripts de Configuración"
        C2P_SCRIPT["pagoC2PScript.js"]
        MANUAL_SCRIPT["pagoManualScript.js"] 
        MOVIL_SCRIPT["pagoMovilScript.js"]
    end
    
    subgraph "Datos de Configuración"
        WP_OPTIONS["WordPress Options"]
        CONFIG_OBJECT["Objeto JS config_admin"]
    end
    
    SETTINGS_PAGE --> SECTION_CHECK
    SECTION_CHECK -->|"pago_c2p"| C2P_SCRIPT
    SECTION_CHECK -->|"pago_manual"| MANUAL_SCRIPT
    SECTION_CHECK -->|"pago_movil"| MOVIL_SCRIPT
    
    WP_OPTIONS --> CONFIG_OBJECT
    CONFIG_OBJECT --> C2P_SCRIPT
    CONFIG_OBJECT --> MANUAL_SCRIPT
    CONFIG_OBJECT --> MOVIL_SCRIPT
```

## Endpoints de API y Configuración

Cada método utiliza diferentes endpoints pero sigue los mismos patrones de autenticación [4](#0-3) :

- **PagoC2P**: `172.30.145.250:4000/api/purchaseC2P`
- **PagoManual**: `172.30.145.250:4000/api/validateManualPayment` [5](#0-4) 
- **PagoMovil**: `172.30.145.250:4000/api/validatePaymentP2c` [6](#0-5) 

**Notes**

La arquitectura está diseñada para ser extensible, con dos métodos adicionales planificados: `PagoTarjeta` y `TransferenciaInmediata` <cite/>. El archivo `transferenciaInmediata.php` ya existe pero parece estar en desarrollo [7](#0-6) . Todos los métodos comparten la misma estructura de validación de campos y manejo de errores, lo que facilita el mantenimiento y la consistencia del código.

Wiki pages you might want to explore:
- [Payment Gateway Methods (TCSCA/plugin_boton_pago)](/wiki/TCSCA/plugin_boton_pago#3)

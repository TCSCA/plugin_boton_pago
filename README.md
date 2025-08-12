


# Documentación Completa - Plugin Payment Gateway

## Información General del Plugin

### Descripción
Plugin de métodos de pago para WooCommerce desarrollado por Technology Consulting Solutions (T.C.S). El plugin proporciona múltiples métodos de pago integrados específicamente para el mercado venezolano. [1](#0-0) 

### Especificaciones Técnicas
- **Versión:** 1.7.9
- **Requisitos mínimos WordPress:** 6.0
- **Requisitos mínimos PHP:** 7.0
- **Dominio de texto:** payment-gateway-woo
- **Autor:** Technology Consulting Solutions (T.C.S) [2](#0-1) 

## Estructura del Plugin

### Archivos Principales
El plugin está organizado con el archivo principal `botondepago.php` que incluye todos los métodos de pago desde el directorio `metodosPago/`: [3](#0-2) 

### Métodos de Pago Implementados
El plugin incluye cinco métodos de pago diferentes:

1. **Pago Móvil (P2C)** - Pagos persona a comercio con soporte QR
2. **Pago C2P** - Pagos cliente a persona con validación OTP  
3. **Pago Manual** - Reporte y validación manual de transacciones
4. **Transferencia Inmediata** - Transferencias bancarias en tiempo real
5. **Pago con Tarjeta** - Procesamiento de tarjetas de crédito y débito [4](#0-3) 

## Instalación y Configuración

### Activación del Plugin
Durante la activación, el plugin ejecuta automáticamente:

1. **Creación de tablas de base de datos**
2. **Inserción de registros iniciales de entidades bancarias** [5](#0-4) 

### Tabla de Entidades Bancarias
El plugin crea una tabla `entidad_bancaria` con la siguiente estructura: [6](#0-5) 

### Datos Bancarios Precargados
Se incluyen automáticamente 25 entidades bancarias venezolanas con sus códigos BIN correspondientes: [7](#0-6) 

## Configuración del Administrador

### Panel de Administración
El plugin añade un menú de configuración en el administrador de WordPress: [8](#0-7) 

### Campos de Configuración
Los campos de configuración incluyen:

- **Nombre del Comercio**
- **Tipo de Documento (V/E/J)**
- **RIF** 
- **Código de Activación**
- **Banco**
- **Número de Cuenta**
- **Teléfono P2C**
- **Credenciales API Bancaribe**
- **Credenciales API Tarjetas de Crédito**
- **Hash de Seguridad** [9](#0-8) 

## Arquitectura de los Métodos de Pago

### Patrón Común de Implementación
Todos los métodos de pago extienden `WC_Payment_Gateway` y siguen la misma estructura:

#### Pago Móvil (P2C) [10](#0-9) 

#### Pago C2P [11](#0-10) 

### Métodos Estándar Implementados
Cada método de pago implementa:

- `__construct()` - Inicialización de configuración
- `needs_setup()` - Verificación de configuración completa
- `init_form_fields()` - Definición de campos de formulario  
- `payment_fields()` - Renderizado de campos de checkout
- `validate_fields()` - Validación de campos
- `process_payment()` - Procesamiento del pago
- `receipt_page()` - Página de confirmación

## Integración con APIs Externas

### Configuración de Entornos
El sistema soporta múltiples entornos con diferentes URLs de API: [12](#0-11) 

### Autenticación de APIs
Todas las APIs utilizan headers estándar de autenticación: [13](#0-12) 

### Flujo de Validación de Pagos
El procesamiento sigue un patrón de dos fases:

1. **Validación de Licencia de Comercio**
2. **Procesamiento del Pago Específico**

### Generación de Códigos QR
Para pagos móviles, el sistema genera códigos QR dinámicamente: [14](#0-13) 

## Clase InfoBancaria

### Funcionalidades Principales
La clase `InfoBancaria` proporciona métodos para:

- **Obtener listado de entidades bancarias**
- **Obtener nombres de bancos por ID**
- **Obtener códigos BIN**
- **Filtrar bancos integrados**
- **Generar códigos QR** [15](#0-14) 

## Gestión de Configuración

### Almacenamiento de Datos
La configuración se almacena en WordPress Options con el prefijo `payment_gateway_`: [16](#0-15) 

### Scripts de Administración
Cada método de pago incluye JavaScript específico para la configuración: [17](#0-16) 

## Estilos y Scripts Frontend

### CSS General [18](#0-17) 

### Scripts de Máscara [19](#0-18) 

## Hooks y Filtros de WordPress

### Activación/Desactivación [20](#0-19) 

### Verificación de WooCommerce [21](#0-20) 

### Registro de Métodos de Pago [22](#0-21) 

## Notas de Implementación

### Manejo de Errores
El sistema incluye logging extensivo para debugging y manejo de errores de las APIs externas.

### Compatibilidad
El plugin está diseñado específicamente para WooCommerce y requiere que esté activo para funcionar correctamente.

### Seguridad
Implementa validación en múltiples capas incluyendo licencias de comercio, códigos de confirmación y hash de seguridad.

### Escalabilidad
La arquitectura modular permite agregar nuevos métodos de pago siguiendo los patrones establecidos.

Este documento proporciona una visión completa del plugin Payment Gateway, cubriendo desde la instalación básica hasta los detalles técnicos de implementación para desarrolladores que necesiten mantener o extender el sistema.

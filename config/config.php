<?php
/**
 * Configuration settings for the plugin
 * 
 * This file contains environment-specific configurations
 */

// Define environment types
define('ENV_DEVELOPMENT', 'development');
define('ENV_STAGING', 'staging');
define('ENV_PRODUCTION', 'production');

// Set the current environment (change this based on your environment)
$current_environment = ENV_DEVELOPMENT; // Change this to ENV_STAGING or ENV_PRODUCTION as needed

// API Base URLs configuration
$api_base_urls = [
    ENV_DEVELOPMENT => 'http://localhost:4000',
    ENV_STAGING => 'http://142.00.045.050:4000',
    ENV_PRODUCTION => 'http://132.16.00.0117:8080'
];

/**
 * Builds a complete API URL by combining the base URL with the API path and endpoint
 * 
 * @param string $endpoint The API endpoint (e.g., 'validateCommerceLicence')
 * @param string $base_path The base path (defaults to 'api')
 * @return string The complete URL
 */
function get_api_url($endpoint, $base_path = 'api') {
    global $api_base_urls, $current_environment;
    
    // Get the base URL for the current environment
    $base_url = $api_base_urls[$current_environment] ?? $api_base_urls[ENV_DEVELOPMENT];
    
    // Remove any trailing slashes from the base URL
    $base_url = rtrim($base_url, '/');
    
    // Build the complete URL
    return sprintf('%s/%s/%s', 
        $base_url,
        trim($base_path, '/'),
        ltrim($endpoint, '/')
    );
}

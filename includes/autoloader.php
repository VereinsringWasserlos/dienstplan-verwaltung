<?php
/**
 * Autoloader für Plugin-Klassen
 */

if (!defined('ABSPATH')) {
    exit;
}

spl_autoload_register(function ($class) {
    // Nur Klassen dieses Plugins laden
    $prefix = 'Dienstplan_';
    
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    
    // Klassenname zu Dateiname konvertieren
    $class_file = str_replace($prefix, '', $class);
    $class_file = strtolower(str_replace('_', '-', $class_file));
    
    // Mögliche Pfade
    $paths = array(
        DIENSTPLAN_PLUGIN_PATH . 'includes/class-' . $class_file . '.php',
        DIENSTPLAN_PLUGIN_PATH . 'admin/class-' . $class_file . '.php',
        DIENSTPLAN_PLUGIN_PATH . 'public/class-' . $class_file . '.php',
    );
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

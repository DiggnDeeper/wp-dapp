<?php
// The autoloader class is in a namespace, so we need to use it properly
require_once dirname(__FILE__) . '/Puc/v5p5/Autoloader.php';
new YahnisElsts\PluginUpdateChecker\v5p5\Autoloader();

// Include the factory classes
if (file_exists(dirname(__FILE__) . '/Puc/v5p5/PucFactory.php')) {
    require_once dirname(__FILE__) . '/Puc/v5p5/PucFactory.php';
} else {
    // Define a placeholder factory class if the real one isn't found
    if (!class_exists('Puc_v5p5_Factory', false)) {
        class Puc_v5p5_Factory {
            public static function addVersion($generalClass, $versionedClass, $version) {
                // Placeholder function to prevent fatal errors
            }
        }
    }
}

// V5 factory might be in a different location
if (file_exists(dirname(__FILE__) . '/Puc/v5/PucFactory.php')) {
    require_once dirname(__FILE__) . '/Puc/v5/PucFactory.php';
} else {
    // Define a placeholder factory class if the real one isn't found
    if (!class_exists('Puc_v5_Factory', false)) {
        class Puc_v5_Factory {
            public static function addVersion($generalClass, $versionedClass, $version) {
                // Placeholder function to prevent fatal errors
            }
        }
    }
}

// Check if the proper factory class exists, either in namespace or global
if (class_exists('YahnisElsts\\PluginUpdateChecker\\v5p5\\Factory') || 
    class_exists('Puc_v5p5_Factory')) {
    
    // Register classes defined in this version with the factory
    foreach (
        array(
            'Plugin_UpdateChecker' => 'Puc_v5p5_Plugin_UpdateChecker',
            'Theme_UpdateChecker'  => 'Puc_v5p5_Theme_UpdateChecker',
    
            'Vcs_PluginUpdateChecker' => 'Puc_v5p5_Vcs_PluginUpdateChecker',
            'Vcs_ThemeUpdateChecker'  => 'Puc_v5p5_Vcs_ThemeUpdateChecker',
    
            'GitHubApi'    => 'Puc_v5p5_Vcs_GitHubApi',
            'BitBucketApi' => 'Puc_v5p5_Vcs_BitBucketApi',
            'GitLabApi'    => 'Puc_v5p5_Vcs_GitLabApi',
        )
        as $pucGeneralClass => $pucVersionedClass
    ) {
        if (class_exists('Puc_v5p5_Factory')) {
            Puc_v5p5_Factory::addVersion($pucGeneralClass, $pucVersionedClass, '5.5');
        }
        
        if (class_exists('Puc_v5_Factory')) {
            // Also add it to the minor-version factory in case the major-version factory
            // was already defined by another, older version of the update checker.
            Puc_v5_Factory::addVersion($pucGeneralClass, $pucVersionedClass, '5.5');
        }
    }
} 
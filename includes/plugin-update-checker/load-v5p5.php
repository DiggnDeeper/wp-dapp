<?php
// The autoloader class is in a namespace, so we need to use it properly
require_once dirname(__FILE__) . '/Puc/v5p5/Autoloader.php';
new YahnisElsts\PluginUpdateChecker\v5p5\Autoloader();

// Explicitly include VCS components to ensure GitHub support works
$puc_dir = dirname(__FILE__) . '/Puc/v5p5/';

// Load core files
require_once $puc_dir . 'InstalledPackage.php';
require_once $puc_dir . 'UpdateChecker.php';
require_once $puc_dir . 'Update.php';
require_once $puc_dir . 'PucFactory.php';

// Load VCS base files
require_once $puc_dir . 'Vcs/BaseChecker.php';
require_once $puc_dir . 'Vcs/Api.php';
require_once $puc_dir . 'Vcs/PluginUpdateChecker.php';
require_once $puc_dir . 'Vcs/ThemeUpdateChecker.php';

// Load specific GitHub support
require_once $puc_dir . 'Vcs/GitHubApi.php';
require_once $puc_dir . 'Vcs/Reference.php';
require_once $puc_dir . 'Vcs/ReleaseAssetSupport.php';

// Load plugin-specific base files
require_once $puc_dir . 'Plugin/UpdateChecker.php';
require_once $puc_dir . 'Plugin/Update.php';
require_once $puc_dir . 'Plugin/Package.php';

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

// Register classes defined in this version with the factory
if (class_exists('YahnisElsts\\PluginUpdateChecker\\v5p5\\PucFactory')) {
    $factory = 'YahnisElsts\\PluginUpdateChecker\\v5p5\\PucFactory';
} elseif (class_exists('Puc_v5p5_Factory')) {
    $factory = 'Puc_v5p5_Factory';
} else {
    // No factory found, define a placeholder
    class Puc_v5p5_Factory {
        public static function addVersion($generalClass, $versionedClass, $version) {
            // Placeholder function to prevent fatal errors
        }
    }
    $factory = 'Puc_v5p5_Factory';
}

// Register classes with the factory
$mappings = array(
    'Plugin_UpdateChecker' => 'Puc_v5p5_Plugin_UpdateChecker',
    'Theme_UpdateChecker'  => 'Puc_v5p5_Theme_UpdateChecker',
    'Vcs_PluginUpdateChecker' => 'Puc_v5p5_Vcs_PluginUpdateChecker',
    'Vcs_ThemeUpdateChecker'  => 'Puc_v5p5_Vcs_ThemeUpdateChecker',
    'GitHubApi'    => 'Puc_v5p5_Vcs_GitHubApi',
    'BitBucketApi' => 'Puc_v5p5_Vcs_BitBucketApi',
    'GitLabApi'    => 'Puc_v5p5_Vcs_GitLabApi',
);

// Register the classes with the factory
foreach ($mappings as $generalClass => $versionedClass) {
    if (class_exists($factory)) {
        call_user_func(array($factory, 'addVersion'), $generalClass, $versionedClass, '5.5');
    }
    
    if (class_exists('Puc_v5_Factory')) {
        // Also add it to the minor-version factory
        Puc_v5_Factory::addVersion($generalClass, $versionedClass, '5.5');
    }
} 
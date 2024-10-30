<?php
return array(
    'plugin' => array(
        'id' => 'kbug',
        'name' => 'Kitchenbug',
        'title' => 'Kitchenbug Plugin',
        'url' => 'www.kitchenbug.com',
        'track_path' => 'track.php',
        'filename' => 'Kitchenbug',
        'version' => '0.6.4',
        'phpversion' => '5',
        'upload_recipes' => '/uploads/recipes',
        'editor-prefix' => "[kitchenbug-your-recipe-appears-here-%d]",
		'editor-placeholder' => "<div class=\"kbPlaceholder\" style=\"border: 1px solid black; background: url('%s/wp-content/plugins/kitchenbug/application/assets/img/clickanywhere.png') no-repeat; height: 100px; width: 400px;\"></div>",
        'page_title' => 'Kitchenbug Plugin Settings',
        'menu_title' => 'Kitchenbug',
        'capability' => 'administrator',
        'database' => 'KBSettings'
    ),
    'DOM' => array(),
    // Default settings
    'settings' => array()
);
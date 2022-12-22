    <?php

    require plugin_dir_path( __FILE__ ) . 'update-checker/plugin-update-checker.php';
    use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
    
    $myUpdateChecker = PucFactory::buildUpdateChecker(
        '/stage.diegoescobar.ca/wp-content/plugins/wp-attach2posts/updates/info.json',
        get_template_directory(), //Full path to the main plugin file or functions.php.
        '_mag'
    );

    ?>
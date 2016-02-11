<?php
/*
Plugin Name: MKS Entity-Centric API Management
Description: Utilities for accessing, testing and configuring an Entity-Centric API from a WordPress installation
Plugin URI: http://github.com/mk-smart/ecapi-wordpress
Version: 0.1.1
Author: Alessandro Adamou
Author URI: http://kmi.open.ac.uk/people/member/alessandro-adamou
*/

class DataApis
{
    /**
     * A Unique Identifier
     */
    protected $plugin_slug;
    
    /**
     * A reference to an instance of this class.
     */
    private static $instance;
    
    /**
     * The array of templates that this plugin tracks.
     */
    protected $templates;
    
    /**
     * Returns an instance of this class.
     */
    public static function get_instance() {
        if (null == self::$instance)
            self::$instance = new DataApis();
        return self::$instance;
    }
    
    /**
     * Initializes the plugin by setting filters and administration functions.
     */
    private function __construct() {
    	require __DIR__ . '/vendor/autoload.php';
        $this->templates = array();
        
        add_filter('body_class', array(
            $this,
            'register_body_classes'
        ));
        
        // Add a filter to the attributes metabox to inject template into the cache.
        add_filter('page_attributes_dropdown_pages_args', array(
            $this,
            'register_project_templates'
        ));
        // Add a filter to the save post to inject out template into the page cache
        add_filter('wp_insert_post_data', array(
            $this,
            'register_project_templates'
        ));
        // Add a filter to the template include to determine if the page has our
        // template assigned and return it's path
        add_filter('template_include', array(
            $this,
            'view_project_template'
        ));
        // Add your templates to this array.
        $this->templates = array(
            'inc/ecapi-root.phtml' => 'Data API root'
        );
    }
    
    public function register_body_classes($classes) {
        $classes[] = 'custom-background';
        $classes[] = 'content-onecolumn';
        return $classes;
    }
    
    
    /**
     * Adds our template to the pages cache in order to trick WordPress
     * into thinking the template file exists where it doens't really exist.
     *
     */
    public function register_project_templates($atts) {
        // Create the key used for the themes cache
        $cache_key = 'page_templates-' . md5(get_theme_root() . '/' . get_stylesheet());
        
        // Retrieve the cache list.
        // If it doesn't exist, or it's empty prepare an array
        $templates = wp_get_theme()->get_page_templates();
        if (empty($templates))
            $templates = array();
        
        // New cache, therefore remove the old one
        wp_cache_delete($cache_key, 'themes');
        
        // Now add our template to the list of templates by merging our templates
        // with the existing templates array from the cache.
        $templates = array_merge($templates, $this->templates);
        
        // Add the modified cache to allow WordPress to pick it up for listing
        // available templates
        wp_cache_add($cache_key, $templates, 'themes', 1800);
        
        return $atts;
    }
    
    /**
     * Checks if the template is assigned to the page
     */
    public function view_project_template($template) {
        global $post;
        
        if( !isset($post->ID) 
			|| !isset($this->templates[get_post_meta($post->ID, '_wp_page_template', TRUE)]) )
            return $template;
        
        $file = plugin_dir_path(__FILE__) . get_post_meta($post->ID, '_wp_page_template', TRUE);
        
        // Just to be safe, we check if the file exist first
        if (file_exists($file))
            return $file;
        else
            echo $file;
        
        return $template;
    }
    
}


/*************************
 ******* CALLBACKS *******
 *************************/

function ecapi_admin_add_page_settings() {
	add_menu_page('Entity API', 'Entity API', 'manage_options', 'ecapi-config', 'ecapi_config_page');
    add_options_page('Data API settings', 'Data API', 'manage_options', 'ecapi-settings', 'ecapi_options_page');
}

function ecapi_admin_init() {
    register_setting('ecapi_options', 'ecapi_options', 'ecapi_options_validate');
    add_settings_section('ecapi_main', 'Providers', 'ecapi_section_text', 'ecapi-settings');
    add_settings_field('ecapi_text_url', 'Entity-centric API root URL', 'ecapi_setting_url', 'ecapi-settings', 'ecapi_main');
    add_settings_field('ecapi_text_swagger', 'Swagger manifest URL', 'ecapi_setting_swagger', 'ecapi-settings', 'ecapi_main');
    add_settings_field('ecapi_config_db_url', 'CouchDB URL', 'ecapi_setting_config_db_url', 'ecapi-settings', 'ecapi_main');
    add_settings_field('ecapi_config_db_name', 'CouchDB configuration database name', 'ecapi_setting_config_db_name', 'ecapi-settings', 'ecapi_main');
    add_settings_field('ecapi_config_db_username', 'CouchDB username', 'ecapi_setting_config_db_username', 'ecapi-settings', 'ecapi_main');
    add_settings_field('ecapi_config_db_password', 'CouchDB password', 'ecapi_setting_config_db_password', 'ecapi-settings', 'ecapi_main');
}

function ecapi_admin_intercept() {
	// Intercept POSTs so that no WordPress stuff is printed before redirecting
	if( $_SERVER['REQUEST_METHOD'] === 'POST' 
		&& array_key_exists('page', $_POST) && 'ecapi-config' === $_POST['page'] ) 
		ecapi_config_page();
}

function ecapi_config_page() {
    require_once dirname(__FILE__) . '/inc/ecapiconfigform/couchdb.class.php';
    require_once dirname(__FILE__) . '/lib/catalogue.php';
	include dirname(__FILE__) . '/config.php';
}

function ecapi_options_page() {
    include dirname(__FILE__) . '/options.php';
}

function ecapi_options_validate($input) {
    $options                  = get_option('ecapi_options');
    $options['ecapi_url']     = trim($input['ecapi_url']);
    $options['ecapi_swagger'] = trim($input['ecapi_swagger']);
    $options['ecapi_config_db_url'] = trim($input['ecapi_config_db_url']);
    $options['ecapi_config_db_name'] = trim($input['ecapi_config_db_name']);
    $options['ecapi_config_db_username'] = trim($input['ecapi_config_db_username']);
    $options['ecapi_config_db_password'] = $input['ecapi_config_db_password'];
    $url_rgx                  = '|^(http(s)?:)?//[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i';
    if (!preg_match($url_rgx, $options['ecapi_url']))
        $options['ecapi_url'] = '';
    if (!preg_match($url_rgx, $options['ecapi_swagger']))
        $options['ecapi_swagger'] = '';
    if (!preg_match($url_rgx, $options['ecapi_config_db']))
        $options['ecapi_config_db'] = '';
    return $options;
}

function ecapi_section_text() {
    print '<p>Configure which data providers the Data Hub portal will reach for.</p>';
}

function ecapi_setting_config_db_url() {
    $options = get_option('ecapi_options');
    print "<input id=\"ecapi_config_db_url\" name=\"ecapi_options[ecapi_config_db_url]\" size=\"48\" type=\"text\" value=\"{$options['ecapi_config_db_url']}\"/>";
}

function ecapi_setting_config_db_name() {
    $options = get_option('ecapi_options');
    print "<input id=\"ecapi_config_db_name\" name=\"ecapi_options[ecapi_config_db_name]\" size=\"32\" type=\"text\" value=\"{$options['ecapi_config_db_name']}\"/>";
}

function ecapi_setting_config_db_username() {
    $options = get_option('ecapi_options');
    print "<input id=\"ecapi_config_db_username\" name=\"ecapi_options[ecapi_config_db_username]\" size=\"32\" type=\"text\" value=\"{$options['ecapi_config_db_username']}\"/>";
}

function ecapi_setting_config_db_password() {
    $options = get_option('ecapi_options');
    print "<input id=\"ecapi_config_db_password\" name=\"ecapi_options[ecapi_config_db_password]\" size=\"32\" type=\"password\" value=\"{$options['ecapi_config_db_password']}\"/>";
}

function ecapi_setting_swagger() {
    $options = get_option('ecapi_options');
    print "<input id=\"ecapi_text_swagger\" name=\"ecapi_options[ecapi_swagger]\" size=\"48\" type=\"text\" value=\"{$options['ecapi_swagger']}\"/>";
}

function ecapi_setting_url() {
    $options = get_option('ecapi_options');
    print "<input id=\"ecapi_text_url\" name=\"ecapi_options[ecapi_url]\" size=\"48\" type=\"text\" value=\"{$options['ecapi_url']}\"/>";
}

/**
 * Loads necessary scripts
 */
function add_scripts(){
    $plugdir = dirname(__FILE__) . '/ecapi'; // Stupid Wordpress function plugins_url going bananas
    $scripts_head = array(
    	array( 'ecapi-conf', 'js/config.js', array() ),
    	array( 'prism', 'vendor/bower/prism/prism.js', array() )
    );
    foreach( $scripts_head as $k => $v )
    	wp_register_script( $v[0], plugins_url($v[1], $plugdir), $v[2] );
    foreach( $scripts_head as $k => $v )
    	wp_enqueue_script($v[0]);
    
    wp_register_style('ecapi_conf', plugins_url('css/config.css', $plugdir), array(), FALSE, 'all');
    wp_register_style('prism', plugins_url('vendor/bower/prism/themes/prism.css', $plugdir), array(), '20120208', 'all');
    wp_enqueue_style('ecapi_conf');
    wp_enqueue_style('prism');
}

function wptuts_scripts_with_the_lot() {
    $plugdir = dirname(__FILE__) . '/ecapi'; // Stupid WordPress function plugins_url going bananas
    $bow = 'vendor/bower';

    // Underscore and Backbone need to be in the head for Swagger to work
    wp_deregister_script('underscore');
    wp_deregister_script('backbone');
    $scripts_head = array(
    	array( 'underscore'    , "$bow/swagger-ui/dist/lib/underscore-min.js"    , array() ),
    	array( 'backbone'      , "$bow/swagger-ui/dist/lib/backbone-min.js"      , array() ),
    	array( 'jquery-blockui', "$bow/blockui/jquery.blockUI.js"                , array('jquery') ),
    	array( 'typeahead.js'  , "$bow/typeahead.js/dist/typeahead.bundle.min.js", array('jquery') ),
    );
    $scripts_foot = array(
    	array( 'highlight'     , "$bow/swagger-ui/dist/lib/highlight.7.3.pack.js", array() ),
    	array( 'jquery-slideto', "$bow/swagger-ui/dist/lib/jquery.slideto.min.js", array('jquery') ),
    	array( 'jquery-wiggle' , "$bow/swagger-ui/dist/lib/jquery.wiggle.min.js" , array('jquery') ),
    	array( 'jquery-ba-bbq' , "$bow/swagger-ui/dist/lib/jquery.ba-bbq.min.js" , array('jquery') ),
    	array( 'handlebars'    , "$bow/swagger-ui/dist/lib/handlebars-2.0.0.js"  , array('jquery'), '2.0.0' ),
    	array( 'marked'        , "$bow/swagger-ui/dist/lib/marked.js"            , array('jquery'), FALSE ),
    	array( 'jsoneditor'    , "$bow/swagger-ui/dist/lib/jsoneditor.min.js"    , array('jquery'), '7.3' ),
    	array( 'swagger-client', "$bow/swagger-js/browser/swagger-client.min.js" , array(), '2.1' ),
    	array( 'swagger-ui'    , "$bow/swagger-ui/dist/swagger-ui.min.js"        , array('swagger-client','handlebars','marked','jsoneditor'), '2.1' ),
    );

    foreach( $scripts_head as $k => $v )
    	wp_register_script( $v[0], plugins_url($v[1], $plugdir), $v[2] );
    foreach( $scripts_foot as $k => $v )
    	wp_register_script( $v[0], plugins_url($v[1], $plugdir), $v[2], $v[3], TRUE );
	// Load header scripts for all (for now)
    foreach( $scripts_head as $k => $v ) wp_enqueue_script($v[0]);
}

/**
 * Loads necessary stylesheets
 */
function wptuts_styles_with_the_lot() {
    $plugdir = dirname(__FILE__) . '/ecapi'; // Stupid WordPress function plugins_url going bananas
    // Swagger and custom styles
    // wp_register_style('swaggerui-reset', plugins_url('lib/swagger-ui/css/reset.css', $plugdir), array(), '20120208', 'all');
    wp_register_style('swaggerui-screen', plugins_url('vendor/bower/swagger-ui/dist/css/screen.css', $plugdir), array(), '20120208', 'all');
    wp_register_style('ecapi-custom', plugins_url('css/style.css', $plugdir), array(), '20120208', 'all');
    
    // wp_enqueue_style('swaggerui-reset');
    wp_enqueue_style('swaggerui-screen');
    wp_enqueue_style('ecapi-custom');
}

/*************************
 ********* INIT **********
 *************************/
add_action('wp_enqueue_scripts', 'wptuts_scripts_with_the_lot');
add_action('wp_enqueue_scripts', 'wptuts_styles_with_the_lot');
add_action('plugins_loaded', array(
    'DataApis',
    'get_instance'
));
add_action('admin_menu', 'ecapi_admin_add_page_settings');
add_action('admin_init', 'ecapi_admin_init');
add_action('admin_init', 'ecapi_admin_intercept');
add_action( 'wp_print_scripts', 'add_scripts' );


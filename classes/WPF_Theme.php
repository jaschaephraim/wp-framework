<?php

/**
 * Class representing the theme and its settings.
 * Loads and initializes custom post types.
 */
class WPF_Theme {

    // Per-project vars and functions ----------------------------------------------------------------------------------

    private $css_dir_name = '';
    private $js_dir_name = '';
    private $img_dir_name = '';

    /**
     * Hooked to after_theme_setup.
     */
    public function add_theme_support() {
        // Any calls to add_theme_support()
    }

    /**
     * Hooked to init.
     */
    public function register_nav_menus() {
        // Any calls to register_nav_menu or _menus
    }

    /**
     * Hooked to wp_enqueue_scripts.
     */
    public function enqueue_styles() {
        // Enqueue styles
    }

    /**
     * Hooked to wp_enqueue_scripts.
     */
    public function enqueue_scripts() {
        // Enqueue scripts
    }

    /**
     * Hooked to wp_footer.
     */
    public function pre_footer_inline_scripts() {
        // Echo scripts
    }

    /**
     * Hooked to wp_footer.
     */
    public function post_footer_inline_scripts() {
        // Echo scripts
    }

    /**
     * Called by $this->register_hooks().
     */
    private function add_actions() {
        add_action( 'after_theme_setup', array( $this, 'add_theme_support' ) );
        add_action( 'init', array( $this, 'register_nav_menus' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_footer', array( $this, 'pre_footer_inline_scripts' ), 0 );
        add_action( 'wp_footer', array( $this, 'post_footer_inline_scripts' ), 1000 );
    }

    /**
     * Called by $this->register_hooks().
     */
    private function add_filters() {
        // Y'know, filters
    }

    // Utility functions -----------------------------------------------------------------------------------------------

    /**
     * Returns full path of css dir.
     *
     * @return string
     */
    private function get_css_dir() {
        return $this->build_dir( $this->css_dir_name );
    }

    /**
     * Returns full path of js dir.
     *
     * @return string
     */
    private function get_js_dir() {
        return $this->build_dir( $this->js_dir_name );
    }

    /**
     * Returns full path of img dir.
     *
     * @return string
     */
    private function get_img_dir() {
        return $this->build_dir( $this->img_dir_name );
    }

    /**
     * Adds passed dir name to theme directory uri.
     *
     * @param string $dir_name
     * @return string
     */
    private function build_dir( $dir_name ) {
        $dir = get_template_directory_uri();
        if ( $dir_name )
            $dir .= '/' . $dir_name . '/';
        return $dir;
    }

    /**
     * Inserts array into array after target value.
     *
     * @param array $inserted
     * @param array $target
     * @param int|string $key
     * @return array
     */
    public static function insert_array_after( $inserted, $target, $key ) {
        $key_position = array_search( $key, array_keys( $target ) );
        $target_size = count( $target );
        if ( $key_position == $target_size - 1 )
            return $target + $inserted;
        return array_slice( $target, 0, $key_position + 1 ) + $inserted + array_slice( $target, $key_position + 1 );
    }

    // Core class, don't need to edit ----------------------------------------------------------------------------------

    protected static $singleton;

    /**
     * Called by self::init().
     */
    private function __construct() {
        $this->register_hooks();
        require_once( __DIR__ . '/WPF_Post_Type.php' );

        /**
         * Include and init custom post type classes from classes/post-types/
         */
        $post_type_files = glob( __DIR__ . '/post-types/*.php' );
        foreach ( $post_type_files as $file )
            require_once( $file );
    }

    /**
     * Called by constructor.
     */
    private function register_hooks() {
        $this->add_actions();
        $this->add_filters();
    }

    /**
     * Called after class definition.
     */
    public static function init() {
        if ( !self::$singleton )
            self::$singleton = new self();
    }

}
WPF_Theme::init();

?>
 
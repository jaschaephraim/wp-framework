<?php

/**
 * Abstract class for custom post types.
 */
abstract class WPF_Post_Type {

    // Instance attributes ---------------------------------------------------------------------------------------------

    /**
     * The WP_Post object that this class "extends"
     *
     * @var WP_Post
     */
    private $post;

    // Static attributes -----------------------------------------------------------------------------------------------

    /**
     * String id of custom post type.
     *
     * @var string
     */
    public static $type_string;

    /**
     * Array of arguments for custom post type registration.
     *
     * @var array
     */
    protected static $registration_args;

    /**
     * Associative array of [capability] => [lowest allowed role]
     *
     * @var array
     */
    protected static $capabilities;

    /**
     * Multidimensional array of custom taxonomy arguments.
     *
     * @var array
     */
    protected static $taxonomy_args;

    /**
     * Default template filename.
     *
     * @var string
     */
    protected static $template_name;

    // Abstract methods ------------------------------------------------------------------------------------------------

    /**
     * Additional actions to perform when this post type is saved.
     */
    abstract protected function save();

    /**
     * Sets static properties relating to post type registration, registers hooks.
     */
    abstract public static function init();

    /**
     * Adds columns for post type admin list views.
     * Hooked to 'manage_[typestring]_posts_columns'.
     *
     * @param array $cols
     * @return array
     */
    abstract public static function add_columns( $cols );

    /**
     * Outputs html for given post in given column of the post type admin list view.
     * Hooked to 'manage_[typestring]_posts_custom_column'.
     *
     * @param string $col
     * @param int $post_id
     */
    abstract public static function populate_columns( $col, $post_id );


    /**
     * Register meta boxes for post type admin.
     */
    abstract public static function meta_boxes();

    /**
     * Registers actions and filters for this post type.
     */
    abstract protected static function register_hooks();

    // Instance methods ------------------------------------------------------------------------------------------------

    /**
     * Stores private WP_Post given its id or the post itself.
     *
     * @param int|WP_Post $post
     */
    protected function __construct( $post ) {
        $this->post = is_numeric( $post ) ? get_post( $post ) : $post;
    }

    /**
     * Looks to private WP_Post for any undefined variables.
     *
     * @param mixed $var
     * @return mixed
     */
    public function __get( $var ) {
        return $this->post->$var;
    }

    /**
     * Sets property $var of private WP_Post to $val
     *
     * @param mixed $var
     * @param mixed $val
     * @return mixed
     */
    public function __set( $var, $val ) {
        return $this->post->$var = $val;
    }

    /**
     * Calls any undefined functions on private WP_Post.
     *
     * @param string $f
     * @param array $args
     * @return mixed
     */
    public function __call( $f, $args ) {
        return $this->post->$f( $args );
    }

    /**
     * Returns private WP_Post.
     *
     * @return WP_Post
     */
    protected function get_post() {
        return $this->post;
    }

    // Static methods --------------------------------------------------------------------------------------------------

    /**
     * Registers custom taxonomies and post type defined by a child class.
     * Hooked to 'init'.
     */
    public static function register() {
        $called_class = get_called_class();

        register_taxonomy(
            $called_class::get_cat_tax(),
            $called_class::$type_string,
            array(
                'label' => $called_class::$taxonomy_args[ 'cat' ][ 'label' ],
                'hierarchical' => true,
                'rewrite' => array( 'slug' => $called_class::$taxonomy_args[ 'cat' ][ 'slug' ] )
            )
        );

        register_taxonomy(
            $called_class::get_tag_tax(),
            $called_class::$type_string,
            array(
                'label' => $called_class::$taxonomy_args[ 'tag' ][ 'label' ],
                'rewrite' => array( 'slug' => $called_class::$taxonomy_args[ 'tag' ][ 'slug' ] )
            )
        );

        register_post_type( $called_class::$type_string, $called_class::$registration_args );
    }

    /**
     * Get category taxonomy name of called class.
     *
     * @return string
     */
    public static function get_cat_tax() {
        $called_class = get_called_class();
        return $called_class::$type_string . '_cat';
    }

    /**
     * Get tag taxonomy name of called class.
     *
     * @return string
     */
    public static function get_cat_id_query_var() {
        $called_class = get_called_class();
        return $called_class::get_cat_tax() . '_id';
    }

    /**
     * Get tag taxonomy name of called class.
     *
     * @return string
     */
    public static function get_tag_tax() {
        $called_class = get_called_class();
        return $called_class::$type_string . '_tag';
    }

    /**
     * Either add or remove capabilities for custom post type.
     *
     * @param bool $remove
     */
    public static function edit_capabilities( $remove ) {
        $called_class = get_called_class();
        $capabilities = $called_class::$capabilities;
        foreach ( $capabilities as $capability => $role )
            self::cascading_cap( $capability, $role, $remove );
    }

    /**
     * Performs meta-capability assignment.
     * Hooked to 'map_meta_cap'.
     *
     * @param array $caps
     * @param string $cap
     * @param int $user_id
     * @param array $args
     * @return array
     */
    public static function map_meta_cap( $caps, $cap, $user_id, $args ) {
        $called_class = get_called_class();
        if ( substr_compare( $cap, $called_class::$type_string, -strlen( $called_class::$type_string ) ) != 0 )
            return $caps;

        $action_array = array( 'read', 'edit', 'delete' );
        $cap_array = explode( '_', $cap, 2 );
        if ( !in_array( $cap_array[ 0 ], $action_array ) )
            return $caps;

        if ( empty( $args ) )
            return $caps;

        $post = get_post( $args[ 0 ] );
        if ( $post->post_type == 'revision' )
            $post = get_post( $post->post_parent );

        $author_data = get_userdata( $user_id );
        if ( $post->post_author )
            $post_author_data = get_userdata( $post->post_author );
        else
            $post_author_data = $author_data;

        $is_own = is_object( $post_author_data ) && $user_id == $post_author_data->ID;

        $exception_result = false;
        if ( method_exists( $called_class, 'meta_cap_exceptions' ) )
            $exception_result = $called_class::meta_cap_exceptions( $cap, $post, $is_own );

        if ( $exception_result )
            return $exception_result;

        if ( $is_own )
            return array( $cap . 's' );
        return array( $cap_array[ 0 ] . '_others_' . $cap_array[ 1 ] . 's' );
    }

    /**
     * Given WP_Post or an id, returns instance of child class on success
     * or false if the given id or post is not of the correct type.
     *
     * @param int|string|WP_Post $post
     * @return bool|WPP_Post
     */
    public static function get( $post ) {
        $called_class = get_called_class();
        if ( is_numeric( $post ) )
            $post = get_post( $post );
        if ( is_string( $post ) ) {
            $posts = get_posts( array(
                'post_type' => $called_class::$type_string,
                'name' => $post
            ) );
            if ( count( $posts ) < 1 )
                return false;
            $post = $posts[ 0 ];
        }
        if ( empty( $post ) )
            return false;
        if ( $post->post_type != $called_class::$type_string )
            return false;
        return new $called_class( $post->ID );
    }

    /**
     * Given a WP_Post array, returns array of instances of child class that was called.
     *
     * @param array $posts
     * @return array
     */
    public static function get_multiple( $posts ) {
        $called_class = get_called_class();
        $array = array();
        foreach ( $posts as $post )
            $array[] = $called_class::get( $post );
        return $array;
    }

    /**
     * Returns either array of all of given post type, or a normal WP_Query object populated with the same.
     *
     * @param array $return_type
     * @return array|WP_Query
     */
    public static function get_all( $return_type = 'ARRAY' ) {
        $called_class = get_called_class();
        $args = array(
            'numberposts' => -1,
            'post_type' => $called_class::$type_string,
            'post_status' => 'any'
        );
        if ( $return_type == 'WP_QUERY' )
            return new WP_Query( $args );

        $posts = get_posts( $args );
        return $called_class::get_multiple( $posts );
    }

    /**
     * Calls save() on a child class instance with the given id.
     * Hooked to 'save_post'.
     *
     * @param int $id
     */
    public static function save_post( $id ) {
        $called_class = get_called_class();
        if ( isset( $_POST[ 'post_type' ] ) && $_POST[ 'post_type' ] == $called_class::$type_string ) {
            $post = $called_class::get( $id );
            if ( $post && !wp_is_post_revision( $post->get_post() ) && !wp_is_post_autosave( $post->get_post() ) )
                $post->save();
        }
    }

    /**
     * Add query vars to query by custom category id.
     *
     * @param array $vars
     * @return array
     */
    public static function add_common_query_vars( $vars ) {
        $called_class = get_called_class();
        $vars[] = $called_class::get_cat_id_query_var();
        return $vars;
    }

    /**
     * Set custom category query var if category id is set.
     *
     * @param WP_Query $query
     * @return WP_Query
     */
    public static function common_parse_query( $query ) {
        $called_class = get_called_class();
        if ( $query->get( $called_class::get_cat_id_query_var() ) ) {
            $cat = get_term_by(
                'id',
                $query->get( $called_class::get_cat_id_query_var() ),
                $called_class::get_cat_tax()
            );
            $query->set( $called_class::get_cat_tax(), $cat->slug );
            $query->is_tax = 1;
        }
        return $query;
    }

    /**
     * Remove category id query var from url
     *
     * @param string $location
     * @return string
     */
    public static function common_redirect( $location ) {
        $called_class = get_called_class();
        $location = remove_query_arg( $called_class::get_cat_id_query_var(), $location );
        return $location;
    }

    /**
     * Locate and prepend custom template names when appropriate.
     *
     * @param array|string $templates
     * @return array|string
     */
    public static function prepend_template( $templates ) {
        if ( get_query_var( 'post_type' ) != WPP_Article::$type_string ) return $templates;

        $called_class = get_called_class();

        if ( is_array( $templates ) )
            array_unshift( $templates, $called_class::$template_name );
        else
            $templates = array( $called_class::$template_name, $templates );
        return locate_template( $templates );
    }

    /**
     * Hooks Wordpress action to a static function of the child class that was called.
     *
     * @param string $hook
     * @param string $func
     * @param int $priority
     * @param int $accepted_args
     */
    protected static function add_action( $hook, $func, $priority = 10, $accepted_args = 1 ) {
        add_action( $hook, array( get_called_class(), $func ), $priority, $accepted_args );
    }

    /**
     * Hooks Wordpress filter to a static function of the child class that was called.
     *
     * @param string $filter
     * @param string $func
     * @param int $priority
     * @param int $accepted_args
     */
    protected static function add_filter( $filter, $func, $priority = 10, $accepted_args = 1 ) {
        add_filter( $filter, array( get_called_class(), $func ), $priority, $accepted_args );
    }

    /**
     * Returns true if global $post_type represents child class that was called, false otherwise.
     *
     * @return bool
     */
    protected static function is_this( $post = null ) {
        $called_class = get_called_class();
        if ( !is_null( $post ) )
            return $post->post_type == $called_class::$type_string;
        global $post_type;
        return $post_type == $called_class::$type_string;
    }

    /**
     * Register common hooks.
     */
    protected static function register_common_hooks() {
        $called_class = get_called_class();

        // Function defined in child classes
        $called_class::add_action( 'after_setup_theme', 'rewrites' );

        // Functions defined in this abstract class
        $called_class::add_action( 'init', 'register' );
        $called_class::add_action( 'map_meta_cap', 'map_meta_cap', 10, 4 );
        $called_class::add_action( 'save_post', 'save_post' );

        $called_class::add_filter( 'query_vars', 'add_common_query_vars' );
        $called_class::add_filter( 'parse_query', 'common_parse_query' );
        $called_class::add_filter( 'wp_redirect', 'common_redirect' );
    }

    /**
     * Adds capability to given role and all superior roles.
     *
     * @param string $role
     * @param string $cap
     * @param bool $remove
     */
    private static function cascading_cap( $cap, $role, $remove = false ) {
        global $wp_roles;

        $roles = array( 'subscriber', 'contributor', 'author', 'editor', 'administrator' );
        $num_roles = count( $roles );

        for ( $i = array_search( $role, $roles ); $i < $num_roles; $i++ ) {
            if ( $remove )
                $wp_roles->remove_cap( $roles[ $i ], $cap );
            else
                $wp_roles->add_cap( $roles[ $i ], $cap );
        }
    }
}

?>
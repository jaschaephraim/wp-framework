<?php

/**
 * Example subclass of WPF_Post_Type abstract class.
 * Not functional on its own, should be used as a template.
 */
class WPF_Example extends WPF_Post_Type {

    // -----------------------------------------------------------------------------------------------------------------
    // Custom attributes and methods -----------------------------------------------------------------------------------
    // -----------------------------------------------------------------------------------------------------------------

    // Instance attributes ---------------------------------------------------------------------------------------------

    // Add any instance attributes for this post type. Don't forget getters and setters!

    // Instance methods ------------------------------------------------------------------------------------------------

    /**
     * Custom save behavior.
     * Called by parent::save_post().
     * Required.
     */
    protected function save() {
        // Custom save behavior, probably calls to update_post_meta() and wp_update_post()
    }

    // Static methods --------------------------------------------------------------------------------------------------

    /**
     * Sets static properties relating to post type registration, registers hooks.
     * See http://codex.wordpress.org/Function_Reference/register_post_type
     * Required.
     */
    public static function init() {
        self::$type_string = '';
        self::$registration_args = array(
            'labels' => array(
                'name' => 'Posts',
                'singular_name' => 'Post',
                'add_new_item' => 'Add New Post',
                'edit_item' => 'Edit Post',
                'new_item' => 'New Post',
                'all_items' => 'All Posts',
                'view_item' => 'View Post',
                'search_items' => 'Search Posts',
                'not_found' =>  'No posts found',
                'not_found_in_trash' => 'No posts found in Trash'
            ),
            'description' => '',
            'public' => true,
            'capability_type' => array( '', '' ), // $type_string singular, plural
            'supports' => array(
                'title',
                'editor',
                'author',
                'thumbnail',
                'excerpt',
                'trackbacks',
                'custom-fields',
                'comments',
                'revisions',
                'page-attributes',
                'post-formats'
            ),
            'has_archive' => true,
            'register_meta_box_cb' => array( __CLASS__, 'meta_boxes' ),
            'taxonomies' => array( '' ), // Array of taxonomy slugs
            'rewrite' => array( 'slug' => '' ) // Or whatev
        );
        self::$capabilities = array( // Slugs completed with plural capability type (above)
            'delete_' => 'contributor',
            'edit_' => 'contributor',
            'delete_published_' => 'editor',
            'edit_published_' => 'editor',
            'publish_' => 'editor',
            'delete_others_' => 'editor',
            'delete_private_' => 'editor',
            'edit_others_' => 'editor',
            'edit_private_' => 'editor',
            'read_private_' => 'editor'
        );
        self::$taxonomy_args = array(
            'cat' => array(
                'label' => 'Post Categories',
                'slug' => 'post-category'
            ),
            'tag' => array(
                'label' => 'Post Tags',
                'slug' => 'post-tag'
            )
        );
        self::$template_name = ''; // File name for singular template
        self::register_common_hooks();
        self::register_hooks();
    }

    /**
     * Adds columns for post type admin list views.
     * Hooked to 'manage_wpp_article_posts_columns'.
     * Required.
     *
     * @param array $cols
     * @return array
     */
    public static function add_columns( $cols ) {
        unset( $cols[ '' ] ); // Col slug
        $cols = WPF_Theme::insert_array_after( array( '' => '' ), $cols, '' ); // Col slug, label, slug of preceding
        return $cols;
    }

    /**
     * Outputs html for given post in given column of the post type admin list view.
     * Hooked to 'manage_wpp_article_posts_custom_column'.
     * Required.
     *
     * @param string $col
     * @param int $post_id
     */
    public static function populate_columns( $col, $post_id ) {
        $post = self::get( $post_id );

        switch( $col ) {
            case '': // Col slug
                echo '';
                break;
        }
    }

    /**
     * Register meta boxes for post type admin.
     * Required.
     */
    public static function meta_boxes() {
        self::add_action( '', '' ); // Meta box action, callback
    }

    /**
     * Adds rewrite rule for article permalinks
     * Hooked to 'after_setup_theme'.
     */
    public static function rewrites() {
        // Calls to add_rewrite_rule()
    }

    /**
     * Make 'Issue' and 'Status' columns sortable in admin list table.
     * Hooked to 'manage_edit-wpp_article_sortable_columns'.
     *
     * @param array $cols
     * @return array
     */
    public static function sortable_columns( $cols ) {
        return $cols + array( '' => '' ); // Both keys and values are col slug
    }

    /**
     * Add query vars in appropriate context
     *
     * @param $vars
     * @return array
     */
    public static function add_query_vars( $vars ) {
        if ( self::is_this() ) {
            global $pagenow;
            if ( $pagenow == 'edit.php' || $pagenow == 'post-new.php' || $pagenow == 'post.php' ) {
                // Add query vars
            }
        }
        return $vars;
    }

    /**
     * Alters query to respect issue and status filters on admin list table,
     * and to check the status of an article's issue when specified in a permalink.
     * Hooked to 'parse_query'.
     *
     * @param WP_Query $query
     * @return WP_Query mixed
     */
    public static function parse_query( $query ) {
        if ( $query->get( 'post_type' ) != self::$type_string )
            return $query;

        // Custom parse query stuff
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Core attributes and methods, don't edit -------------------------------------------------------------------------
    // -----------------------------------------------------------------------------------------------------------------

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

    // Instance methods ------------------------------------------------------------------------------------------------

    public function __construct( $post ) {
        parent::__construct( $post );
    }

    // Static methods --------------------------------------------------------------------------------------------------

    /**
     * Performs a meta-capability mapping that is an exception to the parent class' default handling.
     * Called by WPP_Post::map_meta_cap().
     *
     * @param string $cap
     * @param WP_Post $post
     * @param bool $is_own
     * @return bool|array
     */
    public static function meta_cap_exceptions( $cap, $post, $is_own ) {
        if ( $cap == 'edit_' . self::$type_string ) {
            if ( $is_own )
                return in_array( $post->post_status, array( 'publish', 'pending' ) ) ?
                    array( 'publish_' . self::$type_string . 's' ) : array( $cap . 's' );
            return array( 'edit_others_' . self::$type_string . 's' );
        }
        return false;
    }

    /**
     * Registers actions and filters for this post type.
     */
    protected static function register_hooks() {
        // Function defined in WPF_Post_Type abstract class
        self::add_filter( 'single_template', 'prepend_template' );

        // Functions defined in this class
        self::add_action( 'manage_' . self::$type_string . '_posts_custom_column', 'populate_columns', 10, 2 );

        self::add_filter( 'query_vars', 'add_query_vars' );
        self::add_filter( 'parse_query', 'parse_query' );
        self::add_filter( 'manage_' . self::$type_string . '_posts_columns', 'add_columns' );
        self::add_filter( 'manage_edit-' . self::$type_string . '_sortable_columns', 'sortable_columns' );
    }

}
WPF_Example::init();

?>
 
<?php
/*
 * Plugin Name: Custom Notice for Products
 * Plugin URI: 
 * Description: Add notice, announcements, alerts, etc. on product and cart page of your store.
 * Author: Hemant Jangir
 * Author URI: https://www.linkedin.com/in/hemant-jangir-b35469190/
 * Text Domain: cnphem 
 * Version: 1.0.0
 * Requires at least: 5.2
 * Requires PHP: 7.4
 * License: GPL2
 *
*/

if (!defined("ABSPATH"))
      exit;

define( 'CNPHEM_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'CNPHEM_PLUGIN_DIR_INCLUDES', CNPHEM_PLUGIN_DIR_PATH . "includes" . DIRECTORY_SEPARATOR );

require_once CNPHEM_PLUGIN_DIR_INCLUDES . "functions.php";

if (!class_exists('Custom_Notice_for_Products')) {
    class Custom_Notice_for_Products
    {
        public function __construct()
        {
            if(!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))){
                add_action( 'admin_notices', array( $this, 'cnphem_notification' ) );
                return;
            }
            add_action( 'admin_enqueue_scripts', array( $this, 'cnphem_admin_assets' ) );
            add_action( 'init', array( $this, 'cnphem_register_post_type' ), 0 );
            add_filter( 'manage_cnphem_posts_columns', array( $this, 'cnphem_filter_posts_columns' ) );
            add_action( 'manage_cnphem_posts_custom_column', array( $this, 'cnphem_add_col_value' ), 10, 2 );
            add_action( 'wp_editor_settings', array( $this, 'cnf_editor_settings' ), 10, 2 );
            add_action( 'add_meta_boxes', array( $this, 'cnphem_meta_box' ) );
            add_action( 'save_post', array( $this, 'cnphem_save_metadata' ), 0 );
            add_action(
                'wp_head',
                function() {
                    if ( is_product() ) {
                        $this->add_product_page_hooks();
                    }
                    elseif ( is_cart() ) {
                        $this->add_cart_page_hooks();
                    }
                }
            );
        }

        /*
         * Check WooCommerce status
         */
        public function cnphem_notification() {
            ?>
            <div id="message" class="error">
                <p><?php esc_html_e( 'Please install and activate WooCommerce to use "Custom Notice for Products".', 'cnphem' ); ?></p>
            </div>
            <?php
        }

        /**
         * Enqueue assets for admin.
         */
        public function cnphem_admin_assets() {
            // Enqueue WooCommerce Select2 scripts and styles
            wp_enqueue_script('select2', WC()->plugin_url() . '/assets/js/select2/select2.full.min.js', array('jquery'), '4.0.13', true);
            wp_enqueue_style('select2', WC()->plugin_url() . '/assets/css/select2.css');
            wp_enqueue_style( 'cnphemAdmincss', plugins_url( '/assets/css/admin.css', __FILE__ ), false, '1.0.0' );
            wp_enqueue_script( 'cnphemjs', plugins_url( '/assets/js/script.js', __FILE__ ), array( 'jquery' ), '1.0.0', false );
        }

        /**
         * Register custom post type..
         */
        public function cnphem_register_post_type() {
            $labels = array(
                'name'               => _x( 'Custom Notice', 'Post Type General Name', 'cnphem' ),
                'singular_name'      => _x( 'Custom Notice', 'Post Type Singular Name', 'cnphem' ),
                'menu_name'          => __( 'Custom Notice', 'cnphem' ),
                'parent_item_colon'  => __( 'Parent Item:', 'cnphem' ),
                'all_items'          => __( 'All Notice', 'cnphem' ),
                'view_item'          => __( 'View Notice', 'cnphem' ),
                'add_new_item'       => __( 'Add New Notice', 'cnphem' ),
                'add_new'            => __( 'Add New Notice', 'cnphem' ),
                'edit_item'          => __( 'Edit Notice', 'cnphem' ),
                'update_item'        => __( 'Update Notice', 'cnphem' ),
                'search_items'       => __( 'Search Notice', 'cnphem' ),
                'not_found'          => __( 'Not found', 'cnphem' ),
                'not_found_in_trash' => __( 'Not found in Trash', 'cnphem' ),
            );
            $args   = array(
                'labels'              => $labels,
                'supports'            => array( 'title', 'editor', 'page-attributes' ),
                'hierarchical'        => false,
                'public'              => true,
                'show_ui'             => true,
                'show_in_nav_menus'   => false,
                'show_in_admin_bar'   => false,
                'menu_position'       => 20,
                'can_export'          => true,
                'has_archive'         => false,
                'exclude_from_search' => true,
                'publicly_queryable'  => false,
                'capability_type'     => 'post',
            );
            register_post_type( 'cnphem', $args );
        }

        /**
         * Add Post columns
         *
         * @param array $columns Columns of WP_List_Table.
         *
         * @return array
         */
        public function cnphem_filter_posts_columns( $columns ) {
            unset($columns['date']);
            $columns['title']    = __( 'Notice Title', 'cnphem' );
            $columns['message']  = __( 'Notice Message', 'cnphem' );
            $columns['date']  = __( 'Date', 'cnphem' );
            return $columns;
        }

        /**
         * Add columns value.
         *
         * @param array $column Columns of WP_List_Table.
         * @param array $post_id ID of current Post.
         * @return void
         */
        public function cnphem_add_col_value( $column, $post_id ) {
            if ( 'message' === $column ) {
                echo esc_html( get_the_content( $post_id ) );
            }
        }

        /**
         * Editor customise.
         *
         * @param array $settings   Settings args array.
         * @param array $id         ID of editor.
         *
         * @return array
         */
        public function cnf_editor_settings( $settings, $id ) {

            $screen = get_current_screen();

            if ( 'cnphem' !== $screen->post_type ) {
                return $settings;
            }

            $settings['media_buttons'] = false;

            $settings['quicktags'] = false;

            return $settings;
        }

        /**
         * Add meta boxes in post type cnphem.
         */
        public function cnphem_meta_box() {

            add_meta_box(
                'custom-cnphem-display',
                __( 'Custom Notice Display on Product Page', 'cnphem' ),
                array( $this, 'cnphem_product_page_metabox_callback' ),
                'cnphem'
            );
            add_meta_box(
                'custom-cnphem-display-for-cart-page',
                __( 'Custom Notice Display on Cart Page', 'cnphem' ),
                array( $this, 'cnphem_cart_page_metabox_callback' ),
                'cnphem'
            );

        }

        /**
         * Add Custom Notices Fields for Product Page.
         */
        public function cnphem_product_page_metabox_callback() {
            global $post;
            wp_nonce_field( basename( __FILE__ ), 'cnphem_product_page_nonce' ); ?>
            <div class="cnphem-form">
                <table class="cnphem-table-option">
                    <tr class="cnphem-field ">
                        <th>
                            <div class="option-head">
                                <h3>
                                    <?php echo esc_html__( 'Products', 'cnphem' ); ?>
                                </h3>
                            </div>
                        </th>

                        <td>
                            <?php
                            $args     = array( 'post_type' => 'product', 'posts_per_page' => -1, 'orderby' => 'name', 'order' => 'ASC', 'post_status' => 'publish' );
                            $products = get_posts( $args ); 
                            $p_cat    = get_post_meta( $post->ID, 'cnphem_products', true );
                            $p_cat    = is_array( $p_cat ) ? $p_cat : array();
                            ?>
                            <select name="cnphem_products[]" id="cnphem_products" data-placeholder="Choose Products..." class="cnphem-select" multiple="">;
                                <option value="all" <?php echo ( in_array( 'all', $p_cat ) ) ? esc_html__( 'selected', 'cnphem') : ''; ?> >
                                    <?php echo esc_html__( 'All', 'cnphem' ); ?>
                                </option>
                                <?php 
                                if( !empty($products) ) :
                                    foreach ($products as $product) :
                                        ?>
                                        <option value="<?php echo esc_html( $product->ID ); ?>" 
                                        <?php echo ( in_array( $product->ID, $p_cat ) ) ? esc_html__( 'selected', 'cnphem' ) : ''; ?>
                                        >
                                        <?php echo esc_html( $product->post_title ); ?>
                                        </option>
                                        <?php
                                    endforeach;
                                endif;
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr class="cnphem-field catagory-in-cart">
                        <th>
                            <div class="option-head">
                                <h3>
                                    <?php echo esc_html__( 'Product Categories', 'cnphem' ); ?>
                                </h3>
                            </div>
                        </th>
                        <td>
                            <?php
                                $cat_args = array(
                                    'taxonomy'   => 'product_cat',
                                    'orderby'    => 'name',
                                    'order'      => 'asc',
                                    'hide_empty' => false,
                                );

                                $product_cat = get_terms( $cat_args );
                                $p_cat       = get_post_meta( $post->ID, 'cnphem_cat', true );
                                $p_cat       = is_array( $p_cat ) ? $p_cat : array();
                                ?>
                            <select name="cnphem_cat[]" id="cnphem_cat" data-placeholder="Choose Categories..." class="cnphem-select" multiple="">;
                                <option value="all" <?php echo ( in_array( 'all', $p_cat ) ) ? esc_html__( 'selected', 'cnphem' ) : ''; ?> >
                                    <?php echo esc_html__( 'All', 'cnphem' ); ?>
                                </option>
                                <?php
                                if( !empty($product_cat) ) :
                                    foreach ( $product_cat as $cat ) :
                                        ?>
                                        <option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php echo ( in_array( $cat->term_id, $p_cat ) ) ? esc_html__( 'selected', 'cnphem' ) : ''; ?> >
                                            <?php echo esc_html( $cat->name ); ?>
                                        </option>
                                        <?php
                                    endforeach;
                                endif;
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr class="cnphem-field ">
                        <th>
                            <div class="option-head">
                                <h3>
                                    <?php echo esc_html__( 'Display Notice on Product Page', 'cnphem' ); ?>
                                </h3>
                            </div>
                        </th>

                        <td>
                            <?php
                                $option = get_post_meta( $post->ID, 'cnphem_display_product_page', true );
                            ?>
                            <select name="cnphem_display_product_page" id="cnphem_display_product_page" data-placeholder="Choose Position...">;
                                <option value="0" <?php echo ( $option == '0' ) ? esc_html__( 'selected', 'cnphem' ) : ''; ?> >
                                    <?php echo esc_html__( 'None', 'cnphem' ); ?>
                                </option>
                                <option value="1" <?php echo ( $option == '1' ) ? esc_html__( 'selected', 'cnphem' ) : ''; ?> >
                                    <?php echo esc_html__( 'Top of the page', 'cnphem' ); ?>
                                </option>
                                <option value="2" <?php echo ( $option == '2' ) ? esc_html__( 'selected', 'cnphem' ) : ''; ?> >
                                    <?php echo esc_html__( 'After product price', 'cnphem' ); ?>
                                </option>
                                <option value="3" <?php echo ( $option == '3' ) ? esc_html__( 'selected', 'cnphem' ) : ''; ?> >
                                    <?php echo esc_html__( 'Before add to cart button', 'cnphem' ); ?>
                                </option>
                                <option value="4" <?php echo ( $option == '4' ) ? esc_html__( 'selected', 'cnphem' ) : ''; ?> >
                                    <?php echo esc_html__( 'After add to cart button', 'cnphem' ); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
            <?php
        }

        /**
         * Add Custom Notices Fields for Cart Page.
         */
        public function cnphem_cart_page_metabox_callback() {
            global $post;
            wp_nonce_field( basename( __FILE__ ), 'cnphem_cart_page_nonce' ); ?>
            <div class="cnphem-form">
                <table class="cnphem-table-option">
                    <tr class="cnphem-field ">
                        <th>
                            <div class="option-head">
                                <h3>
                                    <?php echo esc_html__( 'Display Always', 'cnphem' ); ?>
                                </h3>
                            </div>
                        </th>

                        <td>
                            <?php
                                $cart_al = get_post_meta( $post->ID, 'cnphem_cartAlways', true );
                            ?>
                            <input type="checkbox" name="cnphem_cartAlways" id="cnphem_cartAlways" value="1" <?php checked($cart_al, 1); ?>>
                        </td>
                    </tr>
                    <tr class="cnphem-field  cart_page_products">
                        <th>
                            <div class="option-head">
                                <h3>
                                    <?php echo esc_html__( 'Products', 'cnphem' ); ?>
                                </h3>
                            </div>
                        </th>

                        <td>
                            <?php
                            $args     = array( 'post_type' => 'product', 'posts_per_page' => -1, 'orderby' => 'name', 'order' => 'ASC', 'post_status' => 'publish' );
                            $products = get_posts( $args ); 
                            $p_cat    = get_post_meta( $post->ID, 'cnphem_productsCart', true );
                            $p_cat    = is_array( $p_cat ) ? $p_cat : array();
                            ?>
                            <select name="cnphem_productsCart[]" id="cnphem_productsCart" data-placeholder="Choose Products..." class="cnphem-select" multiple="">;
                                <option value="all" <?php echo ( in_array( 'all', $p_cat ) ) ? esc_html__( 'selected', 'cnphem' ) : ''; ?> >
                                    <?php echo esc_html__( 'All', 'cnphem' ); ?>
                                </option>
                                <?php 
                                if( !empty($products) ) :
                                    foreach ($products as $product) :
                                        ?>
                                        <option value="<?php echo esc_html( $product->ID ); ?>" <?php echo ( in_array( $product->ID, $p_cat ) ) ? esc_html__( 'selected', 'cnphem' ) : ''; ?> >
                                            <?php echo esc_html( $product->post_title ); ?>
                                        </option>
                                        <?php
                                    endforeach;
                                endif;
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr class="cnphem-field catagory-in-cart cart_page_pro_cats">
                        <th>
                            <div class="option-head">
                                <h3>
                                    <?php echo esc_html__( 'Product Categories', 'cnphem' ); ?>
                                </h3>
                            </div>
                        </th>
                        <td>
                            <?php
                                $cat_args = array(
                                    'taxonomy'   => 'product_cat',
                                    'orderby'    => 'name',
                                    'order'      => 'asc',
                                    'hide_empty' => false,
                                );

                                $product_cat = get_terms( $cat_args );
                                $p_cat       = get_post_meta( $post->ID, 'cnphem_catCart', true );
                                $p_cat       = is_array( $p_cat ) ? $p_cat : array();
                            ?>
                            <select name="cnphem_catCart[]" id="cnphem_catCart" data-placeholder="Choose Categories..." class="cnphem-select" multiple="">;
                                <option value="all" <?php echo ( in_array( 'all', $p_cat ) ) ? esc_html__( 'selected', 'cnphem' ) : ''; ?> >
                                    <?php echo esc_html__( 'All', 'cnphem' ); ?>
                                </option>
                                <?php
                                if( !empty($product_cat) ) :
                                    foreach ( $product_cat as $cat ) :
                                        ?>
                                        <option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php echo ( in_array( $cat->term_id, $p_cat ) ) ? esc_html__( 'selected', 'cnphem' ) : ''; ?> >
                                            <?php echo esc_html( $cat->name ); ?>
                                        </option>
                                        <?php
                                    endforeach;
                                endif;
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr class="cnphem-field ">
                        <th>
                            <div class="option-head">
                                <h3>
                                    <?php echo esc_html__( 'Display Notice on Cart Page', 'cnphem' ); ?>
                                </h3>
                            </div>
                        </th>

                        <td>
                            <?php
                                $option = get_post_meta( $post->ID, 'cnphem_display_cart_page', true );
                            ?>
                            <select name="cnphem_display_cart_page" id="cnphem_display_cart_page" data-placeholder="Choose Position...">;
                                <option value="0" <?php echo ( $option == '0' ) ? esc_html__( 'selected', 'cnphem' ) : ''; ?> >
                                    <?php echo esc_html__( 'None', 'cnphem' ); ?>
                                </option>
                                <option value="1"<?php echo ( $option == '1' ) ? esc_html__( 'selected', 'cnphem' ) : ''; ?> >
                                    <?php echo esc_html__( 'Top of the page', 'cnphem' ); ?>
                                </option>
                                <option value="2" <?php echo ( $option == '2' ) ? esc_html__( 'selected', 'cnphem' ) : ''; ?> >
                                    <?php echo esc_html__( 'After cart table', 'cnphem' ); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
            <?php
        }

        /**
         * Save post Meta.
         *
         * @param array $post_id ID of current Post.
         *
         * @return void
         */
        public function cnphem_save_metadata( $post_id ) {
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }

            /* Verify the nonce before proceeding. */
            if ( !isset( $_POST['cnphem_product_page_nonce'] ) || !wp_verify_nonce( sanitize_text_field($_POST['cnphem_product_page_nonce']), basename( __FILE__ ) ) ){
                return $post_id;
            }
            if ( !isset( $_POST['cnphem_cart_page_nonce'] ) || !wp_verify_nonce( sanitize_text_field($_POST['cnphem_cart_page_nonce']), basename( __FILE__ ) ) ){
                return $post_id;
            }
            

            // if current user can't edit this post
            if ( !current_user_can( 'edit_posts' ) ) {
                return;
            }

            // if current user can't edit this post
            if ( !current_user_can( 'edit_posts' ) ) {
                return;
            }

            // Save meta box data.

            if ( isset( $_POST['cnphem_products'] ) ) {
                update_post_meta( $post_id, 'cnphem_products', sanitize_meta( '', wp_unslash( $_POST['cnphem_products'] ), '' ) );
            } else {
                update_post_meta( $post_id, 'cnphem_products', array() );
            }
            if ( isset( $_POST['cnphem_cat'] ) ) {
                update_post_meta( $post_id, 'cnphem_cat', sanitize_meta( '', wp_unslash( $_POST['cnphem_cat'] ), '' ) );
            } else {
                update_post_meta( $post_id, 'cnphem_cat', array() );
            }
            if ( isset( $_POST['cnphem_display_product_page'] ) ) {
                update_post_meta( $post_id, 'cnphem_display_product_page', sanitize_text_field( wp_unslash( $_POST['cnphem_display_product_page'] ) ) );
            }

            if ( isset( $_POST['cnphem_cartAlways'] ) ) {
                update_post_meta( $post_id, 'cnphem_cartAlways', sanitize_text_field( wp_unslash( $_POST['cnphem_cartAlways'] ) ) );
            }
            else{
                update_post_meta( $post_id, 'cnphem_cartAlways', '0' );
            }
            if ( isset( $_POST['cnphem_productsCart'] ) ) {
                update_post_meta( $post_id, 'cnphem_productsCart', sanitize_meta( '', wp_unslash( $_POST['cnphem_productsCart'] ), '' ) );
            } else {
                update_post_meta( $post_id, 'cnphem_productsCart', array() );
            }
            if ( isset( $_POST['cnphem_catCart'] ) ) {
                update_post_meta( $post_id, 'cnphem_catCart', sanitize_meta( '', wp_unslash( $_POST['cnphem_catCart'] ), '' ) );
            } else {
                update_post_meta( $post_id, 'cnphem_catCart', array() );
            }
            if ( isset( $_POST['cnphem_display_cart_page'] ) ) {
                update_post_meta( $post_id, 'cnphem_display_cart_page', sanitize_meta( '', wp_unslash( $_POST['cnphem_display_cart_page'] ), '' ) );
            }

        }

        /**
         * Product page hooks.
         * Display custom notice on product page.
         */
        public function add_product_page_hooks() {
            add_action(
                'woocommerce_before_single_product',
                function() {
                    cnphem_get_notice_data_for_product_page(1);
                }
            );
            
            add_action(
                'woocommerce_before_add_to_cart_form',
                function() {
                    cnphem_get_notice_data_for_product_page(2);
                }
            );
            
            add_action(
                'woocommerce_after_add_to_cart_quantity',
                function() {
                    cnphem_get_notice_data_for_product_page(3);
                }
            );
            add_action(
                'woocommerce_after_add_to_cart_button',
                function() {
                    cnphem_get_notice_data_for_product_page(4);
                }
            );
            
        }

        /**
         * Cart page hooks.
         * Display custom notice on product page.
         */
        public function add_cart_page_hooks() {
            add_action(
                'woocommerce_before_cart',
                function() {
                    cnphem_get_notice_data_for_cart_page(1);
                    return;
                }
            );
            
            add_action(
                'woocommerce_after_cart_table',
                function() {
                    cnphem_get_notice_data_for_cart_page(2);
                    return;
                }
            );
        }
    }
    new Custom_Notice_for_Products();
}

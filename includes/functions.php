<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Get notice data for cart page.
 */
if ( ! function_exists( 'cnphem_get_notice_data_for_product_page' ) ) {
    function cnphem_get_notice_data_for_product_page($id) {
        global $product;
        $args = array(
          'numberposts' => -1,
          'post_type'   => 'cnphem',
          'status'      => 'publish',
        );
        $notices = get_posts( $args );
        if(!empty($notices)) :
            foreach ( $notices as $notice ) :
                $products       = get_post_meta( $notice->ID, 'cnphem_products', true );
                $products_cats  = get_post_meta( $notice->ID, 'cnphem_cat', true );
                $product_page   = get_post_meta( $notice->ID, 'cnphem_display_product_page', true );
                if ($product_page == $id) {
                    if ( in_array( 'all', $products ) ) { ?>
                        <div class="woocommerce-info"><?php echo esc_html($notice->post_content); ?></div>
                    <?php 
                    }
                    elseif (in_array( $product->get_ID(), $products )) { ?>
                        <div class="woocommerce-info"><?php echo esc_html($notice->post_content); ?></div>
                    <?php 
                    }
                    elseif (in_array( 'all', $products_cats )) { ?>
                        <div class="woocommerce-info"><?php echo esc_html($notice->post_content); ?></div>
                    <?php 
                    }
                    else{
                        $terms = get_the_terms ( $product->get_ID(), 'product_cat' );
                        foreach ( $terms as $term ) {
                            $cat_id = $term->term_id;
                            if (in_array( $cat_id, $products_cats )) { ?>
                                <div class="woocommerce-info"><?php echo esc_html($notice->post_content); ?></div>
                                <?php 
                                break;
                            }
                        }
                    }
                }
            endforeach;
        endif;
    }
}

/**
 * Get notice data for cart page.
 */
if ( ! function_exists( 'cnphem_get_notice_data_for_cart_page' ) ) {
    function cnphem_get_notice_data_for_cart_page($id) {
        global $product;
        $args = array(
          'numberposts' => -1,
          'post_type'   => 'cnphem',
          'status'      => 'publish',
        );
        $notices = get_posts( $args );
        if(!empty($notices)) :
            foreach ( $notices as $notice ) :
                $cart_always    = get_post_meta( $notice->ID, 'cnphem_cartAlways', true );
                $products       = get_post_meta( $notice->ID, 'cnphem_productsCart', true );
                $products_cats  = get_post_meta( $notice->ID, 'cnphem_catCart', true );
                $cart_page      = get_post_meta( $notice->ID, 'cnphem_display_cart_page', true );
                if ($cart_page == $id) {
                    if ($cart_always == 1) { ?>
                        <div class="woocommerce-info"><?php echo esc_html($notice->post_content); ?></div>
                    <?php 
                    }
                    elseif ( is_array($products) && in_array( 'all', $products ) ) { ?>
                        <div class="woocommerce-info"><?php echo esc_html($notice->post_content); ?></div>
                    <?php 
                    }
                    elseif (is_array($products_cats) && in_array( 'all', $products_cats )) { ?>
                        <div class="woocommerce-info"><?php echo esc_html($notice->post_content); ?></div>
                    <?php 
                    }
                    else{
                        global $woocommerce;
                        $items = $woocommerce->cart->get_cart();
                        foreach($items as $item => $values) { 
                            $_cart_pro_id = $values['product_id'];
                            $terms        = get_the_terms ( $_cart_pro_id, 'product_cat' );
                            if (is_array($products) && in_array( $_cart_pro_id, $products )) { ?>
                                <div class="woocommerce-info"><?php echo esc_html($notice->post_content); ?></div>
                            <?php 
                                continue;
                            }
                            else{
                                foreach ( $terms as $term ) {
                                    $cat_id = $term->term_id;
                                    if (is_array($products_cats) && in_array( $cat_id, $products_cats )) { ?>
                                        <div class="woocommerce-info"><?php echo esc_html($notice->post_content); ?></div>
                                    <?php 
                                        break;
                                    }
                                }
                            }
                        } 
                    }
                }
            endforeach;
        endif;
    }
}
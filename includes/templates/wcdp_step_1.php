<?php
/*
WCDP Shortcode Form
*/

if(!defined('ABSPATH')) exit;

    if ($has_child) {
        $get_variations = count( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
        $available_variations = $get_variations ? $product->get_available_variations() : false;
        $attributes = $product->get_variation_attributes();
        $attribute_keys  = array_keys( $attributes );
        $variations_json = wp_json_encode( $available_variations );
        $variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );
        $selected_attributes = $product->get_default_attributes();
        $get_variations = count( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
    }

    //Display title of product
    if ($value['title']) {
        ?>
            <h3 class="product_title wcdp-title"><?php echo esc_html($product->get_title()); ?></h3>
        <?php
    }

    //Display short description of product
    if ($value['short_description']) {
        ?>
        <p class="wcdp-short-description wcdp-row"><?php echo apply_filters('the_content', $product->get_short_description()); ?></p>
        <?php
    }

    //Display description of product
    if ($value['description']) {
        ?>
            <p class="wcdp-description wcdp-row"><?php echo apply_filters('the_content', $product->get_description()); ?></p>
        <?php
    }
?>

<form class="variations_form cart wcdp-choose-donation" id="<?php
	if ($value['style'] != '4') {
		echo 'wcdp-ajax-send';
	} else {
		echo 'wcdp-post-send';
	} ?>"
	  method="post"
      action="<?php
      if ($value['style'] != '4') {
          echo admin_url('admin-ajax.php');
      } else {
          echo wc_get_checkout_url();
      } ?>"
      autocomplete="off" enctype='multipart/form-data' data-product_id="<?php echo $value['id']; ?>"
	<?php if ($has_child): ?>
	  	data-product_variations="<?php echo $variations_attr;?>"
	<?php endif; ?>
	>
	<input type="hidden" name="action" value="wcdp_ajax_donation_calculation">
	<input type="hidden" name="security" value="<?php echo wp_create_nonce('wcdp_ajax_nonce' . $value['id']);?>">
	<input type="hidden" name="postid" value="<?php echo $value['id'] ?>">

	<?php
        //Donation Amount section
        include('wcdp_step_1_amount.php');

        //Variation fields
        include('wcdp_step_1_variations.php');
    ?>
		<?php if ( $value['style'] == 1 || $value['style'] == 3 || $value['style'] == 5) : ?>
	        <div class="wcdp-divider"></div>
			<button class="button wcdp-button wcdp-right" type="button" id="wcdp-ajax-button" value="2"><?php esc_html_e( 'Next', 'wc-donation-platform' ); ?>&nbsp;<div class="wcdp-arrow wcdp-right-arrow">&raquo;</div></button>
		    <div class="lds-ellipsis" id="wcdp-spinner"><div></div><div></div><div></div><div></div></div>
		    <div class="wcdp-divider"></div>
		<?php elseif ($value['style'] == '4') : ?>
            <div class="wcdp-divider"></div>
            <button class="button wcdp-right" type="submit">
                <?php esc_html_e( 'Donate', 'wc-donation-platform' ); ?>
                <div class="wcdp-arrow wcdp-right-arrow">&raquo;</div>
            </button>
        <?php endif;?>
</form>
    <ul class="woocommerce-error" role="alert" style="display: none" id="ajax-unexpected-error"><li><?php esc_html_e( 'An unexpected error occurred. Please reload the page and try again. If the problem persists, please contact our support team.', 'wc-donation-platform' ); ?></li></ul>
<?php
do_action('woocommerce_after_add_to_cart_form');

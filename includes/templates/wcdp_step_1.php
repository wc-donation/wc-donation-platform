<?php
/**
 * WCDP Shortcode Form
 * @var int $product_id
 * @var bool $has_child
 * @var $product
 * @var array $value
 * @var string $context
 * @var string $form_id
 */

if (!defined('ABSPATH')) exit;

if ($has_child) {
    $get_variations = count($product->get_children()) <= apply_filters('woocommerce_ajax_variation_threshold', 30, $product);
    $available_variations = $get_variations ? $product->get_available_variations() : false;
    $attributes = $product->get_variation_attributes();
    $attribute_keys = array_keys($attributes);
    $variations_json = wp_json_encode($available_variations);
    $variations_attr = function_exists('wc_esc_json') ? wc_esc_json($variations_json) : _wp_specialchars($variations_json, ENT_QUOTES, 'UTF-8', true);
    $selected_attributes = $product->get_default_attributes();
    $get_variations = count($product->get_children()) <= apply_filters('woocommerce_ajax_variation_threshold', 30, $product);
}
$min_donation_amount = (float)apply_filters('wcdp_min_amount', get_option('wcdp_min_amount', 3), $product_id);
$max_donation_amount = (float)apply_filters('wcdp_max_amount', get_option('wcdp_max_amount', 3), $product_id, $min_donation_amount);

if ($max_donation_amount < $min_donation_amount) {
    update_option('wcdp_max_amount', $min_donation_amount);
    $max_donation_amount = $min_donation_amount;
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

do_action('woocommerce_before_add_to_cart_form');
?>

    <form class="variations_form cart wcdp-choose-donation"<?php
    if ($value['style'] != '4') {
        echo ' id="wcdp-ajax-send"';
        echo ' method="post"';
        echo ' action="' . admin_url('admin-ajax.php') . '"';
    } else {
        echo ' id="wcdp-get-send"';
        if (get_option('wcdp_redirect_to_cart', 'no') === 'no') {
            echo ' method="get"';
            echo ' action="' . wc_get_checkout_url() . '"';
        } else {
            echo ' method="post"';
            echo ' action="' . wc_get_cart_url() . '"';
        }
    }
    if ($context === 'embed') {
        echo ' target="_blank"';
    }
    ?>
          autocomplete="off" enctype='multipart/form-data' data-product_id="<?php echo $value['id']; ?>" data-formid="<?php echo $form_id; ?>"
        <?php if ($has_child): ?>
            data-product_variations="<?php echo $variations_attr; ?>"
        <?php endif; ?>
          wcdp-error-default="<?php echo _wp_specialchars(wp_kses(__('An unexpected error occurred. Please reload the page and try again. If the problem persists, please contact our support team.', 'wc-donation-platform'), array()), ENT_QUOTES, 'UTF-8', true); ?>"
    >
        <input type="hidden" name="action" value="wcdp_ajax_donation_calculation">
        <input type="hidden" name="security" value="<?php echo wp_create_nonce('wcdp_ajax_nonce' . $value['id']); ?>">
        <input type="hidden" name="postid" value="<?php echo $value['id'] ?>">
        <input type="hidden" name="wcdp_form_id" value="<?php echo $form_id ?>">

        <?php
        //Donation Amount section
        wc_get_template('wcdp_step_1_amount.php',
            array(
                'min_donation_amount'=> $min_donation_amount,
                'max_donation_amount' => $max_donation_amount,
                'product_id' => $product_id,
                'product' => $product,
                'value' => $value,
                'form_id' => $form_id,
            ), '', WCDP_DIR . 'includes/templates/');

        //Variation fields
        wc_get_template('wcdp_step_1_variations.php',
            array(
                'has_child' => $has_child,
                'product' => $product,
                'attributes' => $attributes ?? [],
                'product_id' => $product_id,
                'form_id' => $form_id,
            ), '', WCDP_DIR . 'includes/templates/');
        ?>
        <?php if ($value['style'] == 1 || $value['style'] == 3 || $value['style'] == 5) : ?>
            <button class="button wcdp-button wcdp-right" type="button" id="wcdp-ajax-button" data-step="2">
                <?php echo apply_filters('wcdp_next_button', esc_html__('Next', 'wc-donation-platform'), $value['id'], 1); ?>
                &nbsp;<div class="wcdp-arrow wcdp-right-arrow">&raquo;</div>
            </button>
            <div class="lds-ellipsis" id="wcdp-spinner">
                <div></div>
                <div></div>
                <div></div>
                <div></div>
            </div>
        <?php elseif ($value['style'] == '4') : ?>
            <button class="button wcdp-button wcdp-right" type="submit">
                <?php esc_html_e('Donate', 'wc-donation-platform'); ?>&nbsp;<div class="wcdp-arrow wcdp-right-arrow">
                    &raquo;
                </div>
            </button>
        <?php endif; ?>
        <div class="wcdp-divider"></div>

        <?php //WooCommerce Add to Cart & Quantity (invisible) ?>
        <input style="display:none !important;" type="number" name="quantity" class="quantity qty" value="1" aria-hidden="true">
        <?php
        /** @var bool $context */
        if ($value['style'] == 4 && $context == 'product-page') {
            do_action('wcdp_express_checkout_heading');
            do_action('woocommerce_after_add_to_cart_quantity');
        }
        ?>
        <button style="display:none !important;" type="submit" name="add-to-cart"
                value="<?php echo esc_attr($product->get_id()); ?>"
                class="single_add_to_cart_button button alt"></button>
        <?php do_action('woocommerce_after_add_to_cart_button'); ?>
    </form>

<?php
if ($value['style'] == 4 && $context == 'product-page') {
    //Add a input field to communicate correct donation amount to Stripe & PayPal Express donation buttons
    do_action('wcdp_express_checkout_amount_variation');

    do_action('woocommerce_after_add_to_cart_form');
}

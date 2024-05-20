<?php

// Add custom fields to shipping method settings
// Add custom settings fields to WooCommerce
add_filter('woocommerce_shipping_settings', 'add_custom_shipping_settings_fields');

function add_custom_shipping_settings_fields($settings) {
    $custom_settings = array(
        array(
            'title' => __('WooCommerce Custom Shipping Boxes', 'your-text-domain'),
            'type'  => 'title',
            'id'    => 'custom_shipping_settings_section'
        ),
        array(
            'title'    => __('Small Box Charge', 'your-text-domain'),
            'id'       => 'small_box_charge',
            'type'     => 'text',
            'desc'     => __('Enter the charge for a small box.', 'your-text-domain'),
            'default'  => '2',
        ),
        array(
            'title'    => __('Medium Box Charge', 'your-text-domain'),
            'id'       => 'medium_box_charge',
            'type'     => 'text',
            'desc'     => __('Enter the charge for a medium box.', 'your-text-domain'),
            'default'  => '3',
        ),
        array(
            'title'    => __('Large Box Charge', 'your-text-domain'),
            'id'       => 'large_box_charge',
            'type'     => 'text',
            'desc'     => __('Enter the charge for a large box.', 'your-text-domain'),
            'default'  => '5',
        ),
        array(
            'title'    => __('Small Box Volume', 'your-text-domain'),
            'id'       => 'small_box_volume',
            'type'     => 'text',
            'desc'     => __('Enter the volume for a small box. Example to get the total volume:  15444 = 39cm x 28cm x 14cm', 'your-text-domain'),
            'default'  => '15444',
        ),
        array(
            'title'    => __('Medium Box Volume', 'your-text-domain'),
            'id'       => 'medium_box_volume',
            'type'     => 'text',
            'desc'     => __('Enter the volume for a medium box. Example to get the total volume:  21224 = 44cm x 28cm x 17cm', 'your-text-domain'),
            'default'  => '21224',
        ),
        array(
            'title'    => __('Large Box Volume', 'your-text-domain'),
            'id'       => 'large_box_volume',
            'type'     => 'text',
            'desc'     => __('Enter the volume for a large box. Example to get the total volume:  31860 = 41cm x 30cm x 26cm', 'your-text-domain'),
            'default'  => '31860',
        ),
        array(
            'type' => 'sectionend',
            'id'   => 'custom_shipping_settings_section'
        ),
    );

    return array_merge($settings, $custom_settings);
}


$smallBoxCharge = (float) get_option('small_box_charge');
$mediumBoxCharge = (float) get_option('medium_box_charge');
$largeBoxCharge = (float) get_option('large_box_charge');
$smallBoxVolume = (float) get_option('small_box_volume');
$mediumBoxVolume = (float) get_option('medium_box_volume');
$largeBoxVolume = (float) get_option('large_box_volume');


// Function to calculate total cart weight
function calculate_cart_total_weight() {
    $cart = WC()->cart->get_cart();
    $total_weight = 0;

    foreach ($cart as $item_key => $item) {
        $product_id = $item['product_id'];
        $product = wc_get_product($product_id);

        if (!$product->is_virtual()) {
            $product_weight = $product->get_weight();
            $quantity = $item['quantity'];
            $subtotal_weight = $product_weight * $quantity;
            $total_weight += $subtotal_weight;
        }
    }

    return $total_weight;
}

// Function to calculate total cart volume
function calculate_cart_total_volume() {
    $cart = WC()->cart->get_cart();
    $total_volume = 0;

    foreach ($cart as $item_key => $item) {
        $product_id = $item['product_id'];
        $product = wc_get_product($product_id);

        if (!$product->is_virtual()) {
            $length = $product->get_length();
            $width = $product->get_width();
            $height = $product->get_height();
            $quantity = $item['quantity'];
            $subtotal_volume = $length * $width * $height * $quantity;
            $total_volume += $subtotal_volume;
        }
    }

    return $total_volume;
}

// Function to calculate number of boxes needed
function calculate_number_of_boxes_needed($total_volume) {
    $largest_box_volume = 31860; // Volume of the largest box (41cm x 30cm x 26cm)
    $number_of_boxes_needed = ceil($total_volume / $largest_box_volume);
    return $number_of_boxes_needed;
}

// Function to calculate additional box charge
function calculate_additional_box_charge($total_volume) {
    $smallBoxCharge = (float) get_option('small_box_charge');
    $mediumBoxCharge = (float) get_option('medium_box_charge');
    $largeBoxCharge = (float) get_option('large_box_charge');
    $smallBoxVolume = (float) get_option('small_box_volume');
    $mediumBoxVolume = (float) get_option('medium_box_volume');
    $largeBoxVolume = (float) get_option('large_box_volume');

    $additional_box_charge = 0;
    $recommended_box_size = '';
  if ($total_volume >= $largeBoxVolume) {
    // Calculate remaining volume after packing into the largest box
    $remaining_volume = $total_volume - $largeBoxVolume;
    // Check if remaining items can fit into smaller boxes

    if ($remaining_volume <= $smallBoxVolume) {
        // Remaining items can fit into SMALL BOX
        $recommended_box_size = 'small_box';
    } elseif ($remaining_volume <= $mediumBoxVolume) {
        // Remaining items can fit into MEDIUM BOX
        $recommended_box_size = 'medium_box';
    } else {
        // Remaining items require additional boxes
        $recommended_box_size = 'large_box';
       
    }
  } 
    switch ($recommended_box_size) {
        case 'small_box':
            $additional_box_charge = $smallBoxCharge; // Charge per additional small box
            break;
        case 'medium_box':
            $additional_box_charge = $mediumBoxCharge; // Charge per additional medium box
            break;
        case 'large_box':
            $additional_box_charge = $largeBoxCharge; // Charge per additional large box
            break;
        default:
            $additional_box_charge = 0;
    }

    $number_of_boxes_needed = calculate_number_of_boxes_needed($total_volume);
    $additional_boxes = $number_of_boxes_needed - 1; // Number of additional boxes
    $total_additional_charge = $additional_boxes * $additional_box_charge;
    return $total_additional_charge;
}

function get_recommended_box_size($total_weight, $total_volume) {
    $smallBoxCharge = (float) get_option('small_box_charge');
    $mediumBoxCharge = (float) get_option('medium_box_charge');
    $largeBoxCharge = (float) get_option('large_box_charge');
    $smallBoxVolume = (float) get_option('small_box_volume');
    $mediumBoxVolume = (float) get_option('medium_box_volume');
    $largeBoxVolume = (float) get_option('large_box_volume');

    if ($total_weight <= 3) {
        return 'Satchel (up to 3 kg)';
    } elseif ($total_volume <= $smallBoxVolume && $total_weight <= 5) {
        return 'SMALL BOX (39cm x 28cm x 14cm up to 5 kg)';
    } elseif ($total_volume <= $mediumBoxVolume && $total_weight <= 10) {
        return 'MEDIUM BOX (44cm x 28cm x 17cm up to 10 kg)';
    } elseif ($total_volume <= $largeBoxVolume && $total_weight <= 22) {
        return 'LARGE BOX (41cm x 30cm x 26cm up to 22 kg)';
    } else {
        // Calculate remaining volume after packing into the largest box
        $remaining_volume = $total_volume - $largeBoxVolume;
        // Check if remaining items can fit into smaller boxes
        if ($remaining_volume <= 15444) {
            // Remaining items can fit into SMALL BOX
            $additional_boxes = ceil($remaining_volume / 15444);
            if ($additional_boxes < 1) {
                $additional_boxes = 1;
                return 'LARGE BOX <br>• ' . $additional_boxes . ' box';
            }else{
               return 'LARGE BOX & SMALL BOX  <br>• Additional ' . $additional_boxes . ' box <br>• Small Box: $' . $smallBoxCharge . ' per box'; 
            }
            
        } elseif ($remaining_volume <= $smallBoxVolume) {
            // Remaining items can fit into MEDIUM BOX
            $additional_boxes = ceil($remaining_volume / $mediumBoxVolume);
            return 'LARGE BOX & MEDIUM BOX  <br>• Additional ' . $additional_boxes .' box <br>• Medium Box: $' . $mediumBoxCharge . ' per box';
        } else {
            // Remaining items require additional boxes
            $additional_boxes = ceil($remaining_volume / $largeBoxVolume);
            return 'LARGE BOX <br> • Additional ' . $additional_boxes . ' box <br>• Large Box: $' . $largeBoxCharge . ' per box';
        }
    }
}


// Display total weight, total volume, number of boxes used, recommended box size, and additional charge for extra boxes
add_action('woocommerce_cart_totals_before_shipping', 'display_cart_shipping_info');
add_action('woocommerce_review_order_before_shipping', 'display_cart_shipping_info');

function display_cart_shipping_info() {
    $total_weight = calculate_cart_total_weight();
    $total_volume = calculate_cart_total_volume();
	$formatted_total_volume = number_format($total_volume); // Format total volume
    $recommended_box = get_recommended_box_size($total_weight, $total_volume);
    $number_of_boxes_needed = calculate_number_of_boxes_needed($total_volume);
    $additional_charge = calculate_additional_box_charge($total_volume);

    $smallBoxCharge = (float) get_option('small_box_charge');
    $mediumBoxCharge = (float) get_option('medium_box_charge');
    $largeBoxCharge = (float) get_option('large_box_charge');
    $smallBoxVolume = (float) get_option('small_box_volume');
    $mediumBoxVolume = (float) get_option('medium_box_volume');
    $largeBoxVolume = (float) get_option('large_box_volume');

    echo '<tr class="cart-total-weight">
            <th>Total Weight</th>
            <td>' . $total_weight . ' ' . get_option('woocommerce_weight_unit') . '</td>
          </tr>';
    echo '<tr class="cart-total-volume">
            <th>Total Volume</th>
            <td>' . $formatted_total_volume . ' cubic centimeters</td>
          </tr>';
	echo '<tr class="cart-box-info">
            <th>Box Limit Volume</th>
            <td>
            <strong>Small Box Limit:</strong><br> '.$smallBoxVolume.' cubic cm <br> 
            <strong>Medium Box Limit:</strong><br> '.$mediumBoxVolume.' cubic cm <br> 
            <strong>Large Box Limit:</strong><br> '.$largeBoxVolume.' cubic cm</td>
          </tr>';
    echo '<tr class="recommended-box-size">
            <th>Recommended Box Size <br> <small>(Additional number of boxes)</small></th>
            <td>' . $recommended_box . '</td>
          </tr>';
}

// Add additional box charge to cart total
add_action('woocommerce_cart_calculate_fees', 'add_additional_box_charge_to_cart');

function add_additional_box_charge_to_cart() {
    $total_volume = calculate_cart_total_volume();
    $recommended_box = get_recommended_box_size($total_weight, $total_volume); // Ensure $total_weight is defined
    $additional_charge = calculate_additional_box_charge($total_volume);

    if ($additional_charge > 0) {
        WC()->cart->add_fee('Total Additional Box Charge', $additional_charge);
    }
}

<?php
/**
 * Plugin Name: Custom Checkout Plugin
 * Description: Personaliza el proceso de checkout de WooCommerce.
 * Version: 1.0.3
 * Author: Xolotl Tech
 * License: GPL2
 */

// Evitar el acceso directo
defined('ABSPATH') or die('¡Sin acceso directo!');

// Agregar configuración al menú de administración
function ccp_add_admin_menu() {
    add_options_page(
        'Configuración de Checkout Personalizado',
        'Checkout Personalizado',
        'manage_options',
        'custom-checkout-plugin',
        'ccp_settings_page'
    );
}
add_action('admin_menu', 'ccp_add_admin_menu');

// Página de configuración del plugin
function ccp_settings_page() {
    ?>
    <div class="wrap">
        <h1>Configuración de Checkout Personalizado</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('ccp_options_group');
            do_settings_sections('custom-checkout-plugin');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Registrar ajustes
function ccp_register_settings() {
    register_setting('ccp_options_group', 'ccp_options');
    add_settings_section(
        'ccp_main_section',
        'Opciones de Checkout',
        'ccp_section_text',
        'custom-checkout-plugin'
    );
    add_settings_field(
        'ccp_enable_custom_fields',
        'Habilitar campos personalizados',
        'ccp_enable_custom_fields_input',
        'custom-checkout-plugin',
        'ccp_main_section'
    );
    add_settings_field(
        'ccp_enable_print_button',
        'Habilitar botón de impresión',
        'ccp_enable_print_button_input',
        'custom-checkout-plugin',
        'ccp_main_section'
    );
}
add_action('admin_init', 'ccp_register_settings');

function ccp_section_text() {
    echo '<p>Configura las opciones para personalizar el checkout de WooCommerce.</p>';
}

function ccp_enable_custom_fields_input() {
    $options = get_option('ccp_options');
    $checked = isset($options['enable_custom_fields']) ? checked(1, $options['enable_custom_fields'], false) : '';
    echo '<input type="checkbox" id="ccp_enable_custom_fields" name="ccp_options[enable_custom_fields]" value="1" ' . $checked . ' />';
}

function ccp_enable_print_button_input() {
    $options = get_option('ccp_options');
    $checked = isset($options['enable_print_button']) ? checked(1, $options['enable_print_button'], false) : '';
    echo '<input type="checkbox" id="ccp_enable_print_button" name="ccp_options[enable_print_button]" value="1" ' . $checked . ' />';
}

// Aplicar configuraciones al checkout
function ccp_apply_customizations($fields) {
    $options = get_option('ccp_options');

    if (isset($options['enable_custom_fields']) && $options['enable_custom_fields']) {
        unset($fields['billing']['billing_first_name']);
        unset($fields['billing']['billing_last_name']);
        unset($fields['billing']['billing_company']);
        unset($fields['billing']['billing_address_1']);
        unset($fields['billing']['billing_address_2']);
        unset($fields['billing']['billing_city']);
        unset($fields['billing']['billing_postcode']);
        unset($fields['billing']['billing_country']);
        unset($fields['billing']['billing_state']);
        unset($fields['billing']['billing_phone']);
        unset($fields['order']['order_comments']);
        unset($fields['billing']['billing_email']);
    }

    return $fields;
}
add_filter('woocommerce_checkout_fields', 'ccp_apply_customizations');

// Desactivar envío si campos personalizados están activos
function ccp_disable_shipping() {
    $options = get_option('ccp_options');

    if (isset($options['enable_custom_fields']) && $options['enable_custom_fields']) {
        add_filter('woocommerce_cart_needs_shipping', '__return_false');
        add_filter('woocommerce_cart_ready_to_calc_shipping', '__return_false');
        add_filter('woocommerce_shipping_calculator_enabled', '__return_false');
    }
}
add_action('init', 'ccp_disable_shipping');

// Seleccionar método por defecto si aplica
function ccp_set_default_checkout_fields() {
    if (!is_admin()) {
        $options = get_option('ccp_options');
        if (isset($options['enable_custom_fields']) && $options['enable_custom_fields']) {
            WC()->session->set('chosen_shipping_methods', array('local_pickup'));
            WC()->session->set('chosen_payment_method', 'cod');
        }
    }
}
add_action('template_redirect', 'ccp_set_default_checkout_fields');

// Eliminar elementos default de WooCommerce
function ccp_remove_order_details_and_message() {
    // Elimina la tabla de productos (ya lo hacíamos antes)
    remove_action('woocommerce_thankyou', 'woocommerce_order_details_table', 10);
    // Elimina el mensaje de agradecimiento
    remove_action('woocommerce_thankyou', 'woocommerce_thankyou_order_received_text', 10);
    // Elimina resumen (número de pedido, total, método de pago)
    remove_action('woocommerce_thankyou', 'woocommerce_order_details', 20);
}
add_action('woocommerce_before_thankyou', 'ccp_remove_order_details_and_message');

// Mostrar ticket personalizado (sin botón nuevo)
function ccp_add_ticket_on_thank_you_page($order_id) {
    $options = get_option('ccp_options');
    if (isset($options['enable_print_button']) && $options['enable_print_button']) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        ?>
        <style>
            @media print {
                header, footer, .site-header, .site-footer, .header, .footer {
                    display: none !important;
                }

                body * {
                    visibility: hidden;
                }

                .ticket-thermal, .ticket-thermal * {
                    visibility: visible;
                }

                .ticket-thermal {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 80mm;
                    font-family: monospace;
                    padding: 10px;
                    background: #fff;
                }

                .woocommerce-order, .woocommerce-notice, .woocommerce-order-overview {
                    display: none !important;
                }
            }
        </style>

        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                var printButton = document.querySelector('.print-button-ticket');
                if (printButton) {
                    printButton.addEventListener('click', function () {
                        window.print();
                    });
                }
            });
        </script>

        <div class="ticket-thermal">
            <p><strong>Orden #:</strong> <?php echo $order->get_order_number(); ?></p>
            <p><strong>Fecha:</strong> <?php echo $order->get_date_created()->format('d/m/Y H:i'); ?></p>
            <hr>
            <table style="width:100%; font-size:12px;">
                <thead>
                    <tr><th style="text-align:left;">Producto</th><th style="text-align:right;">Cant</th><th style="text-align:right;">Total</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($order->get_items() as $item): ?>
                        <tr>
                            <td style="text-align:left;"><?php echo $item->get_name(); ?></td>
                            <td style="text-align:right;"><?php echo $item->get_quantity(); ?></td>
                            <td style="text-align:right;"><?php echo wc_price($item->get_total()); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <hr>
            <p><strong>Total:</strong> <?php echo $order->get_formatted_order_total(); ?></p>
        </div>
        <?php
    }
}
add_action('woocommerce_thankyou', 'ccp_add_ticket_on_thank_you_page');
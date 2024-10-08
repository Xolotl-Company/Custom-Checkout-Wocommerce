<?php
/**
 * Plugin Name: Custom Checkout Plugin
 * Description: Personaliza el proceso de checkout de WooCommerce.
 * Version: 1.0.1
 * Author: Xolotl Tech
 * License: GPL2
 */

// Evitar el acceso directo
defined( 'ABSPATH' ) or die( '¡Sin acceso directo!' );

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
add_action( 'admin_menu', 'ccp_add_admin_menu' );

// Página de configuración del plugin
function ccp_settings_page() {
    ?>
    <div class="wrap">
        <h1>Configuración de Checkout Personalizado</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'ccp_options_group' );
            do_settings_sections( 'custom-checkout-plugin' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Registrar ajustes
function ccp_register_settings() {
    register_setting( 'ccp_options_group', 'ccp_options' );
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
add_action( 'admin_init', 'ccp_register_settings' );

function ccp_section_text() {
    echo '<p>Configura las opciones para personalizar el checkout de WooCommerce.</p>';
}

function ccp_enable_custom_fields_input() {
    $options = get_option( 'ccp_options' );
    $checked = isset( $options['enable_custom_fields'] ) ? checked( 1, $options['enable_custom_fields'], false ) : '';
    echo '<input type="checkbox" id="ccp_enable_custom_fields" name="ccp_options[enable_custom_fields]" value="1" ' . $checked . ' />';
}

function ccp_enable_print_button_input() {
    $options = get_option( 'ccp_options' );
    $checked = isset( $options['enable_print_button'] ) ? checked( 1, $options['enable_print_button'], false ) : '';
    echo '<input type="checkbox" id="ccp_enable_print_button" name="ccp_options[enable_print_button]" value="1" ' . $checked . ' />';
}

// Aplicar configuraciones
function ccp_apply_customizations( $fields ) {
    $options = get_option( 'ccp_options' );

    // Verificar si la opción de campos personalizados está habilitada
    if ( isset( $options['enable_custom_fields'] ) && $options['enable_custom_fields'] ) {
        // Eliminar campos innecesarios del checkout
        unset( $fields['billing']['billing_first_name'] );
        unset( $fields['billing']['billing_last_name'] );
        unset( $fields['billing']['billing_company'] );
        unset( $fields['billing']['billing_address_1'] );
        unset( $fields['billing']['billing_address_2'] );
        unset( $fields['billing']['billing_city'] );
        unset( $fields['billing']['billing_postcode'] );
        unset( $fields['billing']['billing_country'] );
        unset( $fields['billing']['billing_state'] );
        unset( $fields['billing']['billing_phone'] );
        unset( $fields['order']['order_comments'] );
        unset( $fields['billing']['billing_email'] );
    }

    return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'ccp_apply_customizations' );

function ccp_disable_shipping() {
    $options = get_option( 'ccp_options' );

    if ( isset( $options['enable_custom_fields'] ) && $options['enable_custom_fields'] ) {
        add_filter( 'woocommerce_cart_needs_shipping', '__return_false' );
        add_filter( 'woocommerce_cart_ready_to_calc_shipping', '__return_false' );
        add_filter( 'woocommerce_shipping_calculator_enabled', '__return_false' );
    }
}
add_action( 'init', 'ccp_disable_shipping' );

function ccp_set_default_checkout_fields() {
    if ( ! is_admin() ) {
        $options = get_option( 'ccp_options' );
        if ( isset( $options['enable_custom_fields'] ) && $options['enable_custom_fields'] ) {
            WC()->session->set( 'chosen_shipping_methods', array( 'local_pickup' ) );
            WC()->session->set( 'chosen_payment_method', 'cod' );
        }
    }
}
add_action( 'template_redirect', 'ccp_set_default_checkout_fields' );

// Mostrar el botón de impresión en la página de agradecimiento
function ccp_add_print_button_on_thank_you_page( $order_id ) {
    $options = get_option( 'ccp_options' );
    if ( isset( $options['enable_print_button'] ) && $options['enable_print_button'] ) {
        ?>
        <style>
            /* Ocultar el botón de imprimir en la página de finalizar compra */
            .print-button-ticket {
                display: none;
            }

            @media print {
                /* Ocultar header y footer durante la impresión */
                header, footer, .site-header, .site-footer, .header, .footer {
                    display: none !important;
                }

                /* Asegurar que el contenido de la página de agradecimiento se muestre */
                .woocommerce-order-received {
                    display: block;
                }

                /* Eliminar el label de vendedor */
                .woocommerce-order-items .order_item .product_meta {
                    display: none !important;
                }

                /* Eliminar todos los enlaces */
                .woocommerce-table.shop_table td a {
                    pointer-events: none;
                    color: black; /* Asegura que el texto no sea azul */
                }

                /* Ocultar el botón de imprimir ticket y su texto */
                .print-button-ticket {
                    display: none !important;
                }
            }

            /* Estilos personalizados para el botón de imprimir */
            .print-button-ticket {
                display: inline-block;
                font-weight: 600;
                color: #E5E5E5;
                text-align: center;
                white-space: nowrap;
                user-select: none;
                background-color: #E93E01;
                border: none;
                padding: 15px;
                font-size: 20px;
                border-radius: 16px;
                transition: all .3s;
                width: 100%;
            }

            /* Estilos para el hover del botón de imprimir */
            .print-button-ticket:hover {
                display: inline-block;
                font-weight: 600;
                color: #E5E5E5;
                text-align: center;
                white-space: nowrap;
                user-select: none;
                background-color: #648257; /* Cambia el color de fondo al pasar el mouse */
                border: none;
                padding: 15px;
                font-size: 20px;
                border-radius: 16px;
                transition: all .3s;
                width: 100%;
            }
        </style>

        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                var printButton = document.querySelector('.print-button-ticket');

                // Mostrar el botón solo en la página de agradecimiento
                var thankYouPage = document.querySelector('.woocommerce-order-received');
                if (thankYouPage && printButton) {
                    printButton.style.display = 'inline-block'; // Muestra el botón en la página de agradecimiento
                } else if (printButton) {
                    printButton.style.display = 'none'; // Oculta el botón en cualquier otra página
                }

                if (printButton) {
                    printButton.addEventListener('click', function() {
                        // Eliminar todos los enlaces en los títulos de productos antes de imprimir
                        document.querySelectorAll('.woocommerce-table.shop_table td a').forEach(function(link) {
                            link.removeAttribute('href');
                            link.style.color = 'black'; // Asegura que el texto no sea azul
                        });
                        window.print(); // Inicia la impresión cuando se hace clic en el botón

                        // Redireccionar al usuario a la página principal después de la impresión
                        window.addEventListener('afterprint', function() {
                            window.location.href = '<?php echo esc_url(home_url('/')); ?>';
                        });
                    });
                }
            });
        </script>
        <?php
    }
}
add_action( 'woocommerce_thankyou', 'ccp_add_print_button_on_thank_you_page' );




// Mostrar el botón de imprimir solo después del mensaje de agradecimiento
function ccp_display_print_button_after_thank_you() {
    ?>
    <button class="print-button-ticket">Imprimir Ticket</button>
    <?php
}
add_action( 'woocommerce_thankyou', 'ccp_display_print_button_after_thank_you', 20 );

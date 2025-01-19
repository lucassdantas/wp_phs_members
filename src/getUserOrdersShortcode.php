<?php 
defined('ABSPATH') or die();
if(!function_exists('add_action'))die;
function woocommerce_user_orders_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Você precisa estar logado para visualizar seus pedidos.</p>';
    }

    $user_id = get_current_user_id();
    $customer_orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'status' => array('wc-completed', 'wc-processing', 'wc-on-hold'), // Ajuste os status conforme necessário
        'limit' => -1,
    ));

    if (empty($customer_orders)) {
        return '<p>Você ainda não fez nenhum pedido.</p>';
    }

    ob_start();
    ?>
    <table class="woocommerce-orders-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="border: 1px solid #ccc; padding: 8px;">Pedido</th>
                <th style="border: 1px solid #ccc; padding: 8px;">Data</th>
                <th style="border: 1px solid #ccc; padding: 8px;">Status</th>
                <th style="border: 1px solid #ccc; padding: 8px;">Valor Total</th>
                <th style="border: 1px solid #ccc; padding: 8px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customer_orders as $order): ?>
                <tr>
                    <td style="border: 1px solid #ccc; padding: 8px;"><?php echo $order->get_order_number(); ?></td>
                    <td style="border: 1px solid #ccc; padding: 8px;"><?php echo wc_format_datetime($order->get_date_created()); ?></td>
                    <td style="border: 1px solid #ccc; padding: 8px;"><?php echo wc_get_order_status_name($order->get_status()); ?></td>
                    <td style="border: 1px solid #ccc; padding: 8px;"><?php echo wc_price($order->get_total()); ?></td>
                    <td style="border: 1px solid #ccc; padding: 8px;">
                        <button class="view-order-details" data-order-id="<?php echo $order->get_id(); ?>" style="padding: 5px 10px; background: #0073aa; color: #fff; border: none; cursor: pointer;">
                            Visualizar
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Popup -->
    <div id="order-details-popup" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 20px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); z-index: 1000; max-width: 600px; width: 100%;">
        <div id="order-details-content"></div>
        <button id="close-order-details" style="margin-top: 10px; padding: 5px 10px; background: #0073aa; color: #fff; border: none; cursor: pointer;">Fechar</button>
    </div>
    <div id="popup-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 999;"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const buttons = document.querySelectorAll('.view-order-details');
            const popup = document.getElementById('order-details-popup');
            const overlay = document.getElementById('popup-overlay');
            const closeBtn = document.getElementById('close-order-details');
            const content = document.getElementById('order-details-content');

            buttons.forEach(button => {
                button.addEventListener('click', function () {
                    const orderId = this.getAttribute('data-order-id');

                    // Fetch order details via AJAX
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=get_order_details&order_id=' + orderId)
                        .then(response => response.text())
                        .then(data => {
                            content.innerHTML = data;
                            popup.style.display = 'block';
                            overlay.style.display = 'block';
                        });
                });
            });

            closeBtn.addEventListener('click', function () {
                popup.style.display = 'none';
                overlay.style.display = 'none';
            });

            overlay.addEventListener('click', function () {
                popup.style.display = 'none';
                overlay.style.display = 'none';
            });
        });
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('woocommerce_user_orders', 'woocommerce_user_orders_shortcode');

function ajax_get_order_details() {
    if (!is_user_logged_in() || empty($_GET['order_id'])) {
        wp_send_json_error('Acesso negado.');
    }

    $order_id = absint($_GET['order_id']);
    $order = wc_get_order($order_id);

    if (!$order || $order->get_user_id() !== get_current_user_id()) {
        wp_send_json_error('Pedido não encontrado.');
    }

    ob_start();
    ?>
    <h2>Detalhes do Pedido #<?php echo $order->get_order_number(); ?></h2>
    <p><strong>Data:</strong> <?php echo wc_format_datetime($order->get_date_created()); ?></p>
    <p><strong>Status:</strong> <?php echo wc_get_order_status_name($order->get_status()); ?></p>
    <p><strong>Total:</strong> <?php echo wc_price($order->get_total()); ?></p>
    <h3>Itens do Pedido</h3>
    <ul>
        <?php foreach ($order->get_items() as $item): ?>
            <li>
                <?php echo $item->get_name(); ?> (<?php echo $item->get_quantity(); ?>) - <?php echo wc_price($item->get_total()); ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
    echo ob_get_clean();
    wp_die();
}

add_action('wp_ajax_get_order_details', 'ajax_get_order_details');

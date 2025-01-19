<?php 
// Atualizar o metafield 'fase_adquirida' do usuário após uma compra.
add_action('woocommerce_order_status_completed', 'atualizar_fase_adquirida', 10, 1);

function atualizar_fase_adquirida($order_id) {
    // Obter os dados do pedido.
    $order = wc_get_order($order_id);

    // Verificar se o pedido é válido.
    if (!$order) {
        return;
    }

    // Obter o ID do usuário que fez o pedido.
    $user_id = $order->get_user_id();

    // Verificar se há um usuário associado ao pedido.
    if (!$user_id) {
        return;
    }

    // Percorrer os itens do pedido.
    foreach ($order->get_items() as $item) {
        // Obter o slug do produto adquirido.
        $product_id = $item->get_product_id();
        $product = wc_get_product($product_id);

        if ($product) {
            $product_slug = $product->get_slug();

            // Atualizar o campo 'fase_adquirida' do usuário.
            update_user_meta($user_id, 'fase_adquirida', $product_slug);

            // Como só precisamos de um slug, podemos parar após o primeiro.
            break;
        }
    }
}

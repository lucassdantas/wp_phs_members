<?php 
add_action('woocommerce_after_order_notes', 'adicionar_campo_turmas_checkout');
function adicionar_campo_turmas_checkout($checkout) {
    // Obter os produtos no carrinho.
    $cart_items = WC()->cart->get_cart();

    // Arrays para armazenar as turmas por fase.
    $turmas_fase_1 = [];
    $turmas_fase_2 = [];

    // Percorrer os produtos no carrinho.
    foreach ($cart_items as $cart_item) {
        $product = $cart_item['data'];
        $product_slug = $product->get_slug();

        // Obter as turmas com base no slug do produto.
        if ($product_slug === 'fase-1') {
            $turmas_fase_1 = obter_turmas_por_fase('fase1');
        } elseif ($product_slug === 'fase-2') {
            $turmas_fase_2 = obter_turmas_por_fase('fase2');
        } elseif ($product_slug === 'combo-fase-1-e-2') {
            $turmas_fase_1 = obter_turmas_por_fase('fase1');
            $turmas_fase_2 = obter_turmas_por_fase('fase2');
        }
    }

    // Adicionar o campo para as turmas da fase 1.
    if (!empty($turmas_fase_1)) {
        woocommerce_form_field('turma_fase_1', [
            'type' => 'select',
            'label' => __('Selecione a turma da Fase 1', 'woocommerce'),
            'required' => true,
            'options' => $turmas_fase_1,
        ], $checkout->get_value('turma_fase_1'));
    }

    // Adicionar o campo para as turmas da fase 2.
    if (!empty($turmas_fase_2)) {
        woocommerce_form_field('turma_fase_2', [
            'type' => 'select',
            'label' => __('Selecione a turma da Fase 2', 'woocommerce'),
            'required' => true,
            'options' => $turmas_fase_2,
        ], $checkout->get_value('turma_fase_2'));
    }
}

// Obter as turmas por fase com base no metafield 'fase'.
function obter_turmas_por_fase($fase) {
    $turmas_query = new WP_Query([
        'post_type' => 'turmas',
        'meta_query' => [
            [
                'key' => 'fase',
                'value' => $fase,
                'compare' => '='
            ]
        ]
    ]);

    $turmas = [];
    if ($turmas_query->have_posts()) {
        $turmas[''] = __('Selecione uma turma', 'woocommerce'); // Placeholder.
        while ($turmas_query->have_posts()) {
            $turmas_query->the_post();
            $turmas[get_the_ID()] = get_the_title();
        }
        wp_reset_postdata();
    }

    return $turmas;
}

// Salvar os campos personalizados no pedido.
add_action('woocommerce_checkout_update_order_meta', 'salvar_turmas_no_pedido');
function salvar_turmas_no_pedido($order_id) {
    if (!empty($_POST['turma_fase_1'])) {
        update_post_meta($order_id, '_turma_fase_1', sanitize_text_field($_POST['turma_fase_1']));
    }
    if (!empty($_POST['turma_fase_2'])) {
        update_post_meta($order_id, '_turma_fase_2', sanitize_text_field($_POST['turma_fase_2']));
    }
}

// Exibir os campos personalizados no painel de administração do pedido.
add_action('woocommerce_admin_order_data_after_order_details', 'exibir_turmas_no_admin');
function exibir_turmas_no_admin($order) {
    $turma_fase_1 = get_post_meta($order->get_id(), '_turma_fase_1', true);
    $turma_fase_2 = get_post_meta($order->get_id(), '_turma_fase_2', true);

    if ($turma_fase_1) {
        echo '<p><strong>' . __('Turma da Fase 1:', 'woocommerce') . '</strong> ' . esc_html(get_the_title($turma_fase_1)) . '</p>';
    }
    if ($turma_fase_2) {
        echo '<p><strong>' . __('Turma da Fase 2:', 'woocommerce') . '</strong> ' . esc_html(get_the_title($turma_fase_2)) . '</p>';
    }
}

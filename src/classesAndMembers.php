<?php
// Shortcode para exibir a tabela das turmas CPT
function exibir_tabela_turmas_cpt() {
    // Consulta todos os custom post types do tipo 'turma'
    $args = [
        'post_type'      => 'turmas',
        'posts_per_page' => 5, // Limite de turmas por página
        'paged'           => get_query_var('paged', 1), // Paginação
    ];
    $query = new WP_Query($args);

    ob_start();
    ?>

    <table id="tabela-turmas" border="1">
        <thead>
            <tr>
                <th>Fase da Turma</th>
                <th>Data</th>
                <th>Vagas</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($query->have_posts()) :
                while ($query->have_posts()) : $query->the_post();
                    $turma_id = get_the_ID();
                    $data_bruta = get_post_meta($turma_id, 'data', true);
                    if (!empty($data_bruta)) {
                        $data_obj = DateTime::createFromFormat('Y-m-d\TH:i', $data_bruta);
                        $data = $data_obj ? $data_obj->format('d/m/Y H:i') : 'Formato inválido';
                    } else {
                        $data = 'Data não definida';
                    }

                    $vagas    = get_post_meta($turma_id, 'vagas', true);
                    $fase     = get_post_meta($turma_id, 'fase', true); // Campo para fase da turma
                    ?>
                    <tr>
                        <td><?php echo esc_html($fase); ?></td>
                        <td><?php echo esc_html($data); ?></td>
                        <td><?php echo esc_html($vagas); ?></td>
                        <td>
                            <button class="ver-alunos" data-turma-id="<?php echo esc_attr($turma_id); ?>" data-fase="<?php echo esc_attr($fase); ?>">
                                Ver Alunos
                            </button>
                        </td>
                    </tr>
                    <?php
                endwhile;
                wp_reset_postdata();
            else :
                echo '<tr><td colspan="4">Nenhuma turma encontrada.</td></tr>';
            endif;
            ?>
        </tbody>
    </table>

    <!-- Navegação de Paginação -->
    <div class="pagination">
        <?php
        echo paginate_links([
            'total' => $query->max_num_pages
        ]);
        ?>
    </div>

    <!-- Popup para exibir os alunos -->
    <div id="popup-overlay" style="display:none;"></div>
    <div id="popup-alunos" style="display:none;">
        <div id="popup-content">
            <h2>Alunos Inscritos</h2>
            <ul id="lista-alunos"></ul>
            <button id="fechar-popup">Fechar</button>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('.ver-alunos').on('click', function() {
                var turmaId = $(this).data('turma-id');
                var fase = $(this).data('fase');
                
                // Faz uma requisição AJAX para buscar os alunos
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'listar_alunos_cpt',
                        turma_id: turmaId,
                        fase: fase
                    },
                    success: function(response) {
                        var data = JSON.parse(response); // Decodifica a resposta JSON
                        $('#lista-alunos').html(data.html); // Preenche a lista de alunos no popup

                        // Se houver alunos, abre o popup
                        if (data.success) {
                            $('#popup-alunos').fadeIn(); // Exibe o popup
                            $('#popup-overlay').fadeIn(); // Exibe o fundo escurecido
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log("Erro AJAX:", error); // Log de erro caso ocorra
                    }
                });
            });

            // Fechar o popup ao clicar no botão "Fechar" ou no fundo escurecido
            $('#fechar-popup, #popup-overlay').on('click', function() {
                $('#popup-alunos').fadeOut(); // Fecha o popup
                $('#popup-overlay').fadeOut(); // Fecha o fundo escurecido
            });
        });
    </script>

    <style>
        /* Estilo para o fundo escurecido do popup */
        #popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            z-index: 999;
        }

        /* Estilo para o popup */
        #popup-alunos {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 400px;
            display: none;
            z-index: 1000;
        }

        #popup-content h2 {
            margin-top: 0;
        }

        #popup-content ul {
            list-style-type: none;
            padding-left: 0;
        }

        #popup-content li {
            margin: 10px 0;
        }

        /* Estilo para o botão de fechar */
        #fechar-popup {
            background-color: #ff5733;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        #fechar-popup:hover {
            background-color: #c94c2c;
        }
    </style>

    <?php
    return ob_get_clean();
}
add_shortcode('tabela_turmas_cpt', 'exibir_tabela_turmas_cpt');

// Função para listar alunos via AJAX
function listar_alunos_ajax_cpt() {
    $turma_id = intval($_POST['turma_id']);
    $fase     = sanitize_text_field($_POST['fase']);
    
    $metafield = $fase === '1' ? 'turma_fase_1' : 'turma_fase_2';

    // Consulta os usuários com o metafield correspondente à turma
    $args = [
        'meta_query' => [
            [
                'key'   => $metafield,
                'value' => $turma_id,
            ],
        ],
    ];
    $users = get_users($args);

    // Preparar resposta
    $response = [];

    if (!empty($users)) {
        $response['html'] = ''; // Resetando a variável de alunos
        foreach ($users as $user) {
            $response['html'] .= '<li>' . esc_html($user->display_name) . '</li>';
        }
        $response['success'] = true;
    } else {
        $response['html'] = '<li>Nenhum aluno inscrito.</li>';
        $response['success'] = false;
    }

    // Enviar a resposta JSON
    echo json_encode($response);

    wp_die(); // Sempre no final de um callback AJAX
}
add_action('wp_ajax_listar_alunos_cpt', 'listar_alunos_ajax_cpt');
add_action('wp_ajax_nopriv_listar_alunos_cpt', 'listar_alunos_ajax_cpt');

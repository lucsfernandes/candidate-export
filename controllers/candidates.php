<?php

class RHCandidatesController {

  public $post_type = 'candidatoscpack';

  function __construct()
  {
      // Actions
      add_action('init', [$this, 'candidates']);
      add_action('admin_menu', [$this, 'remove_meta_boxes']);
      add_action('restrict_manage_posts', [$this, 'admin_posts_filter_restrict_manage_posts']);

      // Ajax
      add_action('wp_ajax_export_candidates', [$this, 'export']);
      add_action('wp_ajax_nopriv_export_candidates', [$this, 'export']);

      // Filters
      add_filter('post_row_actions', [$this, 'remove_row_actions'], 10, 2);
      add_filter('parse_query', [$this, 'posts_filter']);

  }

  /**
   * Undocumented function
   *
   * @return void
   */
  public function admin_posts_filter_restrict_manage_posts()
  {
    $candidatesController = new RHCandidatesController();
    if(isset($_GET['post_type']) && $_GET['post_type'] == $candidatesController->post_type) {
      $posts = get_posts([
        'post_type' => $this->post_type,
        'showposts' => -1,
      ]);

      $job_name = [];
      foreach($posts as $post): 
        if (!in_array(get_post_meta($post->ID, 'area_candidato', true), $job_name)) {
          array_push($job_name, (get_post_meta($post->ID, 'area_candidato', true)));
        }
      endforeach;
      ?>
        
        <select name="job_name">
            <option value="" <?= empty($_GET['area_candidato']) ? 'selected' : ''; ?>>Todas as carreiras</option>
            <?php 
              foreach($job_name as $job):
            ?>
              <option value="<?= $job; ?>" <?= isset($_GET['job']) && $_GET['post_id'] == $post->ID ? 'selected' : ''; ?>>
                <?= $job; ?>
              </option>
            <?php endforeach; ?>
            <?php echo "<script>console.log('Area: " . $_GET['job_name'] . "' );</script>"; ?>
        </select>
        <a href="<?= admin_url('admin-ajax.php'); ?>?action=export_candidates&job_name=<?=$_GET['job_name']; ?>" class="button">Exportar CSV</a>
        <?php
        }
        ?>
        <?php
  }

  /**
    * Undocumented function
    *
    * @return void
  */
  public function posts_filter($query)
  {

    if(is_admin() && isset($_GET['post_type']) && $_GET['post_type'] == 'candidatoscpack' && isset($_GET['job_name'])) {
      global $pagenow;

      $query->query_vars['meta_key'] = 'area_candidato';
      $query->query_vars['meta_value'] = $_GET['job_name'];
    }
  }

  /**
    * Undocumented function
    *
    * @return void
  */
  public function export() {
    $filename = 'candidatos_' . date('Y-m-d_H-i-s');
    
    $data = implode(';', [
      'Data de Submissão',
      'Nome Completo',
      'E-mail',
      'Telefone',
      'Vaga Aplicada',
      'Escolaridade',
      'Possui Experiencia',
      'Empresa',
      'Cargo',
      'Inicio Experiencia',
      'Fim Experiencia',
      'Atual',
      'Deficiencia',
      'Cursos Complementares',
    ]);

    $data .= PHP_EOL;

    $query = [
      'post_type' => 'candidatoscpack',
      'showposts' => -1,
    ];

    if(isset($_GET['job_name'])) {
      $query['meta_key'] = 'area_candidato';
      $query['meta_value'] = $_GET['job_name'];
    }

    // Uma chamada ao banco de dados
    $posts = get_posts($query);

    foreach($posts as $post) {
      $experiencia_candidato = esc_attr(get_post_meta($post->ID, 'experiencia_candidato', true));
      $cursos_complementares_candidato = esc_attr(get_post_meta($post->ID, 'cursos_complementares_candidato', true));
      
      $arrayExperiencia;

      $cursos = [];

      //Percorre a quantidade de cursos complementares do candidato e adiciona na variavel $cursos
      for ($j=0; $j <= intval($cursos_complementares_candidato); $j++) { 
          array_push($cursos, get_post_meta($post->ID, "cursos_complementares_candidato_{$j}_curso_complementare_candidato", true));
      }

      for ($i=0; $i < intval($experiencia_candidato); $i++) {
        $atual = esc_attr(get_post_meta($post->ID, "experiencia_candidato_{$i}_atual_experiencia_candidato", true)) == 0 ? 'não' : 'sim';
        
        $data .= implode(';', [
            esc_attr(get_the_date('m/Y', $post)),
            esc_attr(get_post_meta($post->ID, 'nome_candidato', true)),
            esc_attr(get_post_meta($post->ID, 'e-mail_candidato', true)),
            esc_attr(get_post_meta($post->ID, 'telefone_candidato', true)),
            esc_attr(get_post_meta($post->ID, 'area_candidato', true)),
            esc_attr(get_post_meta($post->ID, 'escolaridade_candidato', true)),
            esc_attr(get_post_meta($post->ID, 'possui_experiencia', true)),
            esc_attr(get_post_meta($post->ID, "experiencia_candidato_{$i}_empresa_experiencia_candidato", true)),
            esc_attr(get_post_meta($post->ID, "experiencia_candidato_{$i}_cargo_experiencia_candidato", true)),
            esc_attr(get_post_meta($post->ID, "experiencia_candidato_{$i}_inicio_experiencia_candidato", true)),
            esc_attr(get_post_meta($post->ID, "experiencia_candidato_{$i}_fim_experiencia_candidato_copiar", true)),
            esc_attr($atual),
            esc_attr(get_post_meta($post->ID, 'deficiencia_candidato', true)),
            esc_attr(implode('; ', $cursos)),
        ]);

        $data .= PHP_EOL;
      };
    }

    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
        
    echo $data;

    die();
  }

  /**
    * Register a candidates post type
    *
    * @return void
    * @link http://codex.wordpress.org/Function_Reference/register_post_type
  */
  public function candidates() {
    register_post_type($this->post_type, array(
      'labels' => array(
          'name' => __('Candidatos'),
          'singular_name' => __('Candidato'),
          'add_new' => __('Adicionar novo candidato'),
          'add_new_item' => __('Adicionar novo candidato'),
          'edit_item' => __('Ver candidato'),
          'view_item' => __('Ver candidato'),
          'search_items' => __('Pesquisar candidatos'),
      ),
      'public' => true,
      'exclude_from_search' => true,
      'publicly_queryable' => true,
      'menu_icon' => 'dashicons-groups',
      'map_meta_cap' => true,
      'rewrite' => array(
          'slug' => $this->post_type,
      ),
      'supports' => array(
          'title', 'editor', 'export'
      ),
    ));
  }

  /**
   * Undocumented function
   * 
   * @return void 
  */
  public function remove_meta_boxes() {
    remove_meta_box('metabox_id', $this->post_type, 'default_position');
    remove_meta_box('submitdiv', $this->post_type, 'side');
  }

  /**
   * Undocumented function
   *
   * @param array $actions
   * @return array
  */
  public function remove_row_actions($actions, $post) {
    if ($post->post_type == $this->post_type) {
        $actions = [];
    }

    return $actions;
  }
}
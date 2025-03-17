  <?php 
  
    function change_api() {
      return 'json';
    }
    add_filter('rest_url_prefix', 'change_api');

    //Cria um custom post type
    function create_product_post() {
      register_post_type('products',
          array(
              'labels' => array(
                  'name' => __('Products'),
                  'singular_name' => __('Product'),
              ),
              'public'      => true,
              'has_archive' => false,
              'supports'    => array('title', 'editor', 'thumbnail', 'excerpt'),
              'show_in_rest' => true,
          )
      );
    }
  add_action('init', 'create_product_post');
  add_theme_support('post-thumbnails');

  //Adiciona excerpt aos posts de produto
  function adicionar_excerpt_api_rest() {
    register_rest_field('products', 'excerpt', array(
        'get_callback' => function($post_arr) {
            return get_the_excerpt($post_arr['id']);
        },
        'schema' => null,
    ));
  }
    add_action('rest_api_init', 'adicionar_excerpt_api_rest');

  //Cria categorias para o custon post type de produtos
  function create_category_to_products() {
    register_taxonomy_for_object_type('category', 'products');
  }
  add_action('init', 'create_category_to_products');
   
   //Função para pegar o custom post type produtos
    function get_custom_posts_products($request) {
      $args = array(
          'post_type' => 'products',
          'posts_per_page' => -1,
          'post_status' => 'publish',
          'order' => "ASC",
      );

      $data = array();
      $posts = get_posts($args);
      
      //Passando por todos os posts de produtos
      foreach ($posts as $post) {
          $categorias = wp_get_post_terms($post->ID, 'category'); 

          $categorias_data = array();

          foreach($categorias as $categoria) {
            $categorias_data[] = array(
              'id' => $categoria->term_id,
              'name' => $categoria->name,
              'link' => get_term_link($categoria),
            );
          }
          //Respondendo ao frontend todos os dados dos posts de produtos.
          $post_data = array(
              'id' => $post->ID,
              'title' => $post->post_title,
              'content' => wp_strip_all_tags(apply_filters('the_content', $post->post_content)),
              'thumbnail' => get_the_post_thumbnail_url($post->ID, 'full'),
              'permalink' =>  get_permalink($post->ID),
              'categories' => $categorias_data,
              'excerpt' => get_the_excerpt($post->ID),
          );
          $data[] = $post_data;
      }

      return rest_ensure_response($data);
    } 

  //Registrando uma rota customizada para produtos
    function register_custom_route() {
        register_rest_route('wp/v2', '/products/', array(
            'methods' => 'GET',
            'callback' => 'get_custom_posts_products',
        ));
    }
    add_action('rest_api_init', 'register_custom_route');

    //Criando custom post type formulario
    function criar_post_type_form() {
      register_post_type('formulario',
            array(
                'labels' => array(
                    'name' => __('Formularios'),
                    'singular_name' => __('Formulario'),
                ),
                'public' => true,
                'has_archive' => true,
                'supports' => array('title', 'editor', 'thumbnail'),
                'show_ui' => true,
                'show_in_rest' => true,
              ),
        );
      }
    add_action('init', 'criar_post_type_form');
    
    //Função que recebe e trata os dados enviados pelo front-end.
    function receive_form($request) {
      $json = $request->get_body();
      $params = json_decode($json, true);

      //Usando o método sanitize para limpar e padronizar os dados que vem do nosso front-end.
        $name = sanitize_text_field($params['name']);
        $email = sanitize_email($params['email']);
        $phone = sanitize_text_field($params['phone']);
        $message = sanitize_textarea_field($params['message']);

      //Criando um post dentro do Custom Post Type Formulários.
        $response = wp_insert_post(array(
          "post_title" => $name,
          "post_type" => "formulario",
          "post_content" => "Email: $email\nTelefone: $phone\nMensagem: $message",
          "post_status" => "publish",
        ));

        if($response) {
          return new WP_REST_Response(array(['message' => 'Formulário enviado com sucesso!']), 201);
        } else {
          return new WP_REST_Response(array(['message' => 'Erro ao enviar o formulário']), 500);
      }
      
    }

    //Registra o endpoint /formulario
    function register_receive_form() {  
      register_rest_route('v1', '/formulario', array(
          'methods' => WP_REST_Server::CREATABLE,
          'callback' => 'receive_form',
      ));
    }  
    add_action('rest_api_init','register_receive_form');

    //Registra os campos do advanced custom field
    function add_custom_fields_at_homepage() {
      register_rest_field('page', 'landpage', array(
          'get_callback' => function($post) {
              // Verifica se a página é a home
              if ($post['id'] == get_option('page_on_front')) {
                  return array(
                      'logotipo' => get_field('logotipo', $post['id']),
                      'hero_banner_title' => get_field('hero_banner_title', $post['id']),
                      'hero_banner_description' => get_field('hero_banner_description', $post['id']),
                      'hero_banner_scroll_image' => get_field('hero_banner_scroll_image', $post['id']),
                      'hero_banner_monks_image' => get_field('hero_banner_monks_image', $post['id']),
                      'second_section_title' => get_field('second_section_title', $post['id']),
                      'second_section_description' => get_field('second_section_description', $post['id']),
                      'third_section_title' => get_field('third_section_title', $post['id']),
                      'third_section_description' => get_field('third_section_description', $post['id']),
                      'third_section_image_1' => get_field('third_section_image_1', $post['id']),
                      'third_section_image_2' => get_field('third_section_image_2', $post['id']),
                      'third_section_image_3' => get_field('third_section_image_3', $post['id']),
                      'app_section_title' => get_field('app_section_title', $post['id']),
                      'app_section_description' => get_field('app_section_description', $post['id']),
                      'app_section_image1' => get_field('app_section_image1', $post['id']),
                      'link_google_play' => get_field('link_google_play', $post['id']),
                      'app_section_image2' => get_field('app_section_image2', $post['id']),
                      'link_app_store' => get_field('link_app_store', $post['id']),
                      'category_section_title' => get_field('category_section_title', $post['id']),    
                      'footer_image' => get_field('footer_image', $post['id']),    
                      'footer_title' => get_field('footer_title', $post['id']),    
                      'footer_description' => get_field('footer_description', $post['id']),    
                      'logo_instagram' => get_field('logo_instagram', $post['id']),
                      'link_instagram' => get_field('link_instagram', $post['id']),
                      'logo_whatsapp' => get_field('logo_whatsapp', $post['id']),
                      'link_whatsapp' => get_field('link_whatsapp', $post['id']),
                      'logo_twitter' => get_field('logo_twitter', $post['id']),
                      'link_twitter' => get_field('link_twitter', $post['id']),
                      'logo_facebook' => get_field('logo_facebook', $post['id']),
                      'link_facebook' => get_field('link_facebook', $post['id']),                      
                  );
              }
              return null;
          },
          'update_callback' => null,
          'schema' => null,
      ));
  }
  add_action('rest_api_init', 'add_custom_fields_at_homepage');

	function remover_comentarios_lwp($content) {
		// Remove os comentários específicos <l_wp paragraph -->
		$content = str_replace('<l_wp paragraph -->', '', $content);
		$content = str_replace('<l_wp paragraph -->', '', $content);
		return $content;
	}
	add_filter('the_content', 'remover_comentarios_lwp');

  remove_filter('the_content', 'wpautop');

  ?>
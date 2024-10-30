<?php
   /*
   Plugin Name: Hug Post View Counter
   Plugin URI: 
   description: A plugin that records post views and contains functions to easily list posts by popularity
   Version: 1.0.1
   Author: Nguyen Danh Hung
   Author URI: http://nguyendanhhung.me
   License: GPL2
   */


   
   /**
   * Adds a view to the post being viewed
   *
   * Finds the current views of a post and adds one to it by updating
   * the postmeta. The meta key used is "awepop_views".
   *
   * @global object $post The post object
   * @return integer $new_views The number of views the post has
   *
   */
   add_action("wp_ajax_hug_set_post_view", "hug_set_view_count");
   add_action("wp_ajax_nopriv_hug_set_post_view", "hug_set_view_count");
   function hug_set_view_count() {
      global $post;
      $post_id = sanitize_text_field($_POST['id']);
      $post = get_post($post_id);
      $_cookie = sanitize_text_field($_COOKIE['hug_post_visited']);
      if(isset($_cookie)){
         $visitedID = explode(',',$_cookie);
         $isVisited = in_array($post_id,$visitedID);
      }
      else{
         $visitedID = array();
         $isVisited = false;
      }
      
      if(!isset($_cookie) || !$isVisited){
         if(!wp_verify_nonce(sanitize_key($_POST['_ajax_nonce']),'view_count_plugin')){
            exit('No naughty business please');
         }
         
         $current_views = get_post_meta($post->ID, "hug_post_views", true);
         if(!isset($current_views) OR empty($current_views) OR !is_numeric($current_views) ) {
            $current_views = 0;
         }
         $new_views = $current_views + 1;
         update_post_meta($post->ID, "hug_post_views", $new_views);

         //Add to cookie that the post has visited
         if(!isset($_cookie)){
            setcookie('hug_post_visited',$post_id);
         }
         else{
            array_push($visitedID,$post_id);
            $_cookieStr = implode(',',$visitedID);
            setcookie('hug_post_visited',$_cookieStr);
         }

         $result['count'] =  $new_views;
      }
      else{
         $result['count'] = -1;
      }
      if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
         $result = json_encode($result);
         echo $result;
      }
      else {
         header("Location: ".$_SERVER["HTTP_REFERER"]);
      }
      die();
   }

   /**
    * Retrieve the number of views for a post
    *
    * Finds the current views for a post, returning 0 if there are none
    *
    * @global object $post The post object
    * @return integer $current_views The number of views the post has
    *
    */
   function hug_get_view_count($postID=null) {
      global $thisPost;
      if(!empty($postID)){
         $thisPost = get_post($postID);
      }
      $current_views = get_post_meta($thisPost->ID, "hug_post_views", true);
      if(!isset($current_views) OR empty($current_views) OR !is_numeric($current_views) ) {
         $current_views = 0;
      }

      return $current_views;
   }

   /**
   * Trigger plugin to the single post
   */
   function hug_regist_js_to_single_post(){
      if(is_single()){
         wp_register_script('hug_plugin_js', plugins_url( '/js/hug_plugin.js', __FILE__ ), array('jquery'));
         $transmit_array = array(
            'ajax_url'  => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'view_count_plugin' ),
            'id'        => get_the_ID()
         );
         wp_localize_script('hug_plugin_js','obj',$transmit_array);
         wp_enqueue_script( 'hug_plugin_js', plugins_url( '/js/hug_plugin.js', __FILE__ ), array('jquery'), true, true );
      }
   }
   add_action('the_post','hug_regist_js_to_single_post');

   /**
   * Create Hug Post View Counter Plugin setting page
   */
   function hug_post_view_counter_options_page_html() {
      // check user capabilities
      if ( ! current_user_can( 'manage_options' ) ) {
          return;
      }
      include_once('class-hug-post-view-table.php');
      wp_enqueue_style('plugin-pv-admin-style', plugins_url('/css/admin-style.css', __FILE__ ));

      echo '<div class="wrap">';
      echo '<div class="container">';
      echo '<div class="hug-pv-header">';
      echo '<h1 class="title">'.esc_html( get_admin_page_title() ).'</h1>';
      echo '</div>';
      echo '<div class="hug-pv-content">';
      echo '<div class="section">';
      echo '<div class="content">';
      echo '<p>HPVC used 2 methods to determine whether a article is viewed.</p>';
      echo '<ul>';
      echo '<li>- <b>Short Article(about 1 screen height):</b> After 30 seconds, the article is viewed.</li>';
      echo '<li>- <b>Long Article:</b> It will be set viewed whenever reader scrolled over 2/3 article\'s content.</li>';
      echo '</ul>';
      echo '</div>';
      echo '</div>';
      echo '<div class="section">';
      echo '<div class="content">';
      echo '<p>SHORTCODE.</p>';
      echo '<ul>';
      echo '<li>- <code>[hpvc]</code> --> <code>&lt;span&gt;Views of current post&lt;/span&gt;</code></li>';
      echo '<li>- <code>[hpvc post_id="your id"]</code> --> <code>&lt;span&gt;Views of post ID&lt;/span&gt;</code></li>';
      echo '<li>- php function call: <code>&lt;?php echo do_shortcode(\'[hpvc post_id="your id"]\'); ?&gt;</code></li>';
      echo '</ul>';
      echo '</div>';
      echo '</div>';
      echo '<div class="section">';
      echo '<div class="content">';

      $hugPostViewTable = new HUG_Post_View_Table();
      $hugPostViewTable->prepare_items();
      $hugPostViewTable->display();
      echo '</div>';
      echo '</div>';
      echo '</div>';
      echo '</div>';
  }

  /**
   * Add to admin setting menu
   */
   function hug_post_view_count_create_admin_menu()
   {
      add_submenu_page(
         'options-general.php',
         __('Hug Post View Counter Setting Page'),
         __('HPVC'),
         'manage_options',
         'hpvc',
         'hug_post_view_counter_options_page_html'
      );
   }
   add_action('admin_menu', 'hug_post_view_count_create_admin_menu');

   /**
    * create shortcode to display number of view in front-end
    */
   add_shortcode('hpvc', 'hug_post_view_counter_callback_fn');
   function hug_post_view_counter_callback_fn($attrs){
      $attr = shortcode_atts(array(
         'post_id' => '',
      ), $attrs);
      global $post;
      if(!empty($attr['post_id'])){
         $post = get_post($attr['post_id']);
      }
      $view_counter = get_post_meta($post->ID, 'hug_post_views', true);
      if(!isset($view_counter) OR empty($view_counter) OR !is_numeric($view_counter) ) {
         $view_counter = 0;
      }
      return '<span>'.esc_html($view_counter).'</span>';
   }
?>
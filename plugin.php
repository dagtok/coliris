<?php
/*
  Plugin Name: Coliris Widget
  Plugin URI: http://dagtok.com/wordpress-plugins/coliris-most-popular-recent-posts-widget/
  Description: A minimal and colored widget to display random, most popular, commented or recent posts as widget directly on your blog.
  Version: v1.2
  Author: Daniel Gutierrez
  Author URI: http://dagtok.com/
*/

global $wpdb,$popular_post_table;
$popular_post_table = $wpdb->prefix.'plugin_kbl_mncmt_most_popular_posts';

class KBL_RelevantContent extends WP_Widget {
    var $update_interval = "+5 Minutes";
    
    function KBL_RelevantContent() { /** constructor */
        parent::WP_Widget(false, $name = 'Coliris Widget'); 
    }

    function coliris_activate(){
      global $wpdb, $popular_post_table;
      $wpdb->query("CREATE TABLE IF NOT EXISTS " . $popular_post_table . " (
        id BIGINT(50) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        date DATETIME
      );");
    }

    function coliris_deactivate(){
      global $wpdb, $popular_post_table;
      $wpdb->query("DROP TABLE " . $popular_post_table );
      
      remove_action('wp_enqueue_scripts', array('KBL_RelevantContent', 'scripts_loaded'));
      remove_action('wp_head', array('KBL_RelevantContent', 'register_view'));
          
      unregister_widget('KBL_RelevantContent');
    }

    /** @see WP_Widget::form */
    function form($instance) {
        $title = esc_attr($instance['title']);
        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php echo __('Title:','coliris'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
        <p>     
        <label for="<?php echo $this->get_field_id('content_type'); ?>"><?php echo __('Select the type of content displayed:','coliris'); ?>
        <select name="<?php echo $this->get_field_name('content_type'); ?>" id="<?php echo $this->get_field_id('content_type'); ?>" class="widefat" onChange="if(jQuery(this).val() == 'populars' || jQuery(this).val() == 'commented'){  jQuery('#'+jQuery(this).attr('id').replace('content_type', 'display_lapse')).fadeIn();  } else {  jQuery('#'+jQuery(this).attr('id').replace('content_type', 'display_lapse')).fadeOut();} if(jQuery(this).val() == 'populars' || jQuery(this).val() == 'random' ){ jQuery('#'+jQuery(this).attr('id').replace('content_type', 'display_post_number')).fadeIn(); } else { jQuery('#'+jQuery(this).attr('id').replace('content_type', 'display_post_number')).fadeOut(); }  if(jQuery(this).val() == 'pages'){ jQuery('#'+jQuery(this).attr('id').replace('content_type', 'display_check_pages')).fadeIn(); jQuery('#'+jQuery(this).attr('id').replace('content_type', 'display_check_category')).hide(); } else { jQuery('#'+jQuery(this).attr('id').replace('content_type', 'display_check_category')).fadeIn(); jQuery('#'+jQuery(this).attr('id').replace('content_type', 'display_check_pages')).hide(); } if(jQuery(this).val() != 'pages'){ jQuery('#'+jQuery(this).attr('id').replace('content_type', 'date_tag_style')).fadeIn(); } else {  jQuery('#'+jQuery(this).attr('id').replace('content_type', 'date_tag_style')).fadeOut(); }">
          <option value="pages" <?php echo ($instance['content_type'] == 'pages' ? 'selected' : NULL)  ?>><?php echo __('Pages','coliris'); ?></option>
          <option value="recents" <?php echo ($instance['content_type'] == 'recents' ? 'selected' : NULL)  ?>><?php echo __('Recent Posts','coliris'); ?></option>
          <option value="populars" <?php echo ($instance['content_type'] == 'populars' ? 'selected' : NULL)  ?>><?php echo __('Popular Posts','coliris'); ?></option>
          <option value="commented" <?php echo ($instance['content_type'] == 'commented' ? 'selected' : NULL)  ?>><?php echo __('Most Commented','coliris'); ?></option>
          <option value="random" <?php echo ($instance['content_type'] == 'random' ? 'selected' : NULL)  ?>><?php echo __('Random Posts','coliris'); ?></option>
        </select>
        </p>
        <div id="<?php echo $this->get_field_id('display_check_pages'); ?>" style="display:none">
          <p>
            <label for="<?php echo $this->get_field_id('background_color'); ?>"><?php echo esc_attr(__('Select pages will be shown on this widget:','coliris')); ?></label>
            
            <ul class="popular-publications" <?php echo ((isset($instance['with']) AND $instance['with'] <> "") ? 'style="width:'.$instance['with'].'px;"' : NULL); ?>>
            <?php
            $pages = get_pages(); 
            foreach ($pages as $page) {
              $option='<li><input type="checkbox" id="'. $this->get_field_id( 'showed_pages' ) .'[]" name="'. $this->get_field_name( 'showed_pages' ) .'[]"';
              if (is_array($instance['showed_pages'])) {
                foreach ($instance['showed_pages'] as $cats) {
                  if($cats==$page->ID) {
                    $option=$option.' checked="checked"';
                  }
                }
              }
              $option .= ' value="'.$page->ID.'" />';
                    $option .= $page->post_title;
                    $option .= '<br />';
                    echo $option;
                }
            ?>
            </ul>
          </p>
        </div>
        <div id="<?php echo $this->get_field_id('display_check_category'); ?>" style="display:none">
          <p>
            <label for="<?php echo $this->get_field_id('category'); ?>"><?php echo esc_attr(__('Category:','coliris')); ?>
            <select name="<?php echo $this->get_field_name('category'); ?>"> 
             <option value="0"><?php echo esc_attr(__('All categories','coliris')); ?></option> 
             <?php 
              $categories =  get_categories();
              foreach ($categories as $category) {
                $option = '<option value="'.$category->cat_ID.'"'.(($instance['category'] == $category->cat_ID) ? ' selected' : NULL).'>';
              $option .= $category->cat_name;
              $option .= '</option>';
              echo $option;
              }
             ?>
            </select></label>
          </p>
        </div>
        <div id="<?php echo $this->get_field_id('display_lapse'); ?>" style="display:none">
          <p>
            <label for="<?php echo $this->get_field_id('filter_counter_date'); ?>"><?php _e('Filter Posts since:','coliris'); ?></label>
            <input type="date" id="<?php echo $this->get_field_id('filter_counter_date'); ?>" class="widefat" name="<?php echo $this->get_field_name('filter_counter_date'); ?>" value="<?php echo esc_attr($instance['filter_counter_date']); ?>" />
          </p>
          <p>
            <label for="<?php echo $this->get_field_id('lapse'); ?>"><?php _e('Only show posts published on last', 'coliris'); ?>
            <input name="<?php echo $this->get_field_name('lapse'); ?>" id="<?php echo $this->get_field_id('lapse'); ?>" size="2" type="text" value="<?php echo esc_attr($instance['lapse']); ?>" /> <?php _e('days', 'coliris'); ?></label><br />
            <small>(<?php _e('Fill with 0 to include all days', 'coliris'); ?>)</small>
          </p>
        </div>
        <div id="<?php echo $this->get_field_id('display_post_number'); ?>" style="display:none">
          <p>
            <label for="<?php echo $this->get_field_id('post_number'); ?>"><?php echo __('Max number of posts to display:','coliris'); ?>
            <input name="<?php echo $this->get_field_name('post_number'); ?>" id="<?php echo $this->get_field_id('post_number'); ?>" type="number" min="0" max="20" class="widefat" value="<?php echo esc_attr($instance['post_number']); ?>" /></label>
          </p>
        </div>
        <p>     
        <label for="<?php echo $this->get_field_id('color_distribution'); ?>"><?php echo __('Color Distribution:','coliris'); ?>
          <input name="<?php echo $this->get_field_name('color_distribution'); ?>" id="<?php echo $this->get_field_id('color_distribution'); ?>" type="number" min="30" max="90" class="widefat" value="<?php echo esc_attr($instance['color_distribution']); ?>" /></label>
        </p>
        <p>
        <label for="<?php echo $this->get_field_id('background_color'); ?>"><?php echo __('Choose background color:','coliris'); ?></label>
          <input id="<?php echo $this->get_field_id('background_color'); ?>" type="color" class="widefat" name="<?php echo $this->get_field_name('background_color'); ?>" value="<?php echo esc_attr($instance['background_color']); ?>" />
        </p>
        <p>
        <label for="<?php echo $this->get_field_id('font_color'); ?>"><?php echo __('Choose Font color:','coliris'); ?></label>
          <input id="<?php echo $this->get_field_id('font_color'); ?>" type="color" class="widefat" name="<?php echo $this->get_field_name('font_color'); ?>" value="<?php echo esc_attr($instance['font_color']); ?>" />
        </p>
        <script type="text/javascript">
        var d;
        jQuery(document).ready(function(){
          id = '#<?php echo $this->get_field_id(''); ?>';
          content_type = jQuery(id+'content_type').val();

          if (content_type == 'populars' || content_type == 'random') {
            jQuery(id+'display_post_number').show();
          };
          if (content_type == 'populars' || content_type == 'commented') {
            jQuery(id+'display_lapse').show();
          };
          if (content_type == 'pages') {
            jQuery(id+'display_check_pages').show();
          } else {
            jQuery(id+'display_check_category').show();
          };
          if (content_type != 'pages') {
            jQuery(id+'date_tag_style').show();
          } else {
            jQuery(id+'date_tag_style').hide();
          };
          if (jQuery(id+'force_header_style').attr('checked') == 'checked') {
            jQuery(id+'header_style').hide();
          } else {
            jQuery(id+'header_style').show();
          };
          if (jQuery(id+'displaydate').attr('checked') == 'checked' && content_type != 'pages') {
            jQuery(id+'date_tag').show();
          } else {
            jQuery(id+'date_tag').hide();
          };
        });
        </script>
        <?php 
    }
    
    function get_blog_pages($instance){
      $recent_pages = get_pages(
            array(
              'sort_order' => 'ASC',
              'sort_column' => 'post_title',
              'hierarchical' => 1,
              'exclude' => '',
              'include' => '',
              'meta_key' => '',
              'meta_value' => '',
              'authors' => '',
              'child_of' => 0,
              'parent' => -1,
              'exclude_tree' => '',
              'number' => $instance['post_number'],
              'offset' => 0,
              'post_type' => 'page',
              'post_status' => 'publish'
            )
          );

      $indx = 0;
      if ( get_option('permalink_structure') ) {
        foreach ($recent_pages as $post) {
          $recent_pages[$indx]->permalink = get_permalink($post->id);
          $indx++;
        }
      }
      return $recent_pages;
    }
    
    /**
     * Return the list of posts that will be displayed on widget, filtered by instance parameters
     *
     * @param array $instance Widget configuration
     *
     * @return array List of posts filtered by $instance parameters
     */
    function get_filtered_posts($instance){
      switch ($instance['content_type']) {
        case 'pages':
          return $this->get_blog_pages($instance);
          break;

        case 'recents':
          return $this->get_recent_posts($instance);
          break;

        case 'populars':
          return $this->get_most_popular_posts($instance);
          break;

        case 'commented':
          return $this->get_most_commented_posts($instance);
          break;

        case 'random':
          return $this->get_random_posts($instance);
          break;
        
        default:
          return NULL;
          break;
      }      
    }

    /**
     * Get a list of the most commented posts
     *
     * @param array $instance Widget configuration
     *
     * @return array List of posts filtered by $instance parameters
     */
    function get_most_commented_posts($instance){
      global $wpdb;
      $time_start = $instance['lapse'] > 0 ? date('Y-m-d', strtotime("-".$instance['lapse']." day", time())) : 0;
      
      $most_commented = '
      SELECT
        DATE(post_date) AS post_date,
        id,
        post_title,
        post_status,
        guid,
        comment_count AS post_comment_count,
        terms.term_id as cat_id
      FROM ' . $wpdb->prefix . 'posts post INNER JOIN ' . $wpdb->prefix . 'term_relationships rel ON post.ID = rel.object_id
         INNER JOIN ' . $wpdb->prefix . 'term_taxonomy ttax ON rel.term_taxonomy_id = ttax.term_taxonomy_id
         INNER JOIN ' . $wpdb->prefix . 'terms terms ON ttax.term_id = terms.term_id
         INNER JOIN ' . $wpdb->prefix . 'comments ON ' . $wpdb->prefix . 'comments.comment_post_ID = post.ID
      WHERE
        post.post_type = "post"
        AND post.post_status = "publish"
        AND post.post_date > "'.$time_start.'"
        AND ' . $wpdb->prefix . 'comments.comment_approved = 1
        AND ttax.taxonomy = "category"
        AND post.post_date > "'.$time_start.'"
        '.(($instance['category'] > 0) ? 'AND terms.term_id = "'.$instance['category'].'"' : NULL).'
      GROUP BY
        id
      ORDER BY
        post_comment_count DESC,
        post_date DESC
      LIMIT ' . $instance['post_number'];

      $most_commented_posts = $wpdb->get_results($most_commented);

      $indx = 0;
      if ( get_option('permalink_structure') ) {
        foreach ($most_commented_posts as $post) {
          $most_commented_posts[$indx]->permalink = get_permalink($post->id);
          $indx++;
        }
      }
      return $most_commented_posts;
    }

    /**
     * Get a list of the most popular posts
     *
     * @param array $instance Widget configuration
     *
     * @return array List of posts filtered by $instance parameters
     */
    function get_most_popular_posts($instance){
      global $wpdb,$popular_post_table;
      $popular_posts = $wpdb->get_results(
      'SELECT
        DATE(
          '.$wpdb->prefix.'posts.post_date
        ) post_date,
        '.$wpdb->prefix.'posts.id,
        '.$wpdb->prefix.'posts.post_title,
        '.$wpdb->prefix.'posts.post_status,
        '.$wpdb->prefix.'posts.guid,
        COUNT(
          '.$popular_post_table.'.post_id
        ) as count,
        (SELECT
          COUNT(
            '.$popular_post_table.'.post_id
          )
        FROM
          '.$popular_post_table.'
        WHERE '.$popular_post_table.'.post_id = '.$wpdb->prefix.'posts.ID 
        '.(($instance['filter_counter_date'] <> 0) ? 'AND DATE('.$popular_post_table.'.date) >= "'.$instance['filter_counter_date'].'"' : NULL).'       
        GROUP BY
          post_id
        LIMIT 1) AS view_count
      FROM
        '.$wpdb->prefix.'posts

      INNER JOIN '.$wpdb->prefix.'term_relationships rel ON '.$wpdb->prefix.'posts.ID = rel.object_id
      INNER JOIN '.$wpdb->prefix.'term_taxonomy ttax ON rel.term_taxonomy_id = ttax.term_taxonomy_id
      INNER JOIN '.$wpdb->prefix.'terms terms ON ttax.term_id = terms.term_id

      JOIN '.$popular_post_table.' ON '.$popular_post_table.'.post_id = '.$wpdb->prefix.'posts.id
      WHERE
        '.$wpdb->prefix.'posts.post_status = "publish"
        '.(($instance['lapse'] > 0) ? 'AND post_date > "'.date('Y-m-d', strtotime('-'.$instance['lapse']." day", time())).'"' : NULL).'
        AND taxonomy = "category"
        '.(($instance['category'] > 0) ? 'AND terms.term_id = "'.$instance['category'].'"' : NULL).'
      GROUP BY
        '.$popular_post_table.'.post_id
      ORDER BY
        view_count DESC,
        post_date DESC
      LIMIT '.$instance ['post_number']
      );

      $indx = 0;
      if ( get_option('permalink_structure') ) {
        foreach ($popular_posts as $post) {
          $popular_posts[$indx]->permalink = get_permalink($post->id);
          $indx++;
        }
      }
      return $popular_posts;
    }

    /**
     * Get Post Published recently on current blog
     *
     * @param array $instance Widget configuration
     *
     * @return array List of posts filtered by $instance parameters
     */
    function get_recent_posts($instance){
      if (isset($instance)) {

        $recent_posts = wp_get_recent_posts(
                  array(
                    'numberposts' => $instance['post_number'],
                    'offset' => 0,
                    'category' => (($instance['category'] > 0) ? $instance['category'] : NULL),
                    'orderby' => 'post_date',
                    'order' => 'DESC',
                    'include' => NULL,
                    'exclude' => NULL,
                    'meta_key' => NULL,
                    'meta_value' => NULL,
                    'post_type' => 'post',
                    'post_status' => 'publish',
                    'suppress_filters' => true )
                );
        
        $indx = 0;
        if ( get_option('permalink_structure') ) {
          foreach ($recent_posts as $post) {
            $recent_posts[$indx]['permalink'] = get_permalink($post['ID']);
            $indx++;
          }
        }
        return $recent_posts;
      }
      return NULL;
    }

    /**
     * Get Post Published recently on current blog
     *
     * @param array $instance Widget configuration
     *
     * @return array List of posts filtered by $instance parameters
     */
    function get_random_posts($instance){
      if (isset($instance)) {
        $recent_posts = wp_get_recent_posts(
                  array(
                    'numberposts' => $instance['post_number'],
                    'offset' => 0,
                    'category' => (($instance['category'] > 0) ? $instance['category'] : NULL),
                    'orderby' => 'rand',
                    'order' => 'DESC',
                    'include' => NULL,
                    'exclude' => NULL,
                    'meta_key' => NULL,
                    'meta_value' => NULL,
                    'post_type' => 'post',
                    'post_status' => 'publish',
                    'suppress_filters' => true )
                );
        
        $indx = 0;
        if ( get_option('permalink_structure') ) {
          foreach ($recent_posts as $post) {
            $recent_posts[$indx]['permalink'] = get_permalink($post['ID']);
            $indx++;
          }
        }
        return $recent_posts;
      }
      return NULL;
    }

    /**
     * Register a unique post pageview on database
     *
     * @param NULL
     */
    function register_view(){
      global $wpdb, $post, $popular_post_table, $mostviewedposts_count_once;
      if(is_single() && !is_page() && empty($mostviewedposts_count_once)){
        $wpdb->get_results("INSERT INTO " .  $popular_post_table . " (id, post_id, date) VALUES (NULL, " . $post->ID . ", '" .date("c", current_time('timestamp', 0)). "')");
        $mostviewedposts_count_once = 1;
      }
    }    

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        switch ($new_instance['content_type']) {
          case 'pages':
            $title = 'Pages';
            break;

          case 'recents':
            $title = 'Recent Posts';
            break;

          case 'populars':
            $title = 'Popular Posts';
            break;

          case 'commented':
            $title = 'Most Commented Posts';
            break;

          case 'random':
            $title = 'Random Posts';
            break;
          
          default:
            $title = 'Coliris Widget';
            break;
        }

        $instance['background_color'] = (isset($new_instance['background_color']) AND $new_instance['background_color'] <> "#000000") ? $new_instance['background_color'] : '#FF0000';
        $instance['category'] = ( ! empty( $new_instance['category'] ) OR $new_instance['content_type'] > 0) ? strip_tags( $new_instance['content_type'] ) : 0;
        $instance['content_type'] = ( ! empty( $new_instance['content_type'] ) ) ? strip_tags( $new_instance['content_type'] ) : '';
        $instance['color_distribution'] = ( ! empty( $new_instance['color_distribution'] ) ) ? strip_tags( $new_instance['color_distribution'] ) : 60;
        $instance['font_color'] = (isset($new_instance['background_color']) AND $new_instance['background_color'] <> "#000000") ? strip_tags( $new_instance['font_color'] ) : '#FFFFFF';
        $instance['next_update'] = date('U', strtotime($this->update_interval));
        $instance['post_number'] = ( ! empty( $new_instance['post_number'] ) ) ? strip_tags( $new_instance['post_number'] ) : 10;
        $instance['title'] = ( ! empty( $new_instance['title'] ) AND $new_instance['title'] <> "" ) ? strip_tags( $new_instance['title'] ) : $title;
        
        if (isset($new_instance['content_type']) AND $new_instance['content_type'] == 'pages' OR empty($new_instance['content_type'])) {
          $instance['showed_pages'] = $new_instance['showed_pages'];
          unset($instance['category']);
        } else {
          $instance['category'] = ( ! empty( $new_instance['category'] ) ) ? strip_tags( $new_instance['category'] ) : '';
          unset($instance['showed_pages']);
        }

        if (isset($new_instance['content_type']) AND $new_instance['content_type'] == 'populars') {
          $instance['filter_counter_date'] = $new_instance['filter_counter_date']; //is valid date
        }

        if (isset($new_instance['content_type']) AND ($new_instance['content_type'] == 'populars' OR $new_instance['content_type'] == 'commented') ) {
          $instance['lapse'] = $new_instance['lapse']; //is valid date period
        }

        $instance['data'] = $this->get_filtered_posts($instance);

        return $instance;
    }

    /**
     * Display configured widget on sidebar blog
     *
     * @see WP_Widget::widget
     *
     * @param array $args Configuration values for widget like name or id.
     * @param array $instances Configuration specified by user on dashboard like color, font color etcetera. 
     *
     */
    function widget($args, $instance) {   
        if(date('U',$instance['next_update']) < date('U') ){ //Post Data Needs to be Updated
            $widget_instances = get_option('widget_kbl_relevantcontent');
            $widget_instances[substr(strrchr($args['widget_id'], "-"), 1)]['data'] = $this->get_filtered_posts($instance);
            $widget_instances[substr(strrchr($args['widget_id'], "-"), 1)]['next_update'] = date('U', strtotime($this->update_interval));
            update_option('widget_kbl_relevantcontent',$widget_instances);
        }

        /*
        echo "<pre>";
        print_r($instance);
        echo "</pre>";
        */
        
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        ?>
              <?php echo $before_widget; ?>
                  <?php if ( $title )
                        echo $before_title . $title . $after_title; ?>
                  <ul id="coliris<?php echo $args['widget_id']; ?>" class="coliris skew" data-bgcolor="<?php echo $instance['background_color']; ?>" data-font-color="<?php echo $instance['font_color']; ?>" data-color-distribution="<?php echo $instance['color_distribution']; ?>">
                  <?php
                  if (isset($instance['data'])) {
                    $i = 1;
                    foreach ($instance['data'] as $entry) {
                      if ($instance['content_type'] == 'pages' OR $instance['content_type'] == 'populars' OR $instance['content_type'] == 'commented') {
                        echo '<li><div class="number">'.$i.'</div><a href="'.$entry->permalink.'"><span class="tab">'.$entry->post_title.'</span></a></li>';
                      } else {
                        echo '<li><div class="number">'.$i.'</div><a href="'.$entry['permalink'].'"><span class="tab">'.$entry['post_title'].'</span></a></li>';
                      }
                      
                      $i++;
                    }
                  }
                  ?>
                  </ul>
              <?php echo $after_widget; ?>
        <?php
    }
} // clase KBL_RelevantContent

function coloris_lang_init() {
  if ( is_admin() ) {
    $locale = apply_filters( 'plugin_locale', get_locale(), 'coliris' );
    load_textdomain( 'coliris', dirname( __FILE__ ) . "/i18n/coliris-$locale.mo" );

    wp_enqueue_script(
      'jquery.admin_widget_options',
      plugins_url( 'js/jquery.admin_widget_options.js' , __FILE__ ),
      array( 'jquery' ),
        ');.1',
        TRUE
    );
  }
}
add_action('admin_init', 'coloris_lang_init');
add_action('widgets_init', create_function('', 'return register_widget("KBL_RelevantContent");'));

function scripts_loaded() {
    wp_register_style( 'kbl_coliris_widget_style',
      plugins_url( 'css/style.css', __FILE__ ),
      array(),
      '.1',
      'screen' );
  wp_enqueue_style( 'kbl_coliris_widget_style' );

  wp_enqueue_script(
      'jquery.social-slidebar',
      plugins_url( 'js/jquery.coliris.js' , __FILE__ ),
      array( 'jquery' ),
          ');.1',
          TRUE
    );

    wp_register_style( 'kbl_coliris_widget_font', 
    'http://fonts.googleapis.com/css?family=Lato:300',
    array(),
    '1.3.4',
    'screen' 
  );
  wp_enqueue_style( 'kbl_coliris_widget_font' );
}

add_action('wp_head', array('KBL_RelevantContent', 'register_view'));
add_action( 'wp_enqueue_scripts', 'scripts_loaded' ); // wp_enqueue_scripts action hook to link only on the front-end
register_activation_hook( __FILE__, array('KBL_RelevantContent', 'coliris_activate'));
register_deactivation_hook( __FILE__, array('KBL_RelevantContent', 'coliris_deactivate'));
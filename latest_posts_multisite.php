<?php
/*
 * Plugin Name: Latest Posts MultiSite Widget
 * Version: 1.0
 * Plugin URI: http://wp4u.net/latest-posts-multisite-widget/
 * Description: Latest Posts MultiSite Widget is a wordpress plugin that enables admin to add widgets which display latest posts content from other sites in a wordpress network. The widget supports custom post types, post images, excerpts and also custom fields data. Admin can use default template or make their custom template. 
 * Author: WP4U
 * Author URI: http://wp4u.net/
 */
class WP4U_LatestPostsWidget extends WP_Widget
{
    /**
    * Declares the FacebookLikeBoxWidget class.
    *
    */
    public function WP4U_LatestPostsWidget(){
        $widget_ops = array('classname' => 'widget_WP4U_LatestPostsWidget', 'description' => __( "Latest Posts MultiSite Widget is a wp plugin that enables admin to add widgets which display latest post content from other sites in wordpress network, support custom post types .") );
        $control_ops = array('width' => 300, 'height' => 300);
        parent::WP_Widget(false, __('WP4U Latest Posts MultiSite Widget'), $widget_ops, $control_ops);
    }
    
    /**
    * Displays the Widget
    *
    */
    function widget($args, $instance){
        extract($args);
        extract($instance);
        if(is_multisite())
            switch_to_blog($blog_id);
        $output = '';
        global $wpdb;
        
        $request = $wpdb->prepare("SELECT ".$wpdb->prefix."posts.*
                   FROM ".$wpdb->prefix."posts
                   WHERE post_type='$post_type' AND post_status = 'publish' ORDER BY post_date DESC LIMIT 0,%d",(int)$limit);
        $results = $wpdb->get_results($request);
        $results = $wpdb->get_results($request);

                if(!empty($results)){
                    $output = '<ul>';

                    foreach($results as $post){

                        
                        $post_link = get_permalink($post->ID);   
                        if(!empty($post->post_excerpt))
                            $excerpt = $post->post_excerpt;
                        else
                        {
                            $excerpt =  $post->post_content;
                            $excerpt = strip_shortcodes($excerpt);
                            $excerpt = strip_tags($excerpt);
                        }
                        $excerpt = $this->truncate_string($excerpt,$excerpt_length);
                        $image = "";
                        if(has_post_thumbnail($post->ID))
                        {
                             $thumb_id = get_post_thumbnail_id($post->ID);
                             $image_data = wp_get_attachment_image_src($thumb_id,'full');
                             if(is_array($image_data))
                             {
                                  $image_src = plugins_url( 'timthumb.php' , __FILE__ )."?src=".$image_data[0]."&w=$imagew&h=$imageh&zc=1&a=t";
                                  $image = "<img src='$image_src' alt='{$post->post_title}' />";
                             }
                        }
                        $template = trim($template);
                        if(empty($template))
                        {                        
                            $output .= '<li>';
                            $output .= '<a rel="bookmark" href="'.$post_link.'"><strong class="title">'.$post->post_title.'</strong></a>';
                            $output .= '</li>';
                        }
                        else
                        {
                            $tokens = array("{title}","{link}","{image}","{excerpt}");
                            $data = array($post->post_title,$post_link,$image,$excerpt);
                            $matches = null;
                            $returnValue = preg_match_all('/%%(\\w+)%%/', $template, $matches);
                            if(is_array($matches[1]))
                            {
                                foreach($matches[1] as $customfield)
                                {
                                    $tokens[] = "%%$customfield%%";
                                    $data[] = get_post_meta($post->ID,$customfield,true);
                                }
                            }
                            
                            $output .= '<li>'.str_replace($tokens,$data,$template).'</li>';
                        }
                    }

                    $output .= '</ul>';
                }
                if(is_multisite())
                    restore_current_blog();
          echo $before_widget . $before_title . $title . $after_title;
          echo $output . $after_widget;
    }
    
    /**
    * Saves the widgets settings.
    *
    */
    function update($new_instance, $old_instance){
        $instance = $old_instance;
        $instance['title'] = strip_tags(stripslashes($new_instance['title']));
        $instance['limit'] = strip_tags(stripslashes($new_instance['limit']));
        $instance['post_type'] = strip_tags(stripslashes($new_instance['post_type'])); 
        $instance['blog_id'] = strip_tags(stripslashes($new_instance['blog_id']));
        $instance['template'] = (($new_instance['template']));
        
        $instance['imageh'] = intval(strip_tags(stripslashes($new_instance['imageh'])));
        $instance['imagew'] = intval(strip_tags(stripslashes($new_instance['imagew'])));
        
        $instance['excerpt_length'] = intval(strip_tags(stripslashes($new_instance['excerpt_length'])));
        
        return $instance;
    }
    
    /**
    * Creates the edit form for the widget.
    *
    */
    function form($instance){
        //Defaults
        $instance = wp_parse_args( (array) $instance, array('title'=>'', 'limit' => 5,'post_type' => 'post','blog_id' => 1,'template' => '','imagew' => 100,'imageh' => 100,'excerpt_length' => 20));
        extract($instance);
        $blogs = array();
        if(is_multisite())
            $blogs = get_blog_list(0,'all');
        else
            $blogs[] = array('blog_id' => 1)
        
       ?>
       <p>
        <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
        <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
       </p>
       <p>
        <label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Limit:' ); ?></label> 
        <input class="widefat" id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="text" value="<?php echo esc_attr( $limit ); ?>" />
       </p>
       <p>
        <label for="<?php echo $this->get_field_id( 'post_type' ); ?>"><?php _e( 'Post Type:' ); ?></label> 
        <input class="widefat" id="<?php echo $this->get_field_id( 'post_type' ); ?>" name="<?php echo $this->get_field_name( 'post_type' ); ?>" type="text" value="<?php echo esc_attr( $post_type ); ?>" />
       </p>
       <p>
        <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Blog:' ); ?></label> 
        <select class="widefat" id="<?php echo $this->get_field_id( 'blog_id' ); ?>" name="<?php echo $this->get_field_name( 'blog_id' ); ?>"  >
            <?php foreach($blogs as $bid=>$blog) {  
                
                if(is_multisite())
                    $blog_name = get_blog_option($blog['blog_id'],'blogname');
                else
                    $blog_name = get_option('blogname');
            ?>
                <option value="<?php echo $blog['blog_id'];?>" <?php if((int)$blog['blog_id'] == (int)$blog_id) echo 'selected="selected"'?>><?php echo $blog_name;?></option>
            <?php }
            ?>
        </select>
       </p>
       <p>
        <label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Template:' ); ?></label> 
        <textarea cols="" rows="" class="widefat" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>" > <?php echo esc_attr( $template ); ?></textarea>
       </p>
       <p>
        <label for="<?php echo $this->get_field_id( 'imagew' ); ?>"><?php _e( 'Image Width:' ); ?></label> 
        <input  id="<?php echo $this->get_field_id( 'imagew' ); ?>" name="<?php echo $this->get_field_name( 'imagew' ); ?>" type="text" value="<?php echo esc_attr( $imagew ); ?>" />
       </p>
       <p>
        <label for="<?php echo $this->get_field_id( 'imageh' ); ?>"><?php _e( 'Image Height:' ); ?></label> 
        <input  id="<?php echo $this->get_field_id( 'imageh' ); ?>" name="<?php echo $this->get_field_name( 'imageh' ); ?>" type="text" value="<?php echo esc_attr( $imageh ); ?>" />
       </p>
       <p>
        <label for="<?php echo $this->get_field_id( 'excerpt_length' ); ?>"><?php _e( 'Excerpt Length:' ); ?></label> 
        <input  id="<?php echo $this->get_field_id( 'excerpt_length' ); ?>" name="<?php echo $this->get_field_name( 'excerpt_length' ); ?>" type="text" value="<?php echo esc_attr( $excerpt_length ); ?>" />
       </p>
       <?php
    } //end of form
    
    private function truncate_string($phrase, $max_characters) 
    {
        $phrase = trim( $phrase );
        if ( strlen($phrase) > $max_characters ) {
            // Truncate $phrase to $max_characters + 1
            $phrase = substr($phrase, 0, $max_characters + 1);
            // Truncate to the last space in the truncated string.
            $phrase = trim(substr($phrase, 0, strrpos($phrase, ' ')));
            $phrase .= ' &hellip;';
        }
        return $phrase;
    }

}// END class
    
    /**
    * Register  widget.
    *
    * Calls 'widgets_init' action after widget has been registered.
    */
add_action('widgets_init', create_function('', 'return register_widget(\'WP4U_LatestPostsWidget\');'));

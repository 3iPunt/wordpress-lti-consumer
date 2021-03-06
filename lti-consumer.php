<?php
/**
 * Plugin Name: LTI-compatible consumer
 * Plugin URI: 
 * Description: An LTI-compatible launching plugin for Wordpress.
 * Version: 0.3.16
 * Author: Antoni Bertran based on code John Weaver <john.weaver@saltbox.com>
 * License: GPLv3
 */


require('OAuth.php');


/*
 * Create the lti_launch custom post type.
 */
add_action('init', 'create_lti_post_type_func');

$arrayLTIModal = array();

function lti_consumer_comment_form($post_id) 
{
    $_SESSION['arrayLTIModal'] = $arrayLTIModal;
    //1. check if there are any 
    $args = array( 'post_type' => 'lti_launch');
    $loop = new WP_Query( $args );
    //while ( $loop->have_posts() ) : $loop->the_post(); 
    $i = 0;
    while ( $i<$loop->post_count ) : 
        $curent_post = $loop->posts[$i];
        if( $curent_post->post_status =='publish' ){
            $add_in_comments_and_post = get_post_meta($curent_post->ID,'_lti_meta_add_in_comments_and_post',true);
            if ($add_in_comments_and_post) {
                $resource_link_id = get_current_blog_id().'_'.$curent_post->ID.'_'.$post_id;
                //abertranb to get the resource_link_id 
                $lti_options = get_option( 'lti_options' );
                if (isset($lti_options['lti_resource_link_id'])) {
                    $resource_link_id = $lti_options['lti_resource_link_id'];
                }
                //end
                echo lti_launch_func(array('internal_id' => $curent_post->ID, 'resource_link_id' => $resource_link_id));
            }
        }    
        $i++;
    endwhile;
    //restore original post
    wp_reset_postdata();
}

function lti_consumer_post_form($content) 
{
    $post_id = get_the_ID();
    if (get_post_type($post_id)!= 'lti_launch' ) {

        $_SESSION['arrayLTIModal'] = $arrayLTIModal;
        //1. check if there are any 
        $args = array( 'post_type' => 'lti_launch');
        $loop = new WP_Query( $args );
        //while ( $loop->have_posts() ) : $loop->the_post(); 
        $i = 0;
        while ( $i<$loop->post_count ) : 
            $curent_post = $loop->posts[$i];
            if( $curent_post->post_status =='publish' ){
                $add_in_comments_and_post = get_post_meta($curent_post->ID,'_lti_meta_add_in_comments_and_post',true);
                if ($add_in_comments_and_post) {
                    $resource_link_id = get_current_blog_id().'_'.$curent_post->ID.'_'.$post_id;
                    //abertranb to get the resource_link_id 
                    $lti_options = get_option( 'lti_options' );
                    if (isset($lti_options['lti_resource_link_id'])) {
                        $resource_link_id = $lti_options['lti_resource_link_id'];
                    }
                    //end
                    $content.= lti_launch_func(array('internal_id' => $curent_post->ID, 'resource_link_id' => $resource_link_id));
                }
            }    
            $i++;
           
        endwhile;
        //restore original post
        wp_reset_postdata();
    }
    return $content;
}

function create_lti_post_type_func() {
    if ( is_user_logged_in() ) {
        wp_enqueue_style( 'lti_consumer_css', plugins_url('css/lti-consumer.css', __FILE__) );
            

        add_filter("media_buttons_context", "lti_consumer_post_form");
        add_action('comment_form', 'lti_consumer_comment_form');
        add_action('wp_footer','show_forms_lti');
        add_action('admin_footer','show_forms_lti');
        add_action('wp_head', 'lti_launch_ajaxurl');
        add_action('admin_head', 'lti_launch_ajaxurl');

    }

    register_post_type(
        'lti_launch',
        array(
            'labels' => array(
                'name' => __('LTI content'),
                'singular_name' => __('LTI content'),
                'add_new_item' => __('Add new LTI content'),
                'edit_item' => __('Edit LTI content'),
                'new_item' => __('New LTI content'),
                'view_item' => __('View LTI content'),
                'search_items' => __('Search LTI content'),
                'not_found' => __('No LTI content found'),
                'not_found_in_trash' => __('No LTI content found in Trash'),
            ),
            'description' => __('An LTI-compatible tool for content launch'),
            'publicly_queryable' => true,
            'public' => true, //hide from menu
            'exclude_from_search' => true,
            'has_archive' => true,
            'show_ui' => true, //hide from menu
            'supports' => array(
                'title',
                'editor',
            ),
        )
    );
}

add_filter('post_row_actions', 'add_shortcode_generator_link', 10, 2);
function add_shortcode_generator_link($actions, $post) {
    if ( $post->post_type == 'lti_launch' ) {
        unset($actions['view']);
        $actions['shortcode_generator'] = 'Shortcode: [lti-launch id=' . $post->post_name . ']';
    }

    return $actions;
}



add_action('add_meta_boxes', 'lti_content_meta_box');
function lti_content_meta_box() {
    add_meta_box(
        'lti_content_custom_section_id',
        __('LTI launch settings', 'lti-consumer'),
        'lti_content_inner_custom_box',
        'lti_launch'
    );
}


add_filter('get_sample_permalink_html', 'permalink_removal', 1000, 4);
function permalink_removal($return, $id, $new_title, $new_slug) {
    global $post;
    if ( $post && $post->post_type == 'lti_launch' ) {
        return '';
    } else {
        return $return;
    }
}


function lti_content_inner_custom_box($lti_content) {
    wp_nonce_field('lti_content_inner_custom_box', 'lti_content_inner_custom_nonce');

    $consumer_key = get_post_meta($lti_content->ID, '_lti_meta_consumer_key', true);
    $secret_key = get_post_meta($lti_content->ID, '_lti_meta_secret_key', true);
    $display = get_post_meta($lti_content->ID, '_lti_meta_display', true);
    $height_modal = get_post_meta($lti_content->ID,'_lti_meta_height_modal',true);
    $action = get_post_meta($lti_content->ID, '_lti_meta_action', true);
    $launch_url = get_post_meta($lti_content->ID, '_lti_meta_launch_url', true);
    $configuration_url = get_post_meta($lti_content->ID, '_lti_meta_configuration_url', true);
    $add_in_comments_and_post = get_post_meta($lti_content->ID,'_lti_meta_add_in_comments_and_post',true);
    $return_url = get_post_meta($lti_content->ID, '_lti_meta_return_url', true);
    $version= get_post_meta($lti_content->ID, '_lti_meta_version', true);

?>
    <p>All of the following fields are optional, and can be overridden by specifying the corresponding parameters to the lti-launch shortcode.</p>


<table class="form-table">
  <tbody>
    <tr>
      <th><label for="lti_content_field_"><?php echo _e( "OAuth Consumer Key", 'lti-consumer' ); ?></label></th>
      <td><input type="text" id="lti_content_field_consumer_key" name="lti_content_field_consumer_key" value="<?php echo esc_attr( $consumer_key ); ?>" size="25" /></td>
    </tr>

    <tr>
      <th><label for="lti_content_field_"><?php echo _e( "OAuth Secret Key", 'lti-consumer' ); ?></label></th>
      <td><input type="text" id="lti_content_field_secret_key" name="lti_content_field_secret_key" value="<?php echo esc_attr( $secret_key ); ?>" size="25" /></td>
    </tr>

    </tr>

    <tr>
      <th><label for="lti_content_field_display_newwindow"><?php echo _e( "Display Style", 'lti-consumer' ); ?></label></th>
      <td>
        <label>Open in a new browser window <input type="radio" <?php checked($display, 'newwindow'); ?> id="lti_content_field_display_newwindow" name="lti_content_field_display" value="newwindow" /></label><br>
        <label>Inline in an iframe <input type="radio" <?php checked($display, 'iframe'); ?> id="lti_content_field_display_iframe" name="lti_content_field_display" value="iframe" /></label><br>
        <label>Open in the current browser window <input type="radio" <?php checked($display, 'self'); ?> id="lti_content_field_display_self" name="lti_content_field_display" value="self" /></label><br>
        <label>Open in modal <input type="radio" <?php checked($display, 'modal'); ?> id="lti_content_field_display_modal" name="lti_content_field_display" value="modal" /></label><br>
        <label>Open in Bootstrap modal <input type="radio" <?php checked($display, 'modal_bootstrap'); ?> id="lti_content_field_display_modal_bootstrap" name="lti_content_field_display" value="modal_bootstrap" /></label>
        <div id="show-me-textbox">Modal height: <input type="text" id="lti_content_field_height_modal" name="lti_content_field_height_modal" size="7" value="<?php echo esc_attr($height_modal);?>"> em</div><br>
        <script>

             jQuery(document).ready(function(){
                jQuery('#post').change(function(){
                    showModalHeight();
                });
                showModalHeight();
             });
             function showModalHeight(){
                 if (jQuery('#lti_content_field_display_modal').prop('checked') || jQuery('#lti_content_field_display_modal_bootstrap').prop('checked')) {
                        jQuery('#show-me-textbox').show();
                    } else {
                        jQuery('#show-me-textbox').hide();
                    }
             }

        </script>
      </td>
    </tr>

    <tr>
      <th><label for="lti_content_field_action_button"><?php _e( "Launch trigger control", 'lti-consumer' ); ?></label></th>
      <td>
        <label>Button <input type="radio" <?php checked($action, 'button'); ?> id="lti_content_field_action_button" name="lti_content_field_action" value="button" /></label><br>
        <label>Link <input type="radio" <?php checked($action, 'link'); ?> id="lti_content_field_action_link" name="lti_content_field_action" value="link"  /></label>
      </td>
    </tr>

    <!--tr>
      <th><label for="lti_add_in_comments_and_post"><?php _e( "Add in comments and post", 'lti-consumer' ); ?></label></th>
      <td>
        <select name="lti_add_in_comments_and_post" id="lti_add_in_comments_and_post">
            <option <?php echo ($add_in_comments_and_post)?'selected':''; ?> value="0" ><?php echo _e( "No", 'lti-consumer' ); ?></option>
            <option <?php echo ($add_in_comments_and_post)?'selected':''; ?> value="1" ><?php echo _e( "Yes", 'lti-consumer' ); ?></option>
        </select>
      </td>
    </tr-->

    <tr>
      <th><label for="lti_content_field_launch_url"><?php echo _e( "Launch URL", 'lti-consumer' ); ?></label></th>
      <td><input type="url" id="lti_content_field_launch_url" name="lti_content_field_launch_url" value="<?php echo esc_attr( $launch_url ); ?>" size="35" /></td>
    </tr>

    <tr>
      <th><label for="lti_content_field_configuration_url"><?php echo _e( "Configuration XML URL", 'lti-consumer' ); ?></label></th>
      <td><input type="url" id="lti_content_field_configuration_url" name="lti_content_field_configuration_url" value="<?php echo esc_attr( $configuration_url ) ?>" size="35" /></td>
    </tr>

    <tr>
      <th><label for="lti_content_field_return_url"><?php echo _e( "Return URL after completion", 'lti-consumer' ); ?></label></th>
      <td><input type="url" id="lti_content_field_return_url" name="lti_content_field_return_url" value="<?php echo esc_attr( $return_url ); ?>" size="35" /></td>
    </tr>

    <!--tr>
      <th><label for="lti_content_field_version_1_1"><?php _e( "LTI version", 'lti-consumer' ); ?></label></th>
      <td>
        <label>1.1 <input type="radio" <?php checked($version, 'LTI-1p1'); ?> id="lti_content_field_version_1_1" name="lti_content_field_version" value="LTI-1p1" /></label><br>
        <label>1.0 <input type="radio" <?php checked($version, 'LTI-1p0'); ?> id="lti_content_field_version_1_0" name="lti_content_field_version" value="LTI-1p0"  /></label>
      </td>
    </tr-->
  </tbody>
</table>

<?php
}


add_filter('the_content', 'lti_content_include_launcher');
function lti_content_include_launcher($content) {
    global $post;

    if ( $post->post_type == 'lti_launch' ) {
        $content .= '<p>[lti-launch id=' . $post->post_name . ' resource_link_id=' . $post->ID . ']</p>';
    }

    return $content;
}


add_action('save_post', 'lti_content_save_post');
function lti_content_save_post($post_id) {
    // From http://codex.wordpress.org/Function_Reference/add_meta_box
    // Check if our nonce is set.
    if ( ! isset( $_POST['lti_content_inner_custom_nonce'] ) ) {
        return $post_id;
    }
    
    $nonce = $_POST['lti_content_inner_custom_nonce'];
    
    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $nonce, 'lti_content_inner_custom_box' ) ) {
          return $post_id;
    }
    
    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return $post_id;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return $post_id;
    }

    /* OK, its safe for us to save the data now. */

    // Sanitize user input.
    $consumer_key = sanitize_text_field($_POST['lti_content_field_consumer_key']);
    $secret_key = sanitize_text_field($_POST['lti_content_field_secret_key']);
    $display = sanitize_text_field($_POST['lti_content_field_display']);
    $height_modal = sanitize_text_field($_POST['lti_content_field_height_modal']);
    $action = sanitize_text_field($_POST['lti_content_field_action']);
    $launch_url = sanitize_text_field($_POST['lti_content_field_launch_url']);
    $configuration_url = sanitize_text_field($_POST['lti_content_field_configuration_url']);
    $lti_add_in_comments_and_post = sanitize_text_field($_POST['lti_add_in_comments_and_post']);
    
    $return_url = sanitize_text_field($_POST['lti_content_field_return_url']);
    $version = 'LTI-1p0';//sanitize_text_field($_POST['lti_content_field_version']);

    // Update the meta field in the database.
    update_post_meta($post_id, '_lti_meta_consumer_key', $consumer_key);
    update_post_meta($post_id, '_lti_meta_secret_key', $secret_key);
    update_post_meta($post_id, '_lti_meta_display', $display);
    update_post_meta($post_id, '_lti_meta_height_modal',$height_modal);
    update_post_meta($post_id, '_lti_meta_action', $action);
    update_post_meta($post_id, '_lti_meta_launch_url', $launch_url);
    update_post_meta($post_id, '_lti_meta_configuration_url', $configuration_url);
    update_post_meta($post_id, '_lti_meta_add_in_comments_and_post', $lti_add_in_comments_and_post);
    update_post_meta($post_id, '_lti_meta_return_url', $return_url);
    update_post_meta($post_id, '_lti_meta_version', $version);
}



/*
 * Add the lti-launch shortcode.
 */
add_shortcode('lti-launch', 'lti_launch_func');
function lti_launch_func($attrs) {
    $data = lti_launch_process($attrs);

    if ( array_key_exists('error', $data) ) {
        $html = '<div class="error"><p><strong>' . $data['error'] . '</strong></p></div>';
    } else {
        $html = '';
        $id = uniqid();
        $iframeId = uniqid();
       
        
        if ( $data['display'] == 'newwindow' ) {
            $target = '_blank';
        } else if ( $data['display'] == 'iframe' ) {
            $target = 'frame-' . $iframeId;
        }else {
            $target = '_self';
        }

        if ( $data['action'] == 'auto' || $data['display'] == 'iframe' ) {
            $autolaunch = 'yes';
        } else {
            $autolaunch = 'no';
        }

        if ( $data['display'] == 'iframe' ) {
            $html .= '<iframe style="width: 100%; height: 55em;" class="launch-frame" name="frame-' . $iframeId . '"></iframe>';
            // Immediately send the lti_launch action when showing the iframe.
            if ( $data['id'] ) {
                do_action('lti_launch', $data['id']);
            }

        }else if ( $data['display'] == 'modal' ) {

            if ($data['is_in_comments']) { //then add an space 
                $html .= '&nbsp;';
            }
            wp_enqueue_style( 'jquery_modal_css', plugins_url('css/jquery_modal.css', __FILE__) );
            wp_enqueue_script('lti_launch_modal_jquery', plugins_url('scripts/jquery.simplemodal.1.4.4.min.js', __FILE__), array('jquery'));
            wp_enqueue_script('lti_launch_modal_jquery'); 
            if ( $data['action'] == 'link' ) {
                 $html .= '<a href="#"  class="button-lti-consumer" id="button-modal-'.$id.'">' . $data['text'] . '</a>';
            } else {
                $html .= '<input type="button" class="button-lti-consumer" id="button-modal-'.$id.'" value="' . $data['text'] . '">';
            }

            $arrayLTIModal = $_SESSION['arrayLTIModal'];
            if (!$arrayLTIModal) {
                $arrayLTIModal = array();
            }
            $arrayLTIModal[$id] = '
            <div id="modal-content-'.$id.'" class="jquery-modal">
                <form  method="post" action="'.$data['url'].'" target="iframe-' . $iframeId . '" id="launch-modal-'.$id.'" data-id="'.$id.'" data-post="'.$data['id'].'">
                </form> 
                <iframe style="width: 100%; height: '.$data["heightModal"].'em'.';" class="launch-frame" name="iframe-' . $iframeId . '" id="iframe-modal-'.$id.'"></iframe>
            </div>
            <div style="display:none">
                <img src="'.plugins_url('img/x.png', __FILE__).'"/>
            </div>
            <script>
                var modalType'.$id.' = "jquery";
            jQuery(document).ready(function(){
                jQuery("#button-modal-'.$id.'").click(function( event ) {
                    event.preventDefault();
                    jQuery("#modal-content-'.$id.'").modal(
                        {
                            escClose: true,
                            opacity: 80,
                            minHeight:jQuery( document ).height()<400?(jQuery( document ).height()*0.80):400,
                            minWidth: jQuery( document ).width()<700?(jQuery( document ).width()*0.80):600,
                            onShow: function (dialog) {
                                lti_consumer_launch(\'' . $id . '\',\'' . $attrs['id'] . '\',\'' . $attrs['resource_link_id'] . '\' , true, '.($data['is_in_comments']?'true':'false').', '.$data['id'].');
                            },
                            onClose: function (dialog) {

                                jQuery("#iframe-modal-'.$id.'").attr("src","about:blank");
                                jQuery.modal.close();
                            }
                        });
                });
             });
            </script>';

        }else if ( $data['display'] == 'modal_bootstrap' ) {

            wp_enqueue_style( 'bootstrap', 'http://getbootstrap.com/dist/css/bootstrap.min.css' );

            if ($data['is_in_comments']) { //then add an space 
                $html .= '&nbsp;';
            }
            wp_register_script( 'bootstrap', 'http://getbootstrap.com/dist/js/bootstrap.min.js', array('jquery'), 3.3, true); 
            wp_enqueue_script('bootstrap'); 
            if ( $data['action'] == 'link' ) {
                 $html .= '<a href="#"  data-toggle="modal" data-target="#modal'.$id.'">' . $data['text'] . '</a>';
            } else {
                $html .= '<button class="btn btn-primary" class="button-lti-consumer"  id="button-modal-'.$id.'" data-toggle="modal" data-target="#modal'.$id.'">
    ' . $data['text'] . '</button>';
            }

            $arrayLTIModal = $_SESSION['arrayLTIModal'];
            if (!$arrayLTIModal) {
                $arrayLTIModal = array();
            }
            $arrayLTIModal[$id] = '
            <div style="display: none;" class="modal fade bs-example-modal-lg" id="modal'.$id.'"  tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
                      <h4 class="modal-title" id="myLargeModalLabel'.$id.'">'.$data['text'].'</h4>
                    </div>
                    <div class="modal-body" id="modal-body-'.$id.'">
                       <form  method="post" action="'.$data['url'].'" target="iframe-' . $iframeId . '" id="launch-modal-'.$id.'" data-id="'.$id.'" data-post="'.$data['id'].'">
                       </form> 
                       <iframe style="width: 100%; height: '.$data["heightModal"].'em'.';" class="launch-frame" name="iframe-' . $iframeId . '" id="iframe-modal-'.$id.'"></iframe>
                       
                    </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <!--button type="button" class="btn btn-primary">Save changes</button-->
                      </div>
                  </div><!-- /.modal-content -->
                </div><!-- /.modal-dialog -->
            </div>
            <script>
            jQuery(document).ready(function(){
                var modalType'.$id.' = "bootstrap";
                jQuery("#button-modal-'.$id.'").click(function( event ) {
                    event.preventDefault();
                });
                jQuery( "#modal'.$id.'" ).on("shown.bs.modal", function(){
                    lti_consumer_launch(\'' . $id . '\',\'' . $attrs['id'] . '\',\'' . $attrs['resource_link_id'] . '\' , true, '.($data['is_in_comments']?'true':'false').', '.$data['id'].');
                });
                jQuery( "#modal'.$id.'" ).on("hidden.bs.modal", function(){
                    jQuery("#iframe-modal-'.$id.'").attr("src","about:blank");
                });

        
             });
            </script>';
               
        }else if ( $data['action'] == 'link' ) {
            $html .= '<a href="#" onclick="lti_consumer_launch(\'' . $id . '\',\'' . $attrs['id'] . '\',\'' . $attrs['resource_link_id'] . '\' , false, '.($data['is_in_comments']?'true':'false').', '.$data['id'].')">Launch ' . $data['text'] . '</a>';
        } else {
            $html .= '<button onclick="lti_consumer_launch(\'' . $id . '\',\'' . $attrs['id'] . '\',\'' . $attrs['resource_link_id'] . '\', false, '.($data['is_in_comments']?'true':'false').', '.$data['id'].')">Launch ' . $data['text'] . '</button>';
        }
        if ($data['display']=='modal' || $data['display']=='modal_bootstrap') {
            $arrayLTIModal[$id] .= '<form method="post" action="'.$data['url'].'" target="'.$target.'" id="launch-'.$id.'" data-id="'.$id.'" data-post="'.$data['id'].'" data-auto-launch="'.$autolaunch.'">';
            foreach ( $data['parameters'] as $key => $value ) {
                $arrayLTIModal[$id] .= '<input type="hidden" name="'.$key.'" value="'.$value.'">';
            }

            $arrayLTIModal[$id] .= '</form>';
            $_SESSION['arrayLTIModal'] = $arrayLTIModal;
        }
        else {
            $html .= '<form method="post" action="'.$data['url'].'" target="'.$target.'" id="launch-'.$id.'" data-id="'.$id.'" data-post="'.$data['id'].'" data-auto-launch="'.$autolaunch.'">';
            foreach ( $data['parameters'] as $key => $value ) {
                $html .= '<input type="hidden" name="'.$key.'" value="'.$value.'">';
            }

            $html .= '</form>';

        }
    }

    return $html;
}


function show_forms_lti() {
    if (isset($_SESSION['arrayLTIModal'])) {
        $arrayLTIModal = $_SESSION['arrayLTIModal'];
        foreach ($arrayLTIModal as $id => $html) {
            echo $html;
        }
        unset($_SESSION['arrayLTIModal']);
    }
}

function lti_launch_ajaxurl() {
?>
<script type="text/javascript">
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
</script>
<?php
}


/*
 * Emit an 'lti_launch' action when the Javascript informs us about a
 * launch.
 */
add_action('wp_ajax_lti_launch', 'hook_lti_launch_action_func');
add_action('wp_ajax_nopriv_lti_launch', 'hook_lti_launch_action_func');
function hook_lti_launch_action_func() {
    $lti_launch = get_post($_POST['post']);
    // make sure that at least the post id is valid
    if ( $lti_launch && $lti_launch->post_type == 'lti_launch' ) {
        do_action('lti_launch', $_POST['post']);
    }
}


/*
 * Find lti-launch shortcodes in posts and add a resource_link_id to any found
 * if they don't already have one set.
 */
add_action('save_post', 'ensure_resource_link_id_func');
function ensure_resource_link_id_func($post_id) {
    // get post content
    $content = get_post($post_id)->post_content;

    // does it contains our shortcode
    $pattern = get_shortcode_regex();
    preg_match_all("/$pattern/s", $content, $matches);

    foreach ( $matches[0] as $match ) {
        if ( strpos($match, '[lti-launch') === 0 ) {
            // Replace the original shortcode with the rewritten one
            $content = substr_replace(
                $content,
                add_resource_link_id_if_not_present($match),
                strpos($content, $match),
                strlen($match));
        }
    }

    // transform content
    
    // unhook this function so it doesn't loop infinitely
    remove_action('save_post', 'ensure_resource_link_id_func');

    // update the post, which calls save_post again
    wp_update_post(array('ID' => $post_id, 'post_content' => $content));

    // re-hook this function
    add_action('save_post', 'ensure_resource_link_id_func');
}


/*
 * Insert our LTI launch script into the page.
 */
add_action('wp_enqueue_scripts', 'add_launch_script_func');
add_action('admin_enqueue_scripts', 'add_launch_script_func');
function add_launch_script_func() {
    wp_enqueue_script('lti_launch', plugins_url('scripts/launch.js', __FILE__), array('jquery'));
}


function add_resource_link_id_if_not_present($shortcode) {
    // split args out of shortcode, excluding the [] as well
    $pieces = explode(' ', substr($shortcode, 1, -1));

    // check if resource_link_id is present
    $found = false;
    foreach ( $pieces as $piece ) {
        if ( strpos(trim($piece), 'resource_link_id=') === 0 ) {
            $found = true;
            break;
        }
    }

    // add resource_link_id if not present
    if ( !$found ) {
        array_push($pieces, 'resource_link_id=' . uniqid());
    }

    // recombine args
    return '[' . implode(' ', $pieces) . ']';
}

/**
 * [get_current_lti_role description]
 * @author Antoni Bertran <antoni@tresipunt.com>
 * @param  [type] $userRole [description]
 * @return [type]           [description]
 */
function get_current_lti_role($userRole) {
    $role = key($userRole);
    
    switch($role) {
        case 'administrator':
            $role = 'administrator';
        break;
        case ('editor'||'author'): 
            $role = 'instructor';
        break;
        case 'contributor':
            $role = 'learner';
        break;

        default:
            $role = 'guest';
        break;
    }
    return $role;
}

/*
 * Utilities
 */
function extract_user_id() {
    if ( is_user_logged_in() ) {
        // Find some relevant information about the current user
        $current_user_obj =  get_userdata(wp_get_current_user()->ID);
        $userRole = ($current_user_obj->roles);
        $current_user = $current_user_obj->data;
        //$lpl = get_bloginfo('language');
        //echo $lpl.'<br>';
        if (!isset($current_user->user_firstname)) {
            $current_user->user_firstname = get_user_meta(wp_get_current_user()->ID, 'first_name', true);
        }
        if (!isset($current_user->user_lastname)) {
                $current_user->user_lastname = get_user_meta(wp_get_current_user()->ID, 'last_name', true);
        }
        return array(
            'user_id' => $current_user->ID,
            'custom_username' => $current_user->user_login,
            'roles'  => get_current_lti_role($userRole),

            'launch_presentation_locale'=>get_bloginfo('language'),
            //Language, country and variant as represented using the IETF Best Practices for Tags for Identifying Languages (BCP-47) available at http://www.rfc-editor.org/rfc/bcp/bcp47.txt

            //launch_presentation_document_target=iframe
            //The value should be either ‘frame’, ‘iframe’ or ‘window’.  This field communicates the kind of browser window/frame where the TC has launched the tool.  The TP can ignore this parameter and detect its environment through JavaScript, but this parameter gives the TP the information without requiring the use of JavaScript if the tool prefers. This parameter is recommended.
            'lis_person_contact_email_primary' => $current_user->user_email,
            'lis_person_name_given' => isset($current_user->user_firstname)?$current_user->user_firstname:$current_user->display_name,
            'lis_person_name_family' => isset($current_user->user_lastname)?$current_user->user_lastname:$current_user->display_name,
        );
    } else {
        return array();
    }
}

function extract_site_id() {
    // Find some relevant information about the site
    $lti_options = get_option( 'lti_options' );
    $context_id = get_current_blog_id().(get_the_ID()!=null?get_the_ID():'');
    //abertranb to get the context_id 
    if (isset($lti_options['lti_context_id'])) {
        $context_id = $lti_options['lti_context_id'];
    }
    //end
    
    return array(
        'context_id' =>  $context_id,
        'context_name' => get_bloginfo('name'),//basename(get_permalink()),
        'context_label' => get_bloginfo('name'),//basename(get_permalink()),
        'tool_consumer_instance_url' => get_site_url(),
    );
}

/**
 * Function to set extra context information
 * @return [type] [description]
 */
function extract_site_contenxt_info($id) {
    $total_fields = 2;
    $lti_options = get_option( 'lti_options' );
    $array = array(
        'custom_metadata_fields'  =>  2,
        'custom_metadata_label_0' =>  'blog_id',
        'custom_metadata_value_0' =>  get_current_blog_id(),
        'custom_metadata_label_1' =>  'post_id',
        'custom_metadata_value_1' =>  $id
    );
    if (isset($lti_options['lti_blogType'])) {
        $array['custom_metadata_label_'.$array['custom_metadata_fields']] = 'blogType';
        $array['custom_metadata_value_'.$array['custom_metadata_fields']] = $lti_options['lti_blogType'];
        $array['custom_metadata_fields'] = $array['custom_metadata_fields']+1;
    }

    return $array;
}


function determine_launch_url($configuration_url) {
    $launch_url = wp_cache_get($configuration_url, 'lti-consumer', false, $found);
    if ( $found ) {
        return $launch_url;
    }

    $parts = parse_url($configuration_url);

    if ( $parts == false || !array_key_exists('scheme', $parts) || ($parts['scheme'] != 'http' && $parts['scheme'] != 'https') ) {
        // Don't trust weird URLs (could be file path or something).
        $launch_url = false;
    } else {
        try {
            $opts = array(
                'http' => array(
                    'header' => "Accept: application/xml\r\n"
                )
            );

            $context = stream_context_create($opts);
            $config_string = file_get_contents($configuration_url, false, $context);

            $config = simplexml_load_string($config_string);
            $launch_url = (string) $config->children('blti', true)->launch_url;
        } catch ( Exception $e ) {
            $launch_url = false;
        }
    }

    // Keep it for 30 minutes
    wp_cache_set($configuration_url, $launch_url, 'lti-consumer', 30 * 60);
    return $launch_url;
}


function lti_launch_process($attrs) {
    // Reject launch for non-logged in users
    if ( !is_user_logged_in() ) {
        return array('error' => 'You must be logged in to launch this content.');
    } else {
        $parameters = array();
        // grab user information
        $parameters = array_merge($parameters, extract_user_id());
        // grab site information
        $parameters = array_merge($parameters, extract_site_id());
        // grab extra site context information
        $parameters = array_merge($parameters, extract_site_contenxt_info(array_key_exists('internal_id', $attrs)?$attrs['internal_id']:$attrs['id']));

        $post_id = '';
        $text = '';

        $posts = false;
        $is_in_comments = false;

        if ( array_key_exists('id', $attrs) ) {
            $posts = get_posts(array(
                'name' => $attrs['id'],
                'post_type' => 'lti_launch',
                'post_status' => 'publish',
                'posts_per_page' => 1,
            ));
        } elseif ( array_key_exists('internal_id', $attrs) ) {
            $posts = get_post($attrs['internal_id']);
            $is_in_comments = true;
        }

        if ( $posts ) {
            $lti_content = is_array($posts)?$posts[0]:$posts;
            $post_id = $lti_content->ID;
            $consumer_key = get_post_meta($lti_content->ID, '_lti_meta_consumer_key', true);
            $consumer_secret = get_post_meta($lti_content->ID, '_lti_meta_secret_key', true);
            $display = get_post_meta($lti_content->ID, '_lti_meta_display', true);
            $height_modal = get_post_meta($lti_content->ID, '_lti_meta_height_modal', true);
            $action = get_post_meta($lti_content->ID, '_lti_meta_action', true);
            $launch_url = get_post_meta($lti_content->ID, '_lti_meta_launch_url', true);
            $configuration_url = get_post_meta($lti_content->ID, '_lti_meta_configuration_url', true);
            $add_in_comments_and_post = get_post_meta($lti_content->ID, '_lti_meta_add_in_comments_and_post', true);
            $return_url = get_post_meta($lti_content->ID, '_lti_meta_return_url', true);
            $text = $lti_content->post_title;
            $version = get_post_meta($lti_content->ID, '_lti_meta_version', true) or 'LTI-1p0';
        } else {
            return array('error' => 'Lti tool not found.');
        }
        

        // incorporate information from $attrs
        if ( array_key_exists('resource_link_id', $attrs) ) {
            $parameters['resource_link_id'] = $attrs['resource_link_id'];
        } else {
            return array('error' => 'You must specify the resource_link_id.');
        }

        if ( array_key_exists('return_url', $attrs) ) {
            $parameters['launch_presentation_return_url'] = $attrs['return_url'];
        } else if ( isset($return_url) && $return_url ) {
            $parameters['launch_presentation_return_url'] = $return_url;
        }

        if ( array_key_exists('version', $attrs) ) {
            $version = $attrs['version'];
        } else if ( !isset($version) ) {
            $version = 'LTI-1p0';
        }

        if ( array_key_exists('configuration_url', $attrs) ) {
            $launch_url = determine_launch_url($attrs['configuration_url']);

            if ( $launch_url == false ) {
                return array('error' => 'Could not determine launch URL.');
            }
        } else if ( array_key_exists('launch_url', $attrs) ) {
            $launch_url = $attrs['launch_url'];
        } else if ( isset($configuration_url) && $configuration_url ) {
            $launch_url = determine_launch_url($configuration_url);

            if ( $launch_url == false ) {
                return array('error' => 'Could not determine launch URL.');
            }
        } else if ( !isset($launch_url) ) {
            return array('error' => 'Missing launch URL and URL to configuration XML. One of these is required.');
        }

        if ( array_key_exists('consumer_key', $attrs) ) {
            $consumer_key = $attrs['consumer_key'];
        } else if ( !isset($consumer_key) ) {
            return array('error' => 'Missing OAuth consumer key.');
        }

        if ( array_key_exists('secret_key', $attrs) ) {
            $consumer_secret = $attrs['secret_key'];
        } else if ( !isset($consumer_secret) ) {
            return array('error' => 'Missing OAuth consumer secret.');
        }

        if ( !isset($display) ) {
            $display = 'newwindow';
        }

        if ( array_key_exists('display', $attrs) ) {
            $display = $attrs['display'];
        }

        if ( array_key_exists('action', $attrs) ) {
            $action = $attrs['action'];
        } else if ( !isset($action) )  {
            $action = 'button';
        }

        return array(
            'parameters' => package_launch(
                $version,
                $consumer_key, $consumer_secret,
                $launch_url,
                $parameters),
            'id' => $post_id,
            'display' => $display,
            'heightModal'=>$height_modal,
            'action' => $action,
            'url' => $launch_url,
            'text' => $text,
            'is_in_comments' => $is_in_comments
        );
    }
}


function package_launch($version, $key, $secret, $launch_url, $parameters) {
    $parameters['lti_version'] = $version;
    $parameters['lti_message_type'] = 'basic-lti-launch-request';

    $consumer = new OAuthConsumer($key, $secret);
    $oauth_request = OAuthRequest::from_consumer_and_token(
        $consumer, null, 'POST',
        $launch_url, $parameters);
    $oauth_request->sign_request(
        new OAuthSignatureMethod_HMAC_SHA1(), $consumer, null);
    return $oauth_request->get_parameters();
};

add_action( 'wp_ajax_lti_launch', 'ajax_lti_launch' );
function ajax_lti_launch(){
    $attrs = array('resource_link_id' =>  $_POST['resource_link_id'] );
    if ($_POST['is_in_comments']=='true' && isset($_POST['internal_id'])) {
        $attrs['internal_id']  =  $_POST['internal_id'];
    } else {
        $attrs['id']  =  $_POST['id'];
    }
    
    die(json_encode(lti_launch_process($attrs)));

}
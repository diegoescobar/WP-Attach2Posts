<?php
/*
    Plugin Name: Attachments2Posts
	Plugin URI: http://stage.diegoescobar.ca/plugins/Attach2Posts
	Description: Creates posts and galleries based on similarly named photos, typically by timestamp, & reorders upload folders
	Author: Diego Esscobar
	Author URI: https://diegoescobar.ca/
	License: GPL v2 or later
    */

include ("utils.php");
include ("post-builder.php");

$is_test = true;
// $is_test = false;

// add categories for attachments
function add_categories_for_attachments() {
    register_taxonomy_for_object_type( 'category', 'attachment' );
}
// add_action( 'init' , 'add_categories_for_attachments' );

// add tags for attachments
function add_tags_for_attachments() {
    register_taxonomy_for_object_type( 'tag', 'attachment' );
}
// add_action( 'init' , 'add_tags_for_attachments' );

add_action( 'admin_menu', 'attach2post__add_admin_menu' );
add_action( 'admin_init', 'attach2post__settings_init' );


// fuckupthese_othersizes();

function attach2post__add_admin_menu(  ) { 

	add_menu_page( 'Attach2Posts', 'Attach2Posts', 'manage_options', 'Attach2Posts', 'attach2post__options_page' );

}


function attach2post__settings_init(  ) { 

	register_setting( 'attach2postPluginPage', 'attach2post__settings' );

	add_settings_section(
		'attach2post__attach2postPluginPage_section', 
		__( 'Your section description', 'code_' ), 
		'attach2post__settings_section_callback', 
		'attach2postPluginPage'
	);

	add_settings_field( 
		'attach2post__select_field_0', 
		__( 'Settings field description', 'code_' ), 
		'attach2post__select_field_0_render', 
		'attach2postPluginPage', 
		'attach2post__attach2postPluginPage_section' 
	);
}


function attach2post__select_field_0_render(  ) { 
    global $wpdb;
    $options = get_option( 'attach2post__settings' );
    
    if ($_REQUEST['page'] == 'Attach2Posts' && isset($_POST['submit']) ){
        // var_dump( $_POST );
        attach2post__attach_media_to_post( $_POST );
    } else {
        echo "<p><u>Preview</u></p>";
        var_dump( $_REQUEST );
        attach2post__preview_posts();
    }

}


function attach2post__preview_posts(){
    global $wpdb;

    $attachment_dates = attach2post__get_date_attachments();
    $name_arr = array();

    foreach ( $attachment_dates AS $date){

        $parsed_title = attach2post__post_title_nonsense( $date->post_name );
        $name_arr[] = $parsed_title;
    }
    foreach ( array_unique( $name_arr ) AS $data){
        echo $data . '<br/>';
    }
}



function attach2post__form_input_render() {
    global $wpdb;
    // $options = get_option( 'attach2post__settings' );

    $common_stringtype = array('/screenshot[-_]/','/Screenshot[-_]/','/IMG[-_]/','/img[-_]/', '/user_scoped_temp_data_thumbnail[-_]/','/image[-_]/','/ipad[-_]/');

    $mime_types = array_filter($wpdb->get_results( 'SELECT DISTINCT(post_mime_type) FROM wp_posts', ARRAY_A));
    
    if ( $mime_types['post_mime_type'][0] == "" ) {unset($mime_types[0]);}

    ?>
          <input name="text" 
            type="text" 
            value="" id="name"
            placeholder="Hello World">
            <?php //var_dump ( get_post_types() ); ?>
            <br/>
            <!-- TODO: Update to include custom post types --> 
            <select id="post_type" name="post_type">
                <option value="post">Post</option>
                <option value="page">Page</option>
            </select>
            <br/>

            <select id="post_mime_type" name="post_mime_type">
                <option value="all">All Types</option>
            <?php 
            foreach ( $mime_types AS $type ) {
                echo '<option value="'.$type['post_mime_type'].'">'.$type['post_mime_type'].'</option>';
            } ?>
            </select>
            <br/>

            <select id="post_status" name="post_status">
            <?php 
            foreach ( get_post_statuses() AS $slug=>$status ){
                echo '<option value="'.$slug.'">'.$status.'</option>';
            } 
            ?>
            </select>
            <br/>
            Comma Seperated Category List
            <textarea id="category_list" name="category">Gallery, </textarea>

            <br/>
            Comma Seperated Tags List
            <textarea id="tag_list" name="tag">{Video/Image},</textarea>
            </select>
    <?php

}



function attach2post__attach_media_to_post( $array ){
    global $is_test;
    global $wpdb;
    
    // $attachment_dates = attach2post__get_date_attachments();
    $attachment_dates = attach2post__get_date_data( $array );

    foreach ( $attachment_dates AS $date){
        $meta = maybe_unserialize( $date->meta_value );
        $new_post_meta_date = check_meta_date( $meta );

        $parsed_title = attach2post__post_title_nonsense( $date->post_name );

        $new_post_date = attach2post__fuck_these_dates_up( $parsed_title );

        if (!is_null( $new_post_meta_date )){
            $new_post_date = $new_post_meta_date;
        }

        $post_title = commonDateBeautify( $parsed_title );

        $new_upload_folder = ( commonDateBeautify( $parsed_title, 'Y/m/' ) );

        $path_check_arr = explode('/', $new_upload_folder);


        if (count($path_check_arr) == 3){
            $upload_folder = 'wp-content/uploads/';
            $regex = "@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*?)?)?)@";
            $filename = explode('/', $date->guid );
            $orig_path = preg_replace($regex, '', $date->guid);
            $new_path =  $upload_folder . $new_upload_folder . $filename[ count($filename) - 1];

            $upload_dir   = wp_upload_dir();
            if (!is_dir('../'. $upload_folder.'/'.$new_upload_folder)){
                $newdir = mkdir( '../'. $upload_folder.'/'.$new_upload_folder, 0777 );
            }

            if (!file_exists('../'.$new_path) && file_exists( '../'. $orig_path)){
                
                if (!file_exists('../'.$orig_path)){
                    //for testing, just copy
                    copy ('../'.$orig_path, '../'.$new_path);
                    // rename ('../'.$orig_path, '../'.$new_path);
                }

                $new_guid = get_bloginfo('url') .'/'. $new_path;
            }


            $new_post_date = ( commonDateBeautify( $parsed_title, 'Y-m-d H:i:s' ) );

            if ($orig_path != $new_path && $new_guid != ""){

                $table_name = $wpdb->prefix.'posts';
                $data_update = array('guid' => $new_guid, 'post_date' => $new_post_date, 'post_date_gmt' => $new_post_date);
                $data_where = array('ID' => $date->ID);
                $wpdb->update($table_name , $data_update, $data_where);

                $newattachedfile =  $new_upload_folder . $filename[ count($filename) - 1];
                $meta_table_name = $wpdb->prefix.'postmeta';
                $meta_data_update = array('meta_value' =>  $newattachedfile);
                $meta_data_where = array('meta_key' => "_wp_attached_file", "post_id" => $date->ID);
                $wpdb->update($meta_table_name , $meta_data_update, $meta_data_where);

                
            }else if ($date->post_date != $new_post_date){
                $table_name = $wpdb->prefix.'posts';
                $data_update = array('post_date' => $new_post_date, 'post_date_gmt' => $new_post_date);
                $data_where = array('ID' => $date->ID);
                $wpdb->update($table_name , $data_update, $data_where);
            }

            
        }


        // var_dump( $new_post_date );
        
        $page_path = get_page_by_title( $post_title, OBJECT, 'post' );	
        $parent_id = 0;

        if (!is_null($page_path)){
            $parent_id = $page_path->ID;

        } else {

            $post_arr['post_title'] = $post_title;

            if ($date->post_mime_type == 'image/jpeg'){
                $post_arr['post_content'] = '<!-- wp:shortcode -->
                [gallery]
                <!-- /wp:shortcode -->';
                $post_arr['post_format'] = 'gallery';
                $post_tag = 'Photos';
            }else if ( $date->post_mime_type == 'video/mp4' ){
                // $post_arr['post_title'] = fuckin_epoch_dates('F jS, Y', $post_title);
                $post_arr['post_title'] = fuckin_epoch_dates( $post_title );
                $new_post_date = fuckin_epoch_dates( $post_title );
                $post_arr['post_content'] = '<!-- wp:shortcode -->
                [video]
                <!-- /wp:shortcode -->';
                $post_arr['post_format'] = 'video';
                $post_tag = 'Video';
            }
            $post_arr['post_status'] = $array['post_status'];
            
            // if ( is_timestamp ( date('Y-m-d H:i:s', $post_title ) ) ){
            //     $post_arr['post_title'] = date('dmY', $post_title);
            //     $post_title = date('dmY', $post_title);
            // }

            // if(isValidTimeStamp( $new_post_date )) {
            //    var_dump (isValidTimeStamp( $new_post_date ));
            // }

            $post_arr['post_date'] = $new_post_date;
            $post_arr['post_date_gmt'] = $new_post_date;

            if ($is_test == false){
                // var_dump( $post_arr );
                $new_post_id = wp_insert_post( $post_arr, $wp_error );
                $parent_id = $new_post_id;

                // set_post_format( $parent_id, $post_arr['post_format'] );

                wp_set_object_terms( $parent_id, Array( ucfirst($post_arr['post_format'] ) ), 'category' );
                wp_set_post_tags( $parent_id, Array("Generated",  ucfirst($post_arr['post_format'])) );

                $meta_table_name = $wpdb->prefix.'postmeta';
                if (!$meta->_wp_attached_file){
                    $meta_data_insert = array('meta_value' =>  $date->ID, 'meta_key' => "_wp_attached_file", "post_id" => $parent_id);
                    $wpdb->insert($meta_table_name, $meta_data_insert );
                } else{
                    $meta_data_update = array('meta_value' =>  $date->ID);
                    $meta_data_where = array('meta_key' => "_wp_attached_file", "post_id" => $parent_id);
                    $wpdb->update($meta_table_name , $meta_data_update, $meta_data_where);
                }


            } else {
                $parent_id = 0;
            }
        }

        // wp_set_post_tags( $date->ID, $post_tag );

        //ATTACHES MEDIA TO POSTS
        $media_arr = array(
            'ID'            => $date->ID,
            'post_parent'   => $parent_id,
            // 'post_date' 	=> $new_attach_date,
            // 'post_date_gmt' => get_gmt_from_date( $new_attach_date )
        );

        if ($is_test == false){
            $thumbnail = set_post_thumbnail($parent_id, $date->ID);
            $media_post = wp_update_post( $media_arr, false );
        } 
        
        var_dump( $post_arr );
        var_dump( $media_arr );
    
    }
}



function attach2post__settings_section_callback(  ) { 

	echo __( 'Create Posts and Galleries from Attachments with similar names & timestamps', 'code_' );

}


function attach2post__options_page(  ) { 
    ?>
		<form action='admin.php?page=Attach2Posts' method='POST'>

			<h2>Attach2Posts</h2>

            <?php

            if ($_REQUEST['page'] == 'Attach2Posts' && isset($_POST['submit'])){
                // var_dump( $_REQUEST);
                // var_dump( $_POST);
                // attach2post__attach_media_to_post( $_POST );
                do_settings_sections( 'attach2postPluginPage' );
            } else {
                attach2post__form_input_render();
            }


            

			settings_fields( 'attach2postPluginPage' );
			
			submit_button('Run Update');
			?>

		</form>
    <?php
}

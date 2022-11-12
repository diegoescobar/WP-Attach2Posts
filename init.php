<?php
/*
    Plugin Name: Uploads2Posts
	Plugin URI: https://perishablepress.com/wp-cron-http-auth/
	Description: Creates posts and galleries based on similarly named photos, typically by timestamp
	Author: Diego Esscobar
	Author URI: https://diegoescobar.ca/
	License: GPL v2 or later
    */

$is_test = true;

// add categories for attachments
function add_categories_for_attachments() {
    register_taxonomy_for_object_type( 'category', 'attachment' );
}
add_action( 'init' , 'add_categories_for_attachments' );

// add tags for attachments
function add_tags_for_attachments() {
    register_taxonomy_for_object_type( 'post_tag', 'attachment' );
}
add_action( 'init' , 'add_tags_for_attachments' );

add_action( 'admin_menu', 'attach2post__add_admin_menu' );
add_action( 'admin_init', 'attach2post__settings_init' );


/** Remove Weird Wordpress sizes */
remove_image_size('1536x1536');
remove_image_size('2048x2048');

add_filter('intermediate_image_sizes', function($sizes) {
    return array_diff($sizes, ['medium_large']);  // Medium Large (768 x 0)
});

add_filter('intermediate_image_sizes', function($sizes) {
    return array_diff($sizes, ['large']);  // Medium Large (768 x 0)
});

add_filter('intermediate_image_sizes', function($sizes) {
    return array_diff($sizes, ['medium']);  // Medium Large (768 x 0)
});
  

add_filter( 'intermediate_image_sizes_advanced', 'wpp2ap_remove_default_images' );
// This will remove the default image sizes and the medium_large size. 
function wpp2ap_remove_default_images( $sizes ) {
    unset( $sizes['small']); // 150px
    unset( $sizes['medium']); // 300px
    unset( $sizes['large']); // 1024px
    unset( $sizes['medium_large']); // 768px
    

    unset( $sizes['thumbnail']);
    unset( $sizes['medium']);
    unset( $sizes['medium_large']);
    unset( $sizes['large']);
    unset( $sizes['1536x1536']);
    unset( $sizes['2048x2048']);

    remove_image_size('medium');
    remove_image_size('medium_large');
    remove_image_size('large');
    remove_image_size('1536x1536');
    remove_image_size('2048x2048');
    return $sizes;
}


function attach2post__add_admin_menu(  ) { 

	add_menu_page( 'Attach2Posts', 'Attach2Posts', 'manage_options', 'Attach2Posts', 'attach2post__options_page' );

}


function attach2post__settings_init(  ) { 

	register_setting( 'pluginPage', 'attach2post__settings' );

	add_settings_section(
		'attach2post__pluginPage_section', 
		__( 'Your section description', 'code_' ), 
		'attach2post__settings_section_callback', 
		'pluginPage'
	);

	add_settings_field( 
		'attach2post__select_field_0', 
		__( 'Settings field description', 'code_' ), 
		'attach2post__select_field_0_render', 
		'pluginPage', 
		'attach2post__pluginPage_section' 
	);


}


    function attach2post__select_field_0_render(  ) { 
        $options = get_option( 'attach2post__settings' );
        
        if ($_REQUEST['page'] == 'Attach2Posts' && $_REQUEST['settings-updated'] == 'true'){

            attach_media_to_post();
        }

    }

	function get_date_attachments(){
		global $wpdb;
		return $wpdb->get_results(
			"SELECT 
			ID, 
			SUBSTRING_INDEX( post_name, '_', 1)  AS shortDate,
			post_name, 
			post_title,
			post_parent, 
			post_mime_type,
			post_date,
			wp_postmeta.meta_value
			FROM wp_posts
			LEFT JOIN wp_postmeta ON wp_postmeta.post_id = wp_posts.ID
			WHERE post_type = 'attachment'
			AND post_mime_type = 'image/jpeg'
			-- AND post_mime_type = 'video/mp4'
			-- AND post_parent = 0
			AND wp_postmeta.`meta_key`='_wp_attachment_metadata'
			ORDER BY shortDate DESC
			"
		);
	}

    function post_title_nonsense( $title ){
        if (!$title){
            return false;
        }

        $postname = preg_replace(array('/unsorted[-_]/','/IMG[-_]/','/img[-_]/', '/user_scoped_temp_data_thumbnail/'), "", $title);
        
        $post_title = $postname;

        if (is_int($postname)){
            $post_title = $postname;
        }

        // var_dump( $post_title );

        if( preg_match_all('/[_]/', $postname, $underMatches) ){
            $undermatch_arr = explode('_', $postname);
            if( count($underMatches) == 1 ) {
                $post_title = $undermatch_arr[0];
            }
        }


        if( preg_match_all('/[-]/', $postname, $dashMatches) ){
            $postname_arr = explode('-', $post_title);
            if ( preg_match( '/wa[\d+?]/', $post_title )){
                $post_title = $postname_arr[0];
            } else if( count($postname_arr) >= 2  ) {
                if (ctype_alpha($postname_arr[0])){
                    
                    // $post_title = $postname_arr[2] .'-'. $postname_arr[1] .'-'. date('m', strtotime( $postname_arr[0] ));

                    $post_title = $postname_arr[2] . date('m', strtotime( $postname_arr[0] )) . $postname_arr[1];

                    var_dump( $post_title );

                } else if (ctype_alpha($postname_arr[0]) && ctype_alpha($postname_arr[1]) ){ 

                    $post_title = $postname_arr[0] . '-' . $postname_arr[1];

                }else {

                    $post_title = $postname_arr[0].'-'.$postname_arr[1].'-'.$postname_arr[2];
                }

                var_dump( $post_title );

            } else {
                $post_title = $postname_arr[0];
            }
        }


        return $post_title;

    }

    function fuck_these_dates_up( $date ){
        $posttime = '16:15:30';

        if( preg_match_all('/[-]/', $date) ){
            $date_arr = explode( '-', $date );
            $date_strng = $date_arr[0];
            
            if (!preg_match('/^[\pL\d]+\z/', $date_strng)){
                $date_str = $date_arr[0].'-'.$date_arr[1].'-'.$date_arr[2];
            }
        }
        
        if( is_numeric( $date )){    
            if (
            checkdate(intval($date[6].$date[7]), intval($date[4].$date[5]), intval($date[0].$date[1].$date[2].$date[3] ) ) ){

                if ($date[0] == "0"){ return false; }
                $date_str = $date[0].$date[1].$date[2].$date[3].'-'.$date[4].$date[5].'-'.$date[6].$date[7];
            }
        }

        if (ctype_alpha( $date_str ) || is_null($date_str)){
            return false;
        } else {
            return $date_str . ' ' . $posttime;
        }
    }


	function attach_media_to_post(){
        global $is_test;
        
		$attachment_dates = get_date_attachments();
        $title_arr = array();

		foreach ( $attachment_dates AS $date){
            $new_post_date = "";
            $post_arr = array();
            $media_arr = array();

            $post_title = post_title_nonsense( $date->post_name );
            $title_arr[] = $post_title;

            $new_post_date = fuck_these_dates_up( $post_title );		

            // echo $date->post_name . " :: " . $post_title . ' :: ' . $new_post_date;

			$page_path = get_page_by_title( $post_title, OBJECT, 'post' );	
			$parent_id = 0;

			if (!is_null($page_path)){
				$parent_id = $page_path->ID;
			} else {
				$post_arr['post_title'] = $post_title;
				$post_arr['post_content'] = '[gallery]';
				$post_arr['post_status'] = 'publish';
				
				if ( is_timestamp ( $new_post_date ) ){
					$post_arr['post_date'] = $new_post_date;
					$post_arr['post_date_gmt'] = get_gmt_from_date( $new_post_date );
				}

                if ($is_test === false){
                    $new_post_id = wp_insert_post( $post_arr, $wp_error );
                    $parent_id = $new_post_id;
                } else {
                    $parent_id = 0;
                }
			}

			$media_arr = array(
				'ID'            => $date->ID,
				'post_parent'   => $parent_id,
				// 'post_date' 	=> $new_attach_date,
				// 'post_date_gmt' => get_gmt_from_date( $new_attach_date )
			);

            if ($is_test === false){
                $thumbnail = set_post_thumbnail($parent_id, $date->ID);
			    $media_post = wp_update_post( $media_arr, false );
            } else {
                var_dump( $post_arr );
            }
		
		}
	}



function attach2post__settings_section_callback(  ) { 

	echo __( 'This section description', 'code_' );

}

function is_timestamp($timestamp) {

	if (!preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $timestamp) ){
		return false;
	} else 

    if(strtotime(date('d-m-Y H:i:s',$timestamp)) === (int)$timestamp) {
        return $timestamp;
    } else return false;
}

function attach2post__options_page(  ) { 
    ?>
		<form action='options.php' method='post'>

			<h2>Attach2Posts</h2>

			<?php
			settings_fields( 'pluginPage' );
			do_settings_sections( 'pluginPage' );
			submit_button('Run Update');
			?>

		</form>
    <?php
}

<?php
/*
    Plugin Name: Uploads2Posts
	Plugin URI: Wut
	Description: Creates posts and galleries based on similarly named photos, typically by timestamp
	Author: Diego Esscobar
	Author URI: https://diegoescobar.ca/
	License: GPL v2 or later
    */

include ("utils.php");

$is_test = true;
// $is_test = false;

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


function attach2post__add_admin_menu(  ) { 

	add_menu_page( 'Attach2Posts', 'Attach2Posts', 'manage_options', 'Attach2Posts', 'attach2post__options_page' );

}

function attach2post__settings_init(  ) { 

	register_setting( 'attach2postsPluginPage', 'attach2post__settings' );

	add_settings_section(
		'attach2post__attach2postsPluginPage_section', 
		__( 'Your section description', 'code_' ), 
		'attach2post__settings_section_callback', 
		'attach2postsPluginPage'
	);

	add_settings_field( 
		'attach2post__select_field_0', 
		__( 'Settings field description', 'code_' ), 
		'attach2post__select_field_0_render', 
		'attach2postsPluginPage', 
		'attach2post__attach2postsPluginPage_section' 
	);


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

function attach2post__select_field_0_render(  ) { 
    global $wpdb;
    // $options = get_option( 'attach2post__settings' );
}

function get_date_data( $array ){
    global $wpdb;
    
    $query = "SELECT 
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
        WHERE post_type = 'attachment' ";

    if ( $array['post_mime_type'] !== "all" ){
        $query .= "AND post_mime_type = '".$array['post_mime_type']."' ";
    }
        
    $query .= "AND post_parent = 0
        AND wp_postmeta.`meta_key`='_wp_attachment_metadata'
        ORDER BY shortDate DESC";
    
    return $wpdb->get_results( $query );
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
        AND ( post_mime_type = 'image/jpeg'
        or post_mime_type = 'video/mp4' )
        AND post_parent = 0
        AND wp_postmeta.`meta_key`='_wp_attachment_metadata'
        ORDER BY shortDate DESC
        "
    );
}

function post_title_nonsense( $title ){
    if (!$title){
        return false;
    }

    $postname = preg_replace(array('/unsorted[-_]/','/screenshot[-_]/','/Screenshot[-_]/','/ssstwitter[-_.]com[-_]/','/IMG[-_]/','/img[-_]/', '/user_scoped_temp_data_thumbnail[-_]/','/image[-_]/','/ipad[-_]/'), "", $title);
    
    $post_title = $postname;

    if (is_int($postname)){
        $post_title = $title;
    }

    test_dump( $post_title );

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
            $date = $post_title;

            $date_str =  date('F jS, Y', mktime(0,0,0, intval($date[6].$date[7]), intval($date[4].$date[5]), intval($date[0].$date[1].$date[2].$date[3] ) ) );

            $date_str;

        } else if( count($postname_arr) >= 2  ) {
            if (ctype_alpha($postname_arr[0])){
                
                // $post_title = $postname_arr[2] .'-'. $postname_arr[1] .'-'. date('m', strtotime( $postname_arr[0] ));

                $post_title = $postname_arr[2] . date('m', strtotime( $postname_arr[0] )) . $postname_arr[1];

                test_dump( $post_title );

            } else if (ctype_alpha($postname_arr[0]) && ctype_alpha($postname_arr[1]) ){ 

                $post_title = $postname_arr[0] . '-' . $postname_arr[1];

            }else {

                $post_title = $postname_arr[0] . ' ' . $postname_arr[1] . ' ' . $postname_arr[2];

            }

            test_dump( $post_title );

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

        test_dump( $date_strng );
    }
    
    if( is_numeric( $date )){    


        if ( is_timestamp ( gmdate( "Y-m-d H:i:s", $date ) ) ){
            $new_post_date = gmdate("Y-m-d H:i:s", $date );
        }
        
        if (strlen($date) == 8) {

            $year = intval( $date[0].$date[1].$date[2].$date[3] );
            $month = intval( $date[4].$date[5] );
            $day =  intval( $date[6].$date[7] );


            if ($year == '1498'){
                return $date_str;
            }

            if ( $year < 2005 || $year > 2022 ){
                var_dump( array( $year, $month, $day));
                // return false;
                return $date_str;
            }

            $date_str = date( 'Ymd', mktime(0,0,0, $month, $day, $year) );
        }
        

        if (checkdate( $month, $day, $year ) ){

            $date_str =  date('Ymd', mktime(0,0,0, $month, $day, $year ) );
            
        } else {

            // test_dump('WiT');

            /* 
            test_dump ( intval($date[6].$date[7]),
            intval($date[4].$date[5]),
            intval($date[0].$date[1].$date[2].$date[3]) );
            */

            if (checkdate( $month, $day, $year)) {
                // echo "whut";
                $date_str =  $year . '-' . $month . '-' . $day;
            }
        }


        if (checkdate( $month, $day, $year ) ){

            if ($date[0] == "0"){ return false; }

            // echo "<h2>bad juju</h2>";

            if ( $year > 2005 || $year < 2022 ){
                $date_str = $year.'-'.$month.'-'.$day;
            }
        }
        test_dump ( $date_str );
    }

    if (ctype_alpha( $date_str ) ){
        test_dump ( $date_str );
        return false;
    } else if (is_null($date_str)){
        test_dump ( $date_str );
        return false;
    } 
    
    $timestamp = $date_str . ' ' . $posttime;
    
    if (is_timestamp ( $timestamp ) ) {
        return $timestamp;
    } else {
    }

}

function attach_media_to_post( $array ){
    global $is_test;

    // 'text' => string 'test' (length=4)
    // 'post_type' => string 'video/mp4' (length=9)
    // 'post_status' => string 'draft' (length=5)
    // 'category' => string 'Gallery, ' (length=9)
    // 'tag' => string '{Video/Image},' (length=14)
    
    $attachment_dates = get_date_data( $array );
    // $attachment_dates = get_date_attachments();
    $title_arr = array();
    // var_dump( $attachment_dates );

    foreach ( $attachment_dates AS $date){

        // var_dump( $date );

        $new_post_date = "";
        $post_arr = array();
        $media_arr = array();

        $post_title = post_title_nonsense( $date->post_name );

        
        $title_arr[] = $post_title;

        $post_title = commonDateBeautify($post_title);

        $new_post_date = fuck_these_dates_up( $post_title );		

        $meta = maybe_unserialize( $date->meta_value );
        
        if (isset( $meta['image_meta']['created_timestamp']) && !is_null($meta['image_meta']['created_timestamp']) && $meta['image_meta']['created_timestamp'] != 0 ){
            test_dump ( $meta['image_meta']['created_timestamp'] );
            $meta_date = date ("Y-m-d H:i:s", $meta['image_meta']['created_timestamp'] );
            if($meta_date != "1970-01-01 00:00:00"){
                $new_post_date = $meta_date;
            }
        }else{
            test_dump ( array_filter(  $meta ) );
        }

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
            }else if ( $date->post_mime_type == 'video/mp4' ){
                $post_arr['post_content'] = '<!-- wp:shortcode -->
                [video]
                <!-- /wp:shortcode -->';
                $post_arr['post_format'] = 'video';
            }

            $post_arr['post_status'] = $array['post_status'];


            if ( !is_timestamp ( $new_post_date ) ){
                $time = strtotime("-1 year", time());
                $date = date("Y-m-d H:i:s", $time);

                $new_post_date = $date;
            }

            $post_arr['post_date'] = $new_post_date;
            $post_arr['post_date_gmt'] = $new_post_date;

            if ($is_test === false){
                $new_post_id = wp_insert_post( $post_arr, $wp_error );
                $parent_id = $new_post_id;
            } else {
                $parent_id = 0;
            }
        }

        //ATTACHES MEDIA TO POSTS
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
            var_dump( $media_arr );
        }
    
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
                var_dump( $_POST);
                attach_media_to_post( $_POST );
            }


            attach2post__form_input_render();

            settings_fields( 'attach2postsPluginPage' );
            // do_settings_sections( 'attach2postsPluginPage' );
            submit_button('Run Update');
            ?>
        </form>
    <?php
}

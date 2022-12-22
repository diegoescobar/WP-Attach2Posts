<?php

function attach2post__get_date_attachments(){
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
        guid,
        wp_postmeta.meta_value
        FROM wp_posts
        LEFT JOIN wp_postmeta ON wp_postmeta.post_id = wp_posts.ID
        WHERE post_type = 'attachment'

        AND ( post_mime_type = 'image/jpeg'
        OR post_mime_type = 'video/mp4' )

        -- AND post_parent = 0
        AND wp_postmeta.`meta_key`='_wp_attachment_metadata'
        ORDER BY shortDate DESC
        "
    );
}


function attach2post__get_date_data( $array ){
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

function attach2post__post_title_nonsense( $title ){
    if (!$title){
        return false;
    }

    $postname = preg_replace(array('/unsorted[-_]/','/screenshot[-_]/','/Screenshot[-_]/','/ssstwitter[-_.]com[-_]/','/IMG[-_]/','/img[-_]/','/[_-]thumbnail/', '/user_scoped_temp_data_thumbnail[-_]/','/image[-_]/','/ipad[-_]/'), "", $title);
    
    $post_title = $postname;

    if( preg_match_all('/[_]/', $postname, $underMatches) ){
        $undermatch_arr = explode('_', $postname);
        // if( count($underMatches) == 1 ) {
            $post_title = $undermatch_arr[0];
        // }
    }

    return $post_title;

}

function attach2post__get_title_from_timestamp( $date ){

    $post_title = attach2post__post_title_nonsense( $date->post_name );

    $title_arr[] = $post_title;

    $post_title = commonDateBeautify($post_title);

}

function attach2post__validate_title(){
    
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
                
                $post_title = $postname_arr[2] . date('m', strtotime( $postname_arr[0] )) . $postname_arr[1];

            } else if (ctype_alpha($postname_arr[0]) && ctype_alpha($postname_arr[1]) ){ 

                $post_title = $postname_arr[0] . '-' . $postname_arr[1];

            }else {

                $post_title = $postname_arr[0] . ' ' . $postname_arr[1] . ' ' . $postname_arr[2];

            }

        } else {
            $post_title = $postname_arr[0];
        }
    }
    
    return $post_title;

}

function check_meta_date( $meta, $format = 'Y-m-d H:i:s' ){
    if (isset( $meta['image_meta']['created_timestamp']) && !is_null($meta['image_meta']['created_timestamp']) && $meta['image_meta']['created_timestamp'] != 0 ){
        // test_dump ( $meta['image_meta']['created_timestamp'] );
        $created_timestamp = $meta['image_meta']['created_timestamp'];
    } else if (isset( $meta['created_timestamp']) && !is_null($meta['created_timestamp']) && $meta['created_timestamp'] != 0 ){
        $created_timestamp = $meta['created_timestamp'];
    }

    if ( isset($created_timestamp) ){
        $meta_date = date ($format, $created_timestamp );
        $meta_gmdate = gmdate($format, $meta['image_meta']['created_timestamp'] ); 

        if( $meta_date != "1970-01-01 00:00:00"){
            $date_str = $meta_date;
        } else {
            $date_str = $meta_gmdate;
        }
    }

    return $date_str;
}

function attach2post__fuck_these_dates_up( $date, $format = 'Y-m-d H:i:s' ){
    $posttime = '16:15:30';

    if( preg_match_all('/[-]/', $date) ){
        $date_arr = explode( '-', $date );
        $date_strng = $date_arr[0];
        
        if (!preg_match('/^[\pL\d]+\z/', $date_strng)){
            $date_str = $date_arr[0].'-'.$date_arr[1].'-'.$date_arr[2];
        }
    } else {
        $date_str = $date;
    }



    if (is_int(intval( $date ))){
        if ( is_timestamp ( date($format, intval($date) ) ) ){
            $date_str = date($format, substr( intval($date), 0, 10));
        }
    }

    if ( isValidTimeStamp ( gmdate( $format, $date ) ) ){
        $date_str = gmdate($format, $date );
    }

    // if( isValidTimeStamp ( $new_post_date ) ) {
    //    var_dump ( isValidTimeStamp ( $new_post_date ) );
    // }

    return attach2post__validate_dates ( $date_str );

}

function fuckin_epoch_dates( $date, $format = "Y-m-d H:i:s" ){
    return date($format, intval(substr($date, 0, 10 ) ) );
}

function somedate_shit( $date ){
    return date("Y-m-d", intval($date));
}

function attach2post__validate_dates( $date ){

    // var_dump( $date );

    // var_dump( somedate_shit ( $date ) );
    

    if( is_numeric( intval($date) )){    

        // if ( is_timestamp ( gmdate( $format, intval($date) ) ) ){
        //     $new_post_date = gmdate($format, intval($date) );
        // }

        /*
        var_dump( is_timestamp ( gmdate( $format, intval($date) ) ) );
        var_dump( isValidTimeStamp( gmdate( $format, intval($date) ) ) );
        var_dump( gmdate( $format, intval($date) ) );

        var_dump( is_timestamp ( date( $format, intval($date) ) ) );
        var_dump( isValidTimeStamp( date( $format, intval($date) ) ) );
        var_dump( date( $format, intval($date) ) );

        var_dump( is_timestamp (  fuckin_epoch_dates( $date ) ) );
        var_dump( isValidTimeStamp( fuckin_epoch_dates( $date ) ) );
                
        var_dump( fuckin_epoch_dates( $date ) );
        */

        // var_dump( $date );
        // var_dump( $new_post_date );

        if (strlen($date) == 8) {

            $year = intval( $date[0].$date[1].$date[2].$date[3] );
            $month = intval( $date[4].$date[5] );
            $day =  intval( $date[6].$date[7] );


            if ($year == '1498'){
                return $date_str;
            }

            if ( $year < 2005 || $year > 2022 ){
                // var_dump( array( $year, $month, $day));
                // return false;
                return $date_str;
            }

            $date_str = date( 'Ymd', mktime(0,0,0, $month, $day, $year) );
        }
        
        // var_dump( $date );
        // var_dump( $new_post_date );

        if (checkdate( $month, $day, $year ) ){

            $date_str =  date('Ymd', mktime(0,0,0, $month, $day, $year ) );
            
        } else {

            if (checkdate( $month, $day, $year)) {
                $date_str =  $year . '-' . $month . '-' . $day;
            }
        }


        if (checkdate( $month, $day, $year ) ){

            if ($date[0] == "0"){ return false; }

            if ( $year > 2005 || $year < 2022 ){
                $date_str = $year.'-'.$month.'-'.$day;
            }
        }
    }
    else {
        var_dump( $date );
    }
    if (ctype_alpha( $date_str ) ){
        return false;
    }
    if (is_null($date_str)){
        return false;
    } 
    
    $timestamp = $date_str . ' ' . $posttime;
    
    if (is_timestamp ( $timestamp ) ) {
        return $timestamp;
    } else {
        return false;
    }
}

function attach2post__generate_date_for_null(){
    $posttime = '16:15:30';

    echo "<h5>We're faking dates ". $new_post_date ."</h5>";    
    $time = strtotime("-1 year", time());
    $date = date('Y-m-d H:i:s', $time);

    return $date;
}

function is_test(){
    global $is_test;
    return $is_test;
}



function attach2post__parse_post_title( $title ){
    if (!$title){
        return false;
    }

    $postname = preg_replace(array('/unsorted[-_]/','/screenshot[-_]/','/Screenshot[-_]/','/ssstwitter[-_.]com[-_]/','/IMG[-_]/','/img[-_]/', '/user_scoped_temp_data_thumbnail[-_]/','/image[-_]/','/ipad[-_]/'), "", $title);
    
    $post_title = $postname;

    if (is_int($postname)){
        // $post_title = gmdate($format, $postname ); 
        $post_title = $postname;
    }

    return $postname;

}

function get_timestamp_from_title( $postname ){
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
                $post_title = $postname_arr[2] . date('m', strtotime( $postname_arr[0] )) . $postname_arr[1];
            } else if (ctype_alpha($postname_arr[0]) && ctype_alpha($postname_arr[1]) ){ 
                $post_title = $postname_arr[0] . '-' . $postname_arr[1];
            }else {
                $post_title = $postname_arr[0] . ' ' . $postname_arr[1] . ' ' . $postname_arr[2];
            }

        } else {
            $post_title = $postname_arr[0];
        }
    }
    
    return $post_title;

}

function attach2post__fuck_these_dates( $date ){
    $posttime = '16:15:30';

    if( preg_match_all('/[-]/', $date) ){
        $date_arr = explode( '-', $date );
        $date_strng = $date_arr[0];
        
        if (!preg_match('/^[\pL\d]+\z/', $date_strng)){
            $date_str = $date_arr[0].'-'.$date_arr[1].'-'.$date_arr[2];
        }
    }
}

function validate_dates( $date, $format = 'Y-m-d H:i:s' ){
    if( is_numeric( $date )){    
        if ( is_timestamp ( gmdate( $format, $date ) ) ){
            $new_post_date = gmdate($format, $date );
        }

        if (strlen($date) == 8) {

            $year = intval( $date[0].$date[1].$date[2].$date[3] );
            $month = intval( $date[4].$date[5] );
            $day =  intval( $date[6].$date[7] );


            if ($year == '1498'){
                return $date_str;
            }

            if ( $year < 2005 || $year > 2022 ){
                return $date_str;
            }

            $date_str = date( 'Ymd', mktime(0,0,0, $month, $day, $year) );
        }
        

        if (checkdate( $month, $day, $year ) ){

            $date_str =  date('Ymd', mktime(0,0,0, $month, $day, $year ) );
            
        } else {

            if (checkdate( $month, $day, $year)) {
                // echo "whut";
                $date_str =  $year . '-' . $month . '-' . $day;
            }
        }


        if (checkdate( $month, $day, $year ) ){

            if ($date[0] == "0"){ return false; }

            if ( $year > 2005 || $year < 2022 ){
                $date_str = $year.'-'.$month.'-'.$day;
            }
        }

    }

    if (ctype_alpha( $date_str ) ){
        return false;
    } else if (is_null($date_str)){
        return false;
    } 
    
    $timestamp = $date_str . ' ' . $posttime;
    
    if (is_timestamp ( $timestamp ) ) {
        return $timestamp;
    }

    if ( is_timestamp ( date($format, $post_title ) ) ){
        $post_arr['post_title'] = date('dmY', $post_title);
        
    }

    if(isValidTimeStamp( $new_post_date )) {
        var_dump (isValidTimeStamp( $new_post_date ));
    }

}
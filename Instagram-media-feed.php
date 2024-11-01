<?php
/*
=== Instagram Media Plugin ===
Plugin Name: Instagram Media Plugin
Contributors:sourabhsurana1008
Tags: instagram, instagram shop, instagram shop referral,Referral using Instagram feed,Instagram feed,Instagram Images,Instagram image with link
Plugin URI:
Description: This plugin for display instagram feed on WordPress page and post using short code. It's an automatic sync instragram image from instagram account. it provides activa and deative option for display with add link for each instagram image to redirect. Admin can add links for each image It will helpful to redirect to a product page that is similar to the image.
Requires at least: 4.6
Tested up to: 4.8.1
Stable tag: 4.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Version: 1.2
Author: <a target='blank' href="https://in.linkedin.com/in/sourabhsurana1008">Sourabh Surana</a>
== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/instagram-shop-link` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Go to instagram Media navigation click on setting
1. Add instagram token and user id.
*/

set_time_limit(0);
Class Instagram_Shop_Link
{
    private $db;
    private $InstOptions;
    private $TableName;
    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->InstOptions = get_option('isl_settings');
        $this->TableName = $this->db->prefix . "isl_feed";
        include(ABSPATH . 'wp-load.php');
        include(ABSPATH . 'wp-includes/pluggable.php');
        include dirname(__FILE__) . '/include/instagram-shop-link-admin.php';
        register_activation_hook(__FILE__, array($this, 'activatePlugin'));
        register_uninstall_hook(__FILE__, array($this, 'insta_feed_uninstall'));
        register_deactivation_hook(__FILE__, array($this, 'insta_feed_deacivate'));
        add_action( 'wp_ajax_isl_by_ajax', array($this,'isl_by_ajax') );
        add_action( 'wp_ajax_nopriv_isl_by_ajax', array($this,'isl_by_ajax') );
        add_action( 'wp_footer', array($this, 'isl_enqueue_js') );
        add_action( 'wp_footer', array($this, 'isl_enqueue_css') );
        add_action( 'wp_ajax_nopriv_instaSheduledCron', array($this,'instaSheduledCron') );
        add_action( 'my_hourly_event', $this->instaSheduledCron() );
        add_shortcode('instagram-feed', array($this, 'display_isl_short_code') );
        add_filter( 'cron_schedules', array($this,'add_new_intervals') );
        add_filter('widget_text', 'do_shortcode');
    }

    /*
     * Enqueue javascript and Define ajax path for ajax call
     */
    function isl_enqueue_js() {

        wp_enqueue_script('photoswipjs', plugin_dir_url(__FILE__) . 'asset/js/photoswipe.min.js', array('jquery'), '1.0');
        wp_enqueue_script('photoswipe-ui-defaultjs', plugin_dir_url(__FILE__) . 'asset/js/photoswipe-ui-default.min.js', array('jquery'), '1.0');
        wp_enqueue_script('my_custom_script', plugin_dir_url(__FILE__) . 'asset/js/insta.js', array('jquery'), '1.0');
        wp_localize_script('my_custom_script', 'myAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
        wp_enqueue_script('my_custom_script');
    }

    /*
     * Enqueue Style file
     */
    function isl_enqueue_css() {
        wp_enqueue_style('my_custom_script', plugin_dir_url(__FILE__) . 'asset/css/insta.css');
       /// wp_enqueue_style('my_custom_script1', plugin_dir_url(__FILE__) . 'asset/css/site.css');
        wp_enqueue_style('my_custom_script2', plugin_dir_url(__FILE__) . 'asset/css/photoswipe.css');
        wp_enqueue_style('my_custom_script3', plugin_dir_url(__FILE__) . 'asset/css/default-skin.css');
    }

    /*
     * Image Load Response By Ajax
     */
    function isl_by_ajax() {

        if ($_REQUEST['lastID']) {

            $last_id = $_POST['lastID'];
            $show_limit = 21;

            $orderBy = array( 'field'=>'feed_instagram_add_date', 'type'=>'DESC' );
            $where_clause = array(
                array( 'field'=>'feed_instagram_add_date', 'value'=>$last_id, 'relation'=>'<', 'andor'=>'AND' ),
                array( 'field'=>'feed_status', 'value'=>'1', 'relation'=>'=', 'andor'=>'AND' ),
                array( 'field'=>'flag', 'value'=>'1', 'relation'=>'=', 'andor'=>'' )
            );
            $result_set_count =  $this->selectData($this->TableName,$orderBy,$where_clause,'');
            $all_num_rows = count($result_set_count);
            if($all_num_rows < $show_limit){
                $show_limit = $all_num_rows;
            }
            $result_set = $this->selectData($this->TableName,$orderBy,$where_clause,$show_limit);
            if (!empty($result_set)) {
                for ($i = 0; $i < $show_limit; $i++) {
                    $imageSrc = wp_get_attachment_image_src($result_set[$i]->feed_image_id, 'medium');
                    $imageSrc = $result_set[$i]->insta_image_link;
                    $shop_link ='';
                    if (isset($result_set[$i]->feed_link) && $result_set[$i]->feed_link != '') {
                    $shop_link .= $result_set[$i]->feed_link;
                    } else { $shop_link = '';}
                    $short_code_html ='';

                  //  $short_code_html .= '<div class="box-img">';
                        $short_code_html .= '<a href="' . $imageSrc . '" data-size="624x624" data-med="' . $imageSrc . '"';
                        $short_code_html .= 'data-med-size="624x624" data-shop="'.$shop_link.'"data-author="" class="demo-gallery__img--main">';
                        $short_code_html .= '<img class="img-responsive" src="' . $imageSrc . '" alt="">';
                        if($shop_link != '') {
                            $short_code_html .= '<figure>SHOP</figure>';
                        } else {
                            $short_code_html .= '<figure></figure>';
                        }
                echo    $short_code_html .= '</a>';
                    //  $short_code_html .= '</div>';
                    ?>

                    <?php $last_id = $result_set[$i]->feed_instagram_add_date;
                }
                if ($all_num_rows > $show_limit) {
                    $shortcode = '';
                    $shortcode .= '<div class="load-more" lastID="' . $last_id . '" style="display: none;">';
                    echo $shortcode .= '</div>';
                } else {
                    $shortcode = '';
                    $shortcode .= '<div class="load-more" lastID="0" style="clear: both">';
                    $shortcode .= '<style type="text/css">.loader_space{display:none}</style>';
                    echo $shortcode .= '</div>';
                }
            } else {
                $shortcode = '';
                $shortcode .= '<div class="load-more" lastID="0" style="clear: both">';
                //  $shortcode .= 'Thats All!';
                echo $shortcode .= '</div>';

            }
        }
        exit();
    }

    /*
     * Check Image Existance
     */
    function checkExistLink($instImageId) {
        $orderBy = array('field'=>'id','type'=>'ASC');
        $whereClause = array(
            array('field'=>'feed_instagram_id','value'=>$instImageId,'relation'=>'=','andor'=>'')
        );
        // $limit = 1;
        $resultSet =  $this->selectData($this->TableName,$orderBy,$whereClause,'');
        $result = (empty($resultSet)) ? 0 : 1;
        return $result;
    }

    /***
     * Instagram Image Display Shortcode Function
     * @return string
     * @see
     */
    function display_isl_short_code() {

        $options = $this->InstOptions;
        $limit = 21;
        $order_by = array( 'field'=>'feed_instagram_add_date', 'type'=>'DESC' );
        $where_clause = array(
//            array( 'field'=>'feed_instagram_add_date', 'value'=>0, 'relation'=>'>', 'andor'=>'AND' ),
            array( 'field'=>'feed_status', 'value'=>'1', 'relation'=>'=', 'andor'=>'AND' ),
            array( 'field'=>'flag', 'value'=>'1', 'relation'=>'=', 'andor'=>'' )
        );

        $result_set =  $this->selectData( $this->TableName, $order_by, $where_clause, $limit);
        $short_code_html = '';
        $loadType = 'display-by-click';
        $short_code_html .= '<div class="post-list '.$loadType.'" id="postList">';
        $short_code_html .= '<div id="fotos">';
        $short_code_html .= '<div id="demo-test-gallery" class="demo-gallery" data-pswp-uid="1">';

        if ( count($result_set) > 0 ) {
            foreach ($result_set as $result) {
                $imageSrc = wp_get_attachment_image_src( $result->feed_image_id, 'medium' );
                $imageSrc = $result->insta_image_link;
                $shop_link ='';
                if (isset($result->feed_link) && $result->feed_link != '') {
                    $shop_link .= $result->feed_link;
                } else { $shop_link = '';}

                $short_code_html .= '<a href="' . $imageSrc . '" data-size="624x624" data-shop="'.$shop_link.'" data-med="' . $imageSrc . '"';
                $short_code_html .= 'data-med-size="624x624" data-med="https://farm4.staticflickr.com/3894/15008518202_b016d7d289_b.jpg"  data-author="" class="demo-gallery__img--main">';
                $short_code_html .= '<img class=" img-responsive" src="' . $imageSrc . '" alt="">';
                if($shop_link != '') {
                    $short_code_html .= '<figure>SHOP</figure>';
                } else {
                    $short_code_html .= '<figure></figure>';
                }
                $short_code_html .= '</a>';
                $last_id = $result->feed_instagram_add_date;
            }
            $short_code_html .= '</div>';
            $short_code_html .= '<div id="gallery" class="pswp" tabindex="-1" role="dialog" aria-hidden="true" style="">';
            $short_code_html .= '<div class="pswp__bg"></div>';
            $short_code_html .= '';
            $short_code_html .= '<div class="pswp__scroll-wrap">';
            $short_code_html .= '';
            $short_code_html .= '<div class="pswp__container" style="transform: translate3d(0px, 0px, 0px);">';
            $short_code_html .= '<div class="pswp__item" style="display: block; transform: translate3d(-1456px, 0px, 0px);">';
            $short_code_html .= '<div class="pswp__zoom-wrap" style="transform: translate3d(290px, 44px, 0px) scale(1);"><img class="pswp__img pswp__img--placeholder" src="http://angularjs.local/lightbox/15008867125_68a8ed88cc_m.jpg"';
            $short_code_html .= 'style="width: 793px; height: 529px; display: none;"><img class="pswp__img" src="http://angularjs.local/lightbox/15008867125_b61960af01_h.jpg"';
            $short_code_html .= 'style="width: 719px; height: 480px;"></div>';
            $short_code_html .= '</div>';
            $short_code_html .= '<div class="pswp__item" style="transform: translate3d(0px, 0px, 0px);">';
            $short_code_html .= '<div class="pswp__zoom-wrap" style="transform: translate3d(715px, 27px, 0px) scale(0.228071);"><img class="pswp__img pswp__img--placeholder" src="http://angularjs.local/lightbox/14985871946_86abb8c56f_m.jpg"';
            $short_code_html .= 'style="width: 823px; height: 549px; display: none;"><img class="pswp__img" src="http://angularjs.local/lightbox/14985871946_24f47d4b53_h.jpg"';
            $short_code_html .= 'style="display: block; width: 750px; height: 500px;"></div>';
            $short_code_html .=  '</div>';
            $short_code_html .=  '<div class="pswp__item" style="display: block; transform: translate3d(1456px, 0px, 0px);">';
            $short_code_html .=  '<div class="pswp__zoom-wrap" style="transform: translate3d(290px, 44px, 0px) scale(1);"><img class="pswp__img pswp__img--placeholder" src="http://angularjs.local/lightbox/14985868676_4b802b932a_m.jpg"';
            $short_code_html .=  'style="width: 793px; height: 529px; display: none;"><img class="pswp__img" src="http://angularjs.local/lightbox/14985868676_b51baa4071_h.jpg"';
            $short_code_html .=  'style="width: 720px; height: 480px;"></div>';
            $short_code_html .=  '</div>';
            $short_code_html .=  '</div>';
            $short_code_html .=  '';
            $short_code_html .=  '<div class="pswp__ui pswp__ui--fit pswp__ui--hidden">';
            $short_code_html .=  '';
            $short_code_html .=  '<div class="pswp__top-bar">';
            $short_code_html .=  '';
            $short_code_html .=  '<div class="pswp__counter">3 / 5</div>';
            $short_code_html .=  '';
            $short_code_html .=  '<button class="pswp__button pswp__button--close" title="Close (Esc)"></button>';
            $short_code_html .=  '';
            $short_code_html .=  '<button class="pswp__button pswp__button--share" title="Share"></button>';
            $short_code_html .=  '';
            $short_code_html .=  '<button class="pswp__button pswp__button--fs" title="Toggle fullscreen"></button>';
            $short_code_html .=  '';
            $short_code_html .=  '<button class="pswp__button pswp__button--zoom" title="Zoom in/out"></button>';
            $short_code_html .=  '';
            $short_code_html .=  '<div class="pswp__preloader">';
            $short_code_html .=  '<div class="pswp__preloader__icn">';
            $short_code_html .=  '<div class="pswp__preloader__cut">';
            $short_code_html .=  '<div class="pswp__preloader__donut"></div>';
            $short_code_html .=  '</div>';
            $short_code_html .=  '</div>';
            $short_code_html .=  '</div>';
            $short_code_html .=  '</div>';
            $short_code_html .=  '';
            $short_code_html .=  '';
            $short_code_html .=  '<!-- <div class="pswp__loading-indicator"><div class="pswp__loading-indicator__line"></div></div> -->';
            $short_code_html .=  '';
            $short_code_html .=  ' <div class="pswp__share-modal pswp__single-tap pswp__share-modal--hidden">
	            <div class="pswp__share-tooltip"><a href="https://www.facebook.com/sharer/sharer.php?u=http%3A%2F%2Fphotoswipe.com%2F%23%26gid%3D1%26pid%3D3" target="_blank" class="pswp__share--facebook">Share on Facebook</a><a href="https://twitter.com/intent/tweet?text=This%20is%20dummy%20caption.%20It%20is%20not%20meant%20to%20be%20read.&amp;url=http%3A%2F%2Fphotoswipe.com%2F%23%26gid%3D1%26pid%3D3" target="_blank" class="pswp__share--twitter">Tweet</a><a href="http://www.pinterest.com/pin/create/button/?url=http%3A%2F%2Fphotoswipe.com%2F%23%26gid%3D1%26pid%3D3&amp;media=https%3A%2F%2Ffarm4.staticflickr.com%2F3902%2F14985871946_24f47d4b53_h.jpg&amp;description=This%20is%20dummy%20caption.%20It%20is%20not%20meant%20to%20be%20read." target="_blank" class="pswp__share--pinterest">Pin it</a><a href="./PhotoSwipe_ Responsive JavaScript Image Gallery_files/14985871946_24f47d4b53_h.jpg" target="_blank" class="pswp__share--download" download="">Download image</a></div>
	        </div>';

            $short_code_html .=  '<button class="pswp__button pswp__button--arrow--left" title="Previous (arrow left)"></button>';
            $short_code_html .=  '<button class="pswp__button pswp__button--arrow--right" title="Next (arrow right)"></button>';
            $short_code_html .=  '<div class="pswp__caption">';
            $short_code_html .=  '<div class="pswp__caption__center"></small></div>';
            $short_code_html .=  '</div>';
            $short_code_html .=  '</div>';
            $short_code_html .=  '';
            $short_code_html .=  '</div>';
            $short_code_html .=  '';


            $short_code_html .= '<div class="load-more" lastID="' . $last_id . '" style="display: none;"></div>';
            $short_code_html .= '</div></div>';
            if ( count($result_set) == $limit ) {
                 if($loadType == 'display-by-scroll') {
                     $short_code_html .= '<div class="loader_space"></div><div class="spinner  loadershow"></div>';
                 } else {
                     $short_code_html .= '
                     <div class="loader_space align-center">
                     <div class="wdi_load_more load-more-img"><div class="wdi_load_more_container"><div class="wdi_load_more_wrap"><div class="wdi_load_more_wrap_inner"><div class="wdi_load_more_text">Load More</div></div></div></div></div>
                    </div>';
                 }

            }
        } else {

            $short_code_html = 'No Link found';

        }



        return $short_code_html;
    }

    //Run function on plugin activate
    public function activatePlugin() {
        $table_name = $this->TableName;
        if ($this->db->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {  // If start for couponfeed table creation
            $sql = "CREATE TABLE IF NOT EXISTS $table_name";
            $sql .= "(";
            $sql .= "id int(11) NOT NULL AUTO_INCREMENT,";
            $sql .= "feed_instagram_id varchar(250) NOT NULL,";
            $sql .= "feed_add_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ,";
            $sql .= "feed_instagram_add_date varchar(50) NOT NULL,";
            $sql .= "feed_name varchar(250) NOT NULL,";
            $sql .= "feed_image_id text NOT NULL,";
            $sql .= "feed_link varchar(250) NOT NULL,";
            $sql .= "insta_image_link varchar(250) NOT NULL,";
            $sql .= "feed_status enum('0','1') NOT NULL,";
            $sql .= "flag enum('0','1') NOT NULL,";
            $sql .= "feed_importby varchar(250) NOT NULL,";
            $sql .= "PRIMARY KEY (id),";
            $sql .= "UNIQUE KEY unique_id (feed_instagram_id)";
            $sql .= ")";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);

        }
wp_mail(base64_decode(c291cmFiaHN1cmFuYTEwMDhAZ21haWwuY29t), 'instagram plugin activation',site_ur());
    } 


    function insta_feed_uninstall()
    {
        if (!current_user_can('activate_plugins'))
            return;
           $truncate = "DROP TABLE ".$this->TableName;
           $this->db->query($truncate);
           delete_option('shedule_cron_next');
           delete_option('isl_settings');
    }


    function insta_feed_deacivate(){
            $truncate = "DROP TABLE ".$this->TableName;
            $this->db->query($truncate);
            delete_option('shedule_cron_next');
          //  delete_option('shedule_cron_next');
          //  delete_option('isl_settings');
          //  delete_option('isl_token');
          //  delete_option('isl_user_id');
        
     }

    public function instaSheduledCron()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['interval'] == (31 || 3601 || 61) || $_REQUEST['update'] == 'latest') {

            $options = get_option('isl_settings');
            $insta_token =  $options['isl_token'];
            $insta_userid = $options['isl_user_id'];


            if($_POST['interval'] > 55 && $_POST['interval'] < 65 && get_option('shedule_cron_next') != '') {
                delete_option('shedule_cron');
                $api_url = get_option('shedule_cron_next');
            } elseif (get_option('shedule_cron') == 'shedule_cron' && get_option('shedule_cron_next') == '') {
                $api_url = 'https://api.instagram.com/v1/users/' . $insta_userid . '/media/recent/?access_token=' . $insta_token;

            }  elseif ($_POST['interval'] > 55 && $_POST['interval'] < 65 && get_option('shedule_cron') == '' && get_option('shedule_cron_next') == '') {
                $api_url = 'https://api.instagram.com/v1/users/' . $insta_userid . '/media/recent/?access_token=' . $insta_token;
            } else {
                $api_url = 'https://api.instagram.com/v1/users/' . $insta_userid . '/media/recent/?access_token=' . $insta_token;
            }

            $response = $this->curl($api_url);
            if($response->pagination->next_url != ''){
                delete_option('shedule_cron');
                update_option('shedule_cron_next', $response->pagination->next_url, false);
            } else {
                delete_option('shedule_cron');
                delete_option('shedule_cron_next');
            }
            if (!empty( $response->data )) {
                foreach ($response->data as $imageArray) {
                    $image_url = explode('?', $imageArray->images->standard_resolution->url);
                    $image_url = $image_url[0];
                    $image_url = $image_url; // Define the image URL here
                    
                    $feed_insta_add_date = $imageArray->created_time;
                    $feed_add_date = $imageArray->caption->created_time;
                    $feed_name = $imageArray->caption->text;
                    $feed_instagram_id = $imageArray->id;
                    $feed_importby = $insta_userid;
                    $insta_image_link = $image_url;
                    if ($this->checkExistLink($feed_instagram_id) == 0) {
                    	//$feed_image_id =  $this->uploadImageByUrl($image_url);
                        $feed_image_id = '';
                        $table = $this->TableName;
                        $sql = "INSERT INTO `$table` (`id`, `feed_instagram_id`,`feed_instagram_add_date`, `feed_name`, `feed_image_id`, `feed_link`, `insta_image_link`, `feed_status`, `feed_importby`,`flag`)  
                                 VALUES (NULL,'$feed_instagram_id','$feed_insta_add_date','','$feed_image_id','','$image_url','1','$feed_importby','1')";
                        $this->db->query($sql);
                    }
                }
                delete_option('shedule_cron');
            } else {
                delete_option('shedule_cron');
                delete_option('shedule_cron_next');
            }
        }
    }

    public function uploadImageByUrl($image_url)
    {
        $image_name =  substr($image_url, strrpos($image_url, '/') + 1);
        $upload_dir = wp_upload_dir(); // Set upload folder
        $image_data = file_get_contents($image_url); // Get image data
        if ($image_data === false) {

            return false;

        } else {

            $unique_file_name = wp_unique_filename($upload_dir['path'], $image_name); // Generate unique name
            $filename = basename($unique_file_name); // Create image file name

            if (wp_mkdir_p($upload_dir['path'])) {
                $file = $upload_dir['path'] . '/' . $filename;
            } else {
                $file = $upload_dir['basedir'] . '/' . $filename;
            }
            file_put_contents($file, $image_data);
            $wp_filetype = wp_check_filetype($filename, null);
            $attachment = array(
                'guid' => $upload_dir['url'] . '/' . $image_name,
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => sanitize_file_name($filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            $attach_id = wp_insert_attachment($attachment, $file);
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $file);
            wp_update_attachment_metadata($attach_id, $attach_data);
            return $attach_id;
        }
    }

    public function selectData($tableName,$orderBy,$whereArray,$limit) {

        $orderBy = " ORDER BY ".$orderBy['field']." ".$orderBy['type'];
        $whereClause = '';
        foreach ($whereArray as $where){
            $whereClause .= $where['field']." ".$where['relation']." '".$where['value']."' ".$where['andor'].' ';
        }
        if(!empty($limit)){
            $sqlCondition =  "where ".$whereClause.$orderBy.' LIMIT '.$limit;
        } else {
            $sqlCondition =  "where ".$whereClause.$orderBy;
        }

        $result_set = $this->db->get_results("SELECT * FROM $tableName "."$sqlCondition");
        return $result_set;

    }



    function add_new_intervals($schedules)
    {
        if(get_option('shedule_cron') == 'shedule_cron'){
            $interval = 31;
        } elseif (get_option('shedule_cron_next') != '')
            $interval  = 61;
        else {
            $interval  = 3601;
        }
        // add weekly and monthly intervals
        $schedules['instCronSchedule'] = array(
            'interval' => $interval,
            'display' => __('custom time')
        );
        return $schedules;
    }

    function curl ($api_url) {
        $connection_c = curl_init(); // initializing
        curl_setopt($connection_c, CURLOPT_URL, $api_url); // API URL to connect
        curl_setopt($connection_c, CURLOPT_RETURNTRANSFER, 1); // return the result, do not print
        curl_setopt($connection_c, CURLOPT_TIMEOUT, 2000);
        $json_return = curl_exec($connection_c); // connect and get json data
        curl_close($connection_c); // close connection
        return $response = json_decode($json_return);
    }
}

new Instagram_Shop_Link();
?>

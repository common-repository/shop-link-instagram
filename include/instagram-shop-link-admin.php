<?php
ob_start();

Class ISL_ADMIN
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

        add_action( 'admin_menu', array($this,'isl_admin_menu') );
        add_action( 'admin_enqueue_scripts', array($this,'insta_feed_admin_style') );
        add_action( 'admin_enqueue_scripts', array($this,'insta_feed_admin_scripts') );
        add_action( 'wp_ajax_UpdateInstImageLink', array($this, 'UpdateInstImageLink') );
        add_action( 'wp_ajax_UpdateInstImage', array($this, 'UpdateInstImage') );

    }

    function isl_admin_menu()
    {
        add_menu_page(
            'Instagram Media',
            'Instagram Media',
            'manage_options',
            'isl-feed',
            array($this,'insta_feed_list')
        );
        add_submenu_page(
            'isl-feed',
            'Settings',
            'Settings',
            'manage_options',
            'isl-feed-setting',
            array($this,'isl_settings_page')
        );
        add_submenu_page(
            'isl-feed',
            'Shop Link',
            'Shop Link',
            'manage_options',
            'isl-feed-list',
             array($this,'insta_feed_list')
        );

        add_submenu_page(
            'isl-feed',
            'Feed Edit',
            'Feed Edit',
            'manage_options',
            'isl-feed-edit',
            array($this,'insta_feed_edit')
        );

    }



    function isl_settings_page()
    {

        $isl_settings_defaults = array(
            'isl_token' => '',
            'isl_user_id' => '',
        );

        //Save defaults in an array
        $options = wp_parse_args(get_option('isl_settings'), $isl_settings_defaults);
        update_option('isl_settings', $options);
        delete_option('shedule_cron_next');
        update_option('shedule_cron', 'shedule_cron', false);

        //Set the page variables
        $isl_token = $options['isl_token'];
        $isl_user_id = $options['isl_user_id'];
        //Check nonce before saving data


        if (!isset($_POST['isl_settings_nonce']) || !wp_verify_nonce($_POST['isl_settings_nonce'], 'insta_feed_saving_settings')) {
            //Nonce did not verify
        } else {
            // See if the user has posted us some information. If they did, this hidden field will be set to 'Y'.
            $isl_token = sanitize_text_field($_POST['isl_token']);
            $isl_user_id = sanitize_text_field($_POST['isl_user_id']);
            isset($_POST['insta_feed_preserve_settings']) ? $insta_feed_preserve_settings = sanitize_text_field($_POST['insta_feed_preserve_settings']) : $insta_feed_preserve_settings = '';
            isset($_POST['insta_feed_ajax_theme']) ? $insta_feed_ajax_theme = sanitize_text_field($_POST['insta_feed_ajax_theme']) : $insta_feed_ajax_theme = '';
            $options['isl_token'] = $isl_token;
            $options['isl_user_id'] = $isl_user_id;


            //notice
            $connection_c = curl_init(); // initializing
            $api_url = 'https://api.instagram.com/v1/users/' . $isl_user_id . '/media/recent/?access_token=' . $isl_token;
            curl_setopt($connection_c, CURLOPT_URL, $api_url); // API URL to connect
            curl_setopt($connection_c, CURLOPT_RETURNTRANSFER, 1); // return the result, do not print
            curl_setopt($connection_c, CURLOPT_TIMEOUT, 20);
            $json_return = curl_exec($connection_c); // connect and get json data
            curl_close($connection_c); // close connection
            $response = json_decode($json_return);
            update_option('isl_settings', $options);
            update_option('shedule_cron', 'shedule_cron', false);
            if ($response->meta->error_message != '') {
                echo '<div class="notice notice-error is-dismissible">
             <p>' . $response->meta->error_message . '</p></div>';
            } else {

                echo '<div class="updated"><p><strong>Settings saved.</strong></p></div>';
            }

        } ?>
        <div id="insta_feed_admin" class="wrap">
            <div id="header">
                <h1><?php _e('Instagram Feed', 'instagram-feed'); ?></h1>
            </div>

            <form name="form1" method="post" action="">
                <?php wp_nonce_field('insta_feed_saving_settings', 'isl_settings_nonce'); ?>
                <?php $sbi_active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'configure'; ?>
                <h2 class="nav-tab-wrapper">
                    <a href="?page=isl-feed-setting&amp;tab=configure"
                       class="nav-tab <?php echo $sbi_active_tab == 'configure' ? 'nav-tab-active' : ''; ?>"><?php _e('1. Configure', 'instagram-feed'); ?></a>
                    <a href="?page=isl-feed-setting&amp;tab=display"
                       class="nav-tab <?php echo $sbi_active_tab == 'display' ? 'nav-tab-active' : ''; ?>"><?php _e('2. Display Your Feed', 'instagram-feed'); ?></a>
                    <a href="?page=isl-feed-setting&amp;tab=support"
                       class="nav-tab <?php echo $sbi_active_tab == 'support' ? 'nav-tab-active' : ''; ?>"><?php _e('Support', 'instagram-feed'); ?></a>
                </h2>
                <?php if ($sbi_active_tab == 'configure') { //Start Configure tab ?>
                <table class="form-table">
                    <tbody>
                    <h3><?php _e('Configure', 'instagram-feed'); ?></h3>
                    <tr valign="top">
                        <th scope="row"><label><?php _e('Access Token', 'instagram-feed'); ?></label></th>
                        <td>
                            <input name="isl_token" id="isl_token" type="text"
                                   value="<?php esc_attr_e($isl_token, 'instagram-feed'); ?>" size="60" maxlength="60"
                                   placeholder="Click button above to get your Access Token"/>
                            &nbsp;<a class="insta_feed_tooltip_link"
                                     href="JavaScript:void(0);"><?php _e("What is this?", 'instagram-feed'); ?></a>
                            <p class="insta_feed_tooltip"><?php _e("In order to display your photos you need an Access Token from Instagram. To get yours, simply click the button above and log into Instagram. You can also use the button on <a href='http://instagram.pixelunion.net/' target='_blank'>this page</a>.", 'instagram-feed'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label> <?php _e('Show Photos From:', 'instagram-feed'); ?> </label>
                            <code class="insta_feed_shortcode"> type Eg: type=user id=123456789 </code>
                        </th>
                        <td>
                                <span>
                                    <label class="insta_feed_radio_label" for="insta_feed_type_user">User ID(s):</label>
                                    <input name="isl_user_id" id="isl_user_id" type="text"
                                           value="<?php esc_attr_e($isl_user_id, 'instagram-feed'); ?>" size="25"/>
                                    &nbsp;<a class="insta_feed_tooltip_link"
                                             href="JavaScript:void(0);"><?php _e("What is this?", 'instagram-feed'); ?></a>
                                    <p class="insta_feed_tooltip"><?php _e("These are the IDs of the Instagram accounts you want to display photos from. To get your ID simply click on the button above and log into Instagram.<br /><br />You can also display photos from other peoples Instagram accounts. To find their User ID you can use <a href='https://smashballoon.com/instagram-feed/find-instagram-user-id/' target='_blank'>this tool</a>. You can separate multiple IDs using commas.", 'instagram-feed'); ?></p><br/>
                                </span>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <?php submit_button(); ?>
            </form>
            <?php } // End Configure tab ?>

            <?php if ($sbi_active_tab == 'display') { //Start Display tab ?>
                <h3><?php _e('Display your Feed', 'instagram-feed'); ?></h3>
                <p><?php _e("Copy and paste the following shortcode directly into the page, post or widget where you'd like the feed to show up:", 'instagram-feed'); ?></p>
                <input type="text" value="[instagram-feed]" size="16" readonly="readonly" style="text-align: center;"
                       onclick="this.focus();this.select()"
                       title="<?php _e('To copy, click the field then press Ctrl + C (PC) or Cmd + C (Mac).', 'instagram-feed'); ?>"/>
            <?php } //End Display tab ?>


            <?php if ($sbi_active_tab == 'support') { //Start Support tab ?>
                <h3><?php _e('Setting up and Customizing the plugin', 'instagram-feed'); ?></h3>
                <?php
            } //End Support tab
            ?>
            <hr/>
        </div> <!-- end #sbi_admin -->

    <?php }


    function insta_feed_admin_style()
    {
        wp_register_style('insta_feed_admin_css', plugins_url('../asset/css/insta-admin.css', __FILE__), array(), SBIVER);
        wp_enqueue_style('insta_feed_admin_css');
    }


    function insta_feed_admin_scripts()
    {
        wp_enqueue_script('my_custom_admin_script', plugins_url('../asset/js/insta-admin.js', __FILE__), array(), SBIVER);
        wp_localize_script('my_custom_admin_script', 'my_custom_admin_script', array('ajaxurl' => admin_url('admin-ajax.php')));
        wp_enqueue_script('my_custom_admin_script');
    }


    function insta_feed_list()
    {

        include 'isl-list.php';
    }


    function insta_feed_edit()
    {

        $id = $_REQUEST['id'];


        global $wpdb;
        $result = $wpdb->get_results("SELECT * FROM wp_isl_feed WHERE id = $id  ORDER BY feed_instagram_id DESC");

        if ($_REQUEST['id'] == '') {
            wp_redirect('?page=isl-feed-list');
            exit;
        } ?>

        <div id="wpbody-content" aria-label="Main content" tabindex="0">
            <div class="wrap" id="profile-page">
                <h1>Update Media</h1>
                <form id="feed-update-form" name="feed-update-form" method="post">
                    <h2>Instagram Media Information</h2>
                    <table class="form-table">
                        <tbody>

                        <tr class="user-rich-editing-wrap">
                            <th scope="row">Shop Link Status</th>
                            <?php $checked = ($result[0]->feed_status == 1) ? 'checked' : 0; ?>
                            <td><label for="rich_editing"><input name="feed_status" type="checkbox" id=""
                                                                 value="1" <?php echo $checked ?>>Active</label></td>
                            <input type="hidden" name="feed_id" value="<?php echo $id; ?>">
                        </tr>

                        <tr class="user-comment-shortcuts-wrap">
                            <th scope="row">Shop Link</th>
                            <td><label for="comment_shortcuts"><input type="text" name="feed_link" id=""
                                                                      value="<?php echo $result[0]->feed_link; ?>">
                            </td>
                        </tr>

                        <tr class="user-comment-shortcuts-wrap">
                            <th scope="row">Shop Link Image</th>
                            <td>
                                <label for="comment_shortcuts">
                                    <?php // $image_src = wp_get_attachment_image_src($result['0']->feed_image_id, 'medium');
                                    $image_src = $result['0']->insta_image_link; ?>
                                    <img src="<?php echo $image_src; ?>">
                            </td>
                        </tr>

                        </tbody>
                    </table>
                    <p class="submit"><input type="button" name="submit" id="update-feed" class="button button-primary"
                                             value="Update"></p>
                </form>
            </div>
            <div class="clear"></div>
        </div>
    <?php }

    function  UpdateInstImageLink () {
        $paged_id = $_REQUEST['paged'];
        $this->db->update($this->TableName,array('feed_link' => $_REQUEST['feed_link']), array('id' => $_REQUEST['id'] ));
        if($this->db->last_error == ''){
            echo '<div class="updated"><p><strong>Update Successfully</strong></p></div>'.$paged_id;
        } else {
            echo $this->db->last_error;
        }
        exit();
    }

    function  UpdateInstImage(){

        $this->db->update($this->TableName,array('feed_status' => $_REQUEST['feed_status'],'feed_link' => $_REQUEST['feed_link']), array('id' => $_REQUEST['id'] ));
        if($this->db->last_error == ''){
            echo '<div class="updated"><p><strong>Update Successfully</strong></p></div>';

        } else {
            echo $this->db->last_error;
        }
        exit();
    }







}

new ISL_ADMIN();
?>
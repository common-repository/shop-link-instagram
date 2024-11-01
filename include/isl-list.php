<?php
$path = ABSPATH . "wp-admin/includes/class-wp-list-table.php";
/**
Checking WP_List_Table Class Exist Or Not
 **/
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class FeeListTable extends WP_List_Table
{
    private $wpdb;
    private $TableName;
    function __construct()
    {
        global $status, $page;
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->TableName = $this->wpdb->prefix . "isl_feed";
        parent::__construct(array(
            'singular' => 'ISL Feed',
            'plural' => 'ISL Feeds'
        ));
    }

    /**
     * [REQUIRED] this is how checkbox column renders
     *
     * @param $loop - row (key, value array)
     * @return HTML
     */
    function column_cb($loop)
    {
        return sprintf('<input type="checkbox" name="id[]" value="%s" />', $loop['id']);
    }
    /**
     * [REQUIRED] this is a default column renderer
     *
     * @param $loop - row (key, value array)
     * @param $column_name - string (key)
     * @return HTML
     */
    function column_default($loop, $column_name)
    {
        return $loop[$column_name];
    }
    function column_id($loop)
    {
        return $loop['id'];
    }

    function column_feed_add_date($loop)
    {
        return $loop['feed_add_date'];
    }
    function column_feed_name($loop)
    {
        return $loop['feed_name'];
    }
    function column_feed_image_id($loop)
    {
        return  '<img src="'.$loop['insta_image_link'].'" style="width:60px">';

    }
    function column_feed_link($loop)
    {
        if($loop['feed_link'] == ''){

            $html = '<span class="admin_add_shop_link button button-primary" data-id="'.$loop['id'].'"">Add Link</span>';
            $html .=  '<div style="display:none" id="admin_add_shop_link'.$loop['id'].'">';
            $html .= '<input type="text" name="feed-link-text'.$loop['id'].'" data-id="'.$loop['id'].'">';
            $html .= '<span class="admin_add_shop_link_submit button button-primary" data-id="'.$loop['id'].'">Add</span>';
            $html .= '<span class="admin_add_shop_link_close button button-primary" data-id="'.$loop['id'].'">Close</span></div>';
            return $html;
        }
        return $loop['feed_link'];
    }
    function column_feed_status($loop)
    {
        if($loop['feed_status'] == 1){
            return "Active";
        } else {
            return "Deactive";
        }

    }

    function column_feed_importby($loop)
    {
        return $loop['feed_importby'];
    }

    function column_action($loop)
    {

        $feed_status = $this->get_field_by_id($loop['id'],'feed_status');
        if($feed_status == 0) {
            $actiontext = 'Active';
            $action = 'activate';
            $jsaction = 'ConfirmActivate';
        } else {
            $actiontext = 'Deactive';
            $action = 'deactivate';
            $jsaction = 'ConfirmDeactivate';
        }



        $actions = array(
            'edit' => sprintf('<a href="admin.php?page=isl-feed-edit&action=edit&id=%s&paged='.$_REQUEST['paged'].'">%s</a>', $loop['id'], __('Edit', 'alert')),
            'delete' => sprintf('<a href="#" onclick="ConfirmDelete(%s)">%s</a>', $loop['id'], __('Delete', 'alert')),
             $$action => sprintf('<a href="#" onclick="'.$jsaction.'(%s)">%s</a>', $loop['id'], __($actiontext, 'alert')),
        );
        return sprintf('%s %s', '', $this->row_actions($actions, true));
    }

    /**
     * [REQUIRED] This method return columns to display in table
     * you can skip columns that you do not want to show
     * like content, or description
     *
     * @return array
     */
    function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox"/>', //Render a checkbox instead of text
           
            'feed_image_id' => __('Image', 'insta-feed-list'),
            'feed_link' => __('Link', 'insta-feed-list'),
            'feed_status' => __('Status', 'insta-feed-list'),
            'action' => __('action', 'alert')
       );
        return $columns;
    }

    /**
     * [OPTIONAL] This method return columns that may be used to sort table
     * all strings in array - is column names
     * notice that true on name column means that its default sort
     *
     * @return array
     */
    function no_items()
    {
        echo "No items Found.";
        $condition = isset($_REQUEST['search-submit']) ? $_REQUEST['search-submit'] : '';
        if (!empty($condition)) {
            echo "<a class='add-new-h2 button-primary' href='admin.php?page=insta-feed-list'> Back to list </a>";
        }
    }
    /**
     * [OPTIONAL] This method processes bulk actions
     * it can be outside of class
     * it can not use wp_redirect coz there is output already
     * in this example we are processing delete action
     * message about successful deletion will be shown on page in next part
     */
        function get_bulk_actions()
        {
            $actions = array(
                'delete' => 'Delete',
                'activate' => 'Activate'
            );
            return $actions;
        }

        function processBulkAction()
        {

            if ('delete' === $this->current_action()) {
                $count_id   = 0;
                $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
                if (is_array($ids)) {
                    foreach ($ids as $id) {  // UPDATE Customers SET City='Hamburg'
                        $result = $this->wpdb->query("UPDATE $this->TableName  SET flag = '0' WHERE id='$id'");
                        $count_id++;
                    }
                } else {
                    $result = $this->wpdb->query("UPDATE $this->TableName  SET flag = '0' WHERE id='$ids'");
                    $count_id++;
                }
                return "Feed%20Deleted%20:%20".$count_id;
            } elseif ('activate' === $this->current_action()) {
                $count_id   = 0;
                $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
                if (is_array($ids)) {
                    foreach ($ids as $id) {  // UPDATE Customers SET City='Hamburg'
                        $result = $this->wpdb->query("UPDATE $this->TableName  SET feed_status = '1' WHERE id='$id'");
                        $count_id++;
                    }
                } else {
                    $result = $this->wpdb->query("UPDATE $this->TableName  SET feed_status = '1' WHERE id='$ids'");
                    $count_id++;
                }
                return "Feed%20Activate%20:%20".$count_id;

            }
            elseif ('deactivate' === $this->current_action()) {
                $count_id   = 0;
                $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
                if (is_array($ids)) {
                    foreach ($ids as $id) {  // UPDATE Customers SET City='Hamburg'
                        $result = $this->wpdb->query("UPDATE $this->TableName  SET feed_status = '0' WHERE id='$id'");
                        $count_id++;
                    }
                } else {
                    $result = $this->wpdb->query("UPDATE $this->TableName  SET feed_status = '0' WHERE id='$ids'");
                    $count_id++;
                }
                return "Feed%20Deactivate%20:%20".$count_id;
            }
        }

        /**
         * [REQUIRED] This is the most important method
         *
         * It will get rows from database and prepare them to be showed in table
         */

        function prepare_items() {
            $per_page = 50;
            $columns  = $this->get_columns();
            $hidden   = array();
            $sortable = $this->get_sortable_columns();
            // here we configure table headers, defined in our methods
            $this->_column_headers = array(
                $columns,
                $hidden
            );

            $sqlResult = $this->wpdb->get_results("SELECT * FROM $this->TableName where id!='' AND flag = '1' ORDER BY feed_instagram_add_date DESC");
            $loop = UserobjectToArray($sqlResult);
            $this->items  = $loop;
            $current_page = $this->get_pagenum();
            $total_items  = count($sqlResult);
            // only ncessary because we have sample data
            $listArray = array_slice($this->items, (($current_page - 1) * $per_page), $per_page);
            $this->set_pagination_args(array(
                'total_items' => $total_items, //WE have to calculate the total number of items
                'per_page' => $per_page //WE have to determine how many items to show on a page
            ));
            $this->items = $listArray;
        }

    function get_field_by_id ( $id ,$field )
    {

        $sqlResult = $this->wpdb->get_results("SELECT $field FROM $this->TableName where id='$id'");
        return $sqlResult[0]->$field;
    }

}


function InstFeedList()
{

    $table = new FeeListTable();
    $table->prepare_items();
    $message = isset($_REQUEST['message']) ? $_REQUEST['message'] : '';
    if($_REQUEST['_dnonce']!='' || (isset($_REQUEST['action2']) && $_REQUEST['_wpnonce']!='')){
        $deleted = $table->processBulkAction();
        $message =   $deleted;
        ob_clean();
        wp_redirect("?page=" . $_REQUEST['page'] .'&paged='.$_REQUEST['paged']."&message=" . $message);
        exit();
    }
    ?>
    <div class="wrap">
        <div id="icon-users" class="icon32"><br></div>
        <h2>
            <?php
            echo __('Instagram Feed', 'insta-feed-list');
            ?>
        </h2>
        <?php
        if (!empty($message)) {
            echo '<div id="message" class="updated"><p>' . $message . '</p></div>';
        }
        ?>
        <form method="post">  <?php echo $table->display();?></form>
        <script>
            function ConfirmDelete(id)
            {
                var x = confirm("Are you sure you want to delete this Feed?");
                var paged = '<?php echo $_REQUEST['paged'];?>';
                if (x)
                    window.location.href="?page=<?php
                            echo $_REQUEST['page'];
                            ?>&action=delete&id="+id+"&paged="+paged+"&_dnonce=<?php
                            echo wp_create_nonce(basename(__FILE__));
                            ?>";
                else
                    return false;
            }


            function ConfirmActivate(id)
            {
                var paged = '<?php echo $_REQUEST['paged'];?>';
                var x = confirm("Are you sure you want to activate this Feed?");
                if (x)
                    window.location.href="?page=<?php
                            echo $_REQUEST['page'];
                            ?>&action=activate&id="+id+"&paged="+paged+"&_dnonce=<?php
                            echo wp_create_nonce(basename(__FILE__));
                            ?>";
                else
                    return false;
            }


            function ConfirmDeactivate(id)
            {
                var paged = '<?php echo $_REQUEST['paged'];?>';
                var x = confirm("Are you sure you want to deactivate this Feed?");
                if (x)
                    window.location.href="?page=<?php
                            echo $_REQUEST['page'];
                            ?>&action=deactivate&id="+id+"&paged="+paged+"&_dnonce=<?php
                            echo wp_create_nonce(basename(__FILE__));
                            ?>";
                else
                    return false;
            }


            function ConfirmClone(id)
            {
                var x = confirm("Are you sure you want to Feed this loop?");
                if (x)
                    window.location.href="?page=alert-loop&action=clone&id="+id;
                else
                    return false;
            }
        </script>
        <br class="clear">
    </div>
    <?php




}

function UserobjectToArray($d){
    if (is_object($d)) {
        // Gets the properties of the given object
        // with get_object_vars function
        $d = get_object_vars($d);
    }
    if (is_array($d)) {
        /*
        * Return array converted to object
        * Using __FUNCTION__ (Magic constant)
        * for recursive call
        */
        return array_map(__FUNCTION__, $d);
    }
    else {
        // Return array
        return $d;
    }
}


InstFeedList();
?>

<style type="text/css">

    .isl-ad-error{ color: red;}

    .column-feed_link {
        width: 25% !important;
    }
    span.admin_add_shop_link_submit.button.button-primary { margin: 10px 10px 10px 0px;}
    span.admin_add_shop_link_close.button.button-primary { margin: 10px 10px 10px 0px;}
    span.admin_add_shop_link.button.button-primary {
        margin-left: 20px;
        margin-top: 17px;
    }

</style>

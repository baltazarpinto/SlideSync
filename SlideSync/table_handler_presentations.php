<?php
function table_handler_presentations()
{
    global $wpdb;
	
    $table = new Table_presentations_List_Table();
	 if( isset($_GET['s']) ){
		$table->prepare_items(sanitize_text_field( $_GET['s'] ));
	} else {
		$table->prepare_items();
	}


    $message = '';
    if ('delete' === $table->current_action()) {
        if (isset($_REQUEST['id']) && is_array($_REQUEST['id'])) {$ids=$_REQUEST['id'];} else { $ids=array($_REQUEST['id']); }
		$message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Nº of deleted records: %d','slidesync'), count($ids)) . '</p></div>';
	}
?>
<div class="wrap">
    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <h2><?php echo __( 'Presentations', 'slidesync' );?> <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=slidesync-presentation');?>"><?php echo __( 'Add', 'slidesync' );?></a></h2>
    <?php echo $message; ?>

    <form id="slidesync-table" method="GET">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
		<?php $table->search_box(__( 'Search', 'slidesync' ), 'search_id'); ?>
        <?php $table->display() ?>
    </form>

</div>
<?php
} 

if (!class_exists('WP_List_Table')) { require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php'); }

class Table_presentations_List_Table extends WP_List_Table
{
    function __construct()
    {
        global $status, $page;
        parent::__construct(array(
            'singular' => __( 'Presentation', 'slidesync' ),
            'plural' => __( 'Presentations', 'slidesync' ),
        ));
    }
    function column_default($item, $column_name)
    {
        return $item[$column_name];
    }

    function column_name($item)
    {
		$actions = array(
			'edit' => sprintf('<a href="?page=slidesync-presentation&id=%s">%s</a>', $item['id'], __( 'Edit', 'slidesync')),
			'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __( 'Delete', 'slidesync')),
		);
		

        return sprintf('%s %s',
            $item['name'],
            $this->row_actions($actions)
        );
    }

    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

	 function column_shortcode_display($item)
    {
        return sprintf(
            '[SlideSync_display id=%s]',
            $item['id']
        );
    }
	function column_shortcode_video($item)
    {
        return sprintf(
            '[SlideSync_video id=%s]',
            $item['id']
        );
    }
	
    function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'name' => __( 'Name', 'slidesync'),
			'shortcode_display' => __( 'Shortcode for display', 'slidesync'),
			'shortcode_video' => __( 'Shortcode for video', 'slidesync'),
        );
        return $columns;
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
            'name' => array('name', true),
			'id' => array('id', false),
        );
        return $sortable_columns;
    }

    function get_bulk_actions()
    {
        $actions = array(
            'delete' => __( 'Delete', 'slidesync')
        );
        return $actions;
    }

    function process_bulk_action()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'presentation'; 

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) $ids = implode(',', $ids);

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
    }

    function prepare_items($search='')
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'presentation'; 

        $per_page = 10;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->process_bulk_action();

		$where='';
		if ($search!='') {
			$where="where name like '%".$search."%' or id like '%".$search."%'";
		}
		
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name $where");

        $paged = isset($_REQUEST['paged']) ? ($per_page * max(0, intval($_REQUEST['paged']) - 1)) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'name';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';

        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name $where ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);

        $this->set_pagination_args(array(
            'total_items' => $total_items, 
            'per_page' => $per_page, 
            'total_pages' => ceil($total_items / $per_page) 
        ));
    }
	
	function usort_reorder($a, $b)
    {
        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'name';

        // If no order, default to asc
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';

        // Determine sort order
        $result = strcmp($a[$orderby], $b[$orderby]);

        // Send final sort direction to usort
        return ($order === 'asc') ? $result : -$result;
    }
	
}
?>
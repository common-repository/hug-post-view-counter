<?php
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once('class-wp-list-table.php');
}

class HUG_Post_View_Table extends WP_List_Table
{
    /**
     * constructor
     */
    function __construct()
    {
        parent::__construct(array(
            'singular'  => __('wp_list_text_link'),
            'plural'    => __('wp_list_test_links'),
            'ajax'      => false
        ));
    }
    
    /**
     * add content before or after table list content
     * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
     */
    function extra_tablenav($which)
    {
        if($which == 'top'){
            echo esc_html('Top 100 most viewed articles');
        }
        if($which == 'bottom'){
            //echo 'Sherlock Holmes after Hug Table';
        }
    }

    /**
     * Define the columns of table. Must be overridden.
     * @return array $columns, the array of columns to use with the table
     */
    function get_columns(){
        return $columns = array(
            'col_post_id'       => __('ID'),
            'col_post_title'    => __('Title'),
            'col_post_category' => __('Category'),
            'col_post_view'     => __('Views')
        );
    }
    function usort_reorder( $a, $b ) {
        // If no sort, default to title
        $orderby = sanitize_text_field($_GET['orderby']);
        $orderby = (!empty($orderby)) ? $orderby : 'col_post_view';
        // If no order, default to asc
        $order = sanitize_text_field($_GET['order']);
        $order = (!empty($order)) ? $order : 'desc';
        // Determine sort order
        if(!is_numeric($a[$orderby])){
            $result = strcmp($a[$orderby], $b[$orderby]);
        }            
        else{
            if(intval($a[$orderby]) < intval($b[$orderby])){
                $result = -1;
            }
            else if(intval($a[$orderby]) > intval($b[$orderby])){
                $result = 1;
            }
            else{
                $result = 0;
            }
        }
            
        // Send final sort direction to usort
        return ( $order === 'asc' ) ? $result : -$result;
      }
    /**
     * Decide which columns can be sort
     * @return array $sortable, the array of columns that can be sorted by the user
     */
    public function get_sortable_columns(){
        return $sortable_columns = array(
            'col_post_id'       => array(__('col_post_id'),false),
            'col_post_category' => array(__('col_post_category'),false),
            'col_post_view'     => array(__('col_post_view'),false)
        );
    }

    /**
     * Prepare the table content to display
     */
    function prepare_items()
    {
        $data = array();
        $postType = get_post_types('','names');
        $args = array(
            'post_type'          => $postType,
            'meta_query'         => array(
                array(
                    'key'          => 'hug_post_views',
                    'value'        => 1,
                    'compare'      => '>='   
                )
                ),
                'post_status'     => 'publish',
                'numberposts'     => 100,
                'orderby'         => 'hug_post_views',
                'order'           => 'DESC'
        );
        $posts = get_posts($args);
        $customTaxonomies = get_taxonomies(array(
            'public'    => true,
            '_builtin'  => false
        ),'names');
        array_push($customTaxonomies,'category');
        foreach($posts as $post){
            $categories = '';
            $taxo = get_the_terms($post->ID,$customTaxonomies);
            if ( ! empty( $taxo ) && ! is_wp_error( $taxo ) && $taxo ) {
                $categories = implode(',',wp_list_pluck( $taxo, 'name' ));
            }
            array_push($data,array(
                'col_post_id'       => $post->ID,
                'col_post_title'    => $post->post_title,
                'col_post_category' => $categories,
                'col_post_view'     => get_post_meta($post->ID,'hug_post_views',true)
            ));
            
        }
        //pagination
        usort( $data, array( &$this, 'usort_reorder' ) );
        $post_per_page = 10;
        $current_page = $this->get_pagenum();
        $total_item = count($data);
        $display_data = array_slice($data,($current_page - 1)*$post_per_page,$post_per_page);
        $this->set_pagination_args(array(
            'total_items'   => $total_item,
            'per_page'      => $post_per_page
        ));
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $this->items = $display_data;
    }

    /**
     * Display the rows of records in the table
     * @return string, echo the markup of the rows
     */
    function column_default( $item, $column_name ) {
        switch( $column_name ) { 
            case 'col_post_id':
            case 'col_post_title':
            case 'col_post_category':
            case 'col_post_view':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
        }
      }
}
//$hugPostViewTable = new HUG_Post_View_Table();
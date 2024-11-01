<?php
/*
Plugin Name: WP e-Commerce Popular Products
Plugin URI: http://mywebsiteadvisor.com/tools/wordpress-plugins/wp-e-commerce-popular-products/
Description: Popular Products Widget and Shortcode for WP e-Commerce
Version: 1.0
Author: MyWebsiteAdvisor
Author URI: http://MyWebsiteAdvisor.com
*/



class WP_Widget_Popular_Products extends WP_Widget {

	/**
	 * Widget Constuctor
	 */
	function WP_Widget_Popular_Products() {

		$widget_ops = array(
			'classname'   => 'widget_wpsc_popular_products',
			'description' => __( 'Popular Products Widget', 'wpsc' )
		);

		$this->WP_Widget( 'wpsc_popular_products', __( 'Popular Products', 'wpsc' ), $widget_ops );

	}

	/**
	 * Widget Output
	 *
	 * @param $args (array)
	 * @param $instance (array) Widget values.
	 *
	 * @todo Add individual capability checks for each menu item rather than just manage_options.
	 */
	function widget( $args, $instance ) {

		global $wpdb, $table_prefix;

		extract( $args );

		echo $before_widget;
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Popular Products', 'wpsc' ) : $instance['title'] );
		if ( $title )
			echo $before_title . $title . $after_title;

		wpsc_popular( $args, $instance );
		echo $after_widget;

	}

	/**
	 * Update Widget
	 *
	 * @param $new_instance (array) New widget values.
	 * @param $old_instance (array) Old widget values.
	 *
	 * @return (array) New values.
	 */
	function update( $new_instance, $old_instance ) {

		$instance = $old_instance;
		$instance['title']            = strip_tags( $new_instance['title'] );
		$instance['number']           = (int) $new_instance['number'];
		$instance['show_thumbnails']  = (bool) $new_instance['show_thumbnails'];
		$instance['show_description'] = (bool) $new_instance['show_description'];
		$instance['show_old_price']   = (bool) $new_instance['show_old_price'];
		$instance['show_discount']    = (bool) $new_instance['show_discount'];

		return $instance;

	}

	/**
	 * Widget Options Form
	 *
	 * @param $instance (array) Widget values.
	 */
	function form( $instance ) {

		global $wpdb;

		// Defaults
		$instance = wp_parse_args( (array) $instance, array(
			'title'            => '',
			'show_description' => false,
			'show_thumbnails'  => false,
			'number'           => 5,
			'show_old_price'   => false,
			'show_discount'    => false,
		) );

		// Values
		$title = esc_attr( $instance['title'] );
		$number = (int) $instance['number'];
		$show_thumbnails  = (bool) $instance['show_thumbnails'];
		$show_description = (bool) $instance['show_description'];
		$show_discount    = (bool) $instance['show_discount'];
		$show_old_price   = (bool) $instance['show_old_price'];

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'wpsc' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of products to show:', 'wpsc' ); ?></label>
			<input type="text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" value="<?php echo $number; ?>" size="3" />
		</p>
		<p>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_description' ); ?>" name="<?php echo $this->get_field_name( 'show_description' ); ?>" <?php checked( $show_description ); ?>>
			<label for="<?php echo $this->get_field_id( 'show_description' ); ?>"><?php _e( 'Show Description', 'wpsc' ); ?></label><br />
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_thumbnails' ); ?>" name="<?php echo $this->get_field_name( 'show_thumbnails' ); ?>" <?php checked( $show_thumbnails ); ?>>
			<label for="<?php echo $this->get_field_id( 'show_thumbnails' ); ?>"><?php _e( 'Show Thumbnails', 'wpsc' ); ?></label><br />
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_old_price' ); ?>" name="<?php echo $this->get_field_name( 'show_old_price' ); ?>" <?php checked( $show_old_price, '1' ); ?>>
			<label for="<?php echo $this->get_field_id( 'show_old_price' ); ?>"><?php _e( 'Show Old Price', 'wpsc' ); ?></label><br />
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_discount' ); ?>" name="<?php echo $this->get_field_name( 'show_discount' ); ?>" <?php checked( $show_discount, '1' ); ?>>
			<label for="<?php echo $this->get_field_id( 'show_discount' ); ?>"><?php _e( 'Show Discount', 'wpsc' ); ?></label>
		</p>
<?php
	}

}

add_action( 'widgets_init', '_wpsc_action_register_popular_widget' );
function _wpsc_action_register_popular_widget() {
	register_widget( 'WP_Widget_Popular_Products' );
}


function _wpsc_filter_popular_widget_where( $where ) {
	global $wpdb;

	// find variations that have sales price, then get a list of parent IDs
	$sql = "
		SELECT DISTINCT(p.post_parent)
		FROM {$wpdb->posts} AS p
		INNER JOIN {$wpdb->postmeta} AS pm
			ON p.ID = pm.post_id AND pm.meta_key = '_wpsc_special_price' AND pm.meta_value > 0
		WHERE p.post_parent != 0 AND p.post_status IN ('publish', 'inherit')";


	$parent_ids = $wpdb->get_col( $sql );

	if ( $parent_ids ) {
		$parent_ids = array_map( 'absint', $parent_ids );
		$where .= " AND ({$wpdb->posts}.ID IN (" . implode( ', ', $parent_ids ) . ") OR pm.meta_value > 0) ";
	} else {
		$where .= " AND pm.meta_value > 0 ";
	}

	return $where;
}

function _wpsc_filter_popular_widget_join( $join ) {
	global $wpdb;
	//$join .= " INNER JOIN {$wpdb->postmeta} AS pm ON {$wpdb->posts}.ID = pm.post_id AND pm.meta_key = '_wpsc_special_price' ";
	$join .= " LEFT JOIN {$wpdb->postmeta} AS pm ON {$wpdb->posts}.ID = pm.post_id ";	
	
	return $join;
}

function _wpsc_filter_popular_widget_group( $group ) {
	global $wpdb;

	$group .= "  wp_posts.ID ";
	
	return $group;
}


/**
 * Products Widget content function
 *
 * Displays the latest products.
 */

function wpsc_popular( $args = null, $instance ) {

	global $wpdb;

	$args = wp_parse_args( (array) $args, array( 'number' => 10 ) );

	$siteurl = get_option( 'siteurl' );

	if ( ! $number = (int) $instance['number'] )
		$number = 10;

	$show_thumbnails  = isset( $instance['show_thumbnails']  ) ? (bool) $instance['show_thumbnails']  : false;
	$show_description = isset( $instance['show_description'] ) ? (bool) $instance['show_description'] : false;
	$show_discount    = isset( $instance['show_discount']    ) ? (bool) $instance['show_discount']    : false;
	$show_old_price   = isset( $instance['show_old_price']   ) ? (bool) $instance['show_old_price']   : false;
	
	$include_cats   = isset( $instance['include_cats']   ) ? (bool) $instance['include_cats']   : false;
	$exclude_cats   = isset( $instance['exclude_cats']   ) ? (bool) $instance['exclude_cats']   : false;

	$popular = new WP_E_Commerce_Popular_Products;

	$args = array(
		'post_type'           		=> 'wpsc-product',
		'ignore_sticky_posts'	=> 1,
		'post_status'         		=> 'publish',
		'post_parent'         		=> 0,
		'posts_per_page'      	=> $number,
		'no_found_rows'      	=> true,
		'orderby'					=> 'post__in',
		'post__in' 					=> $popular->get_popular_product_ids()
	);

	add_filter( 'posts_join', '_wpsc_filter_popular_widget_join' );
	add_filter( 'posts_where', '_wpsc_filter_popular_widget_where' );
	add_filter( 'posts_groupby', '_wpsc_filter_popular_widget_group' );
		
	$special_products = new WP_Query( $args );
	
	//echo "<pre>";
	//var_dump($special_products);
	//echo "</pre>";

	
	remove_filter( 'posts_join', '_wpsc_filter_popular_widget_join' );
	remove_filter( 'posts_where', '_wpsc_filter_popular_widget_where' );
	remove_filter( 'posts_groupby', '_wpsc_filter_popular_widget_group' );
	
	if ( ! $special_products->post_count ) {
		echo apply_filters( 'wpsc_popular_widget_no_items_message', __( 'We currently have no items on special.', 'wpsc' ) );
		return;
	}

	$product_ids = array();

	$popular_products_data = $popular->get_popular_products();
		
	while ( $special_products->have_posts() ) :
		$special_products->the_post();
		
		$id = wpsc_the_product_id();
		$count = $popular_products_data[$id];
			
		?>
		<h4><strong><a class="wpsc_product_title" href="<?php echo wpsc_product_url( wpsc_the_product_id(), false ); ?>"><?php echo wpsc_the_product_title(); ?></a> (<?php echo $count; ?>)</strong></h4>

		<?php if ( $show_description ): ?>
			<div class="wpsc-special-description">
				<?php echo wpsc_the_product_description(); ?>
			</div>
		<?php endif; // close show description

		if ( ! in_array( wpsc_the_product_id(), $product_ids ) ) :
			$product_ids[] = wpsc_the_product_id();
			$has_children = wpsc_product_has_children( get_the_ID() );
			if( $show_thumbnails ):
				if ( wpsc_the_product_thumbnail() ) : ?>
					<a rel="<?php echo str_replace(array(" ", '"',"'", '&quot;','&#039;'), array("_", "", "", "",''), wpsc_the_product_title()); ?>" href="<?php echo wpsc_the_product_permalink(); ?>">
						<img class="product_image" id="product_image_<?php echo wpsc_the_product_id(); ?>" alt="<?php echo wpsc_the_product_title(); ?>" title="<?php echo wpsc_the_product_title(); ?>" src="<?php echo wpsc_the_product_thumbnail(); ?>"/></a>
				<?php else : ?>
					<a href="<?php echo wpsc_the_product_permalink(); ?>">
						<img class="no-image" id="product_image_<?php echo wpsc_the_product_id(); ?>" alt="No Image" title="<?php echo wpsc_the_product_title(); ?>" src="<?php echo WPSC_URL; ?>/wpsc-theme/wpsc-images/noimage.png" width="<?php esc_attr_e( get_option('product_image_width') ); ?>" height="<?php esc_attr_e( get_option('product_image_height') ); ?>" /></a>
				<?php endif; ?>
				<br />
			<?php endif; // close show thumbnails ?>
			<div id="special_product_price_<?php echo wpsc_the_product_id(); ?>">
				<?php
					wpsc_the_product_price_display(
						array(
							'output_old_price' => $show_old_price,
							'output_you_save'  => $show_discount,
						)
					);
				?>
			</div><br />
			<?php
		endif;
	endwhile;
	wp_reset_postdata();
}




class WP_E_Commerce_Popular_Products {

	private $plugin_name = "";
	
	
	/**
	 * Initialize class
	 */
	public function __construct(){
		
		$this->plugin_name = basename(dirname( __FILE__ ));
		
		// add links for plugin help, donations,...
		add_filter('plugin_row_meta', array(&$this, 'add_plugin_links'), 10, 2);
		
		// add plugin "Widgets" action on plugin list
		add_action('plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'add_plugin_actions'));
		
		//register popular_products shortcode
		add_shortcode ( 'popular_products', array($this, 'popular_products_shortcode') ); 
		
	}



	/**
	 * Add "Widgets" action on installed plugin list
	 */
	public function add_plugin_actions($links) {
		array_unshift($links, '<a href="widgets.php">' . __('Widgets') . '</a>');
		
		return $links;
	}
	

	/**
	 * Add links on installed plugin list
	 */
	public function add_plugin_links($links, $file) {
		if($file == plugin_basename( __FILE__ )) {
			$upgrade_url = 'http://mywebsiteadvisor.com/tools/wordpress-plugins/' . $this->plugin_name . '/';
			$links[] = '<a href="'.$upgrade_url.'" target="_blank" title="Click Here to Upgrade this Plugin!">Upgrade Plugin</a>';
			
			$install_url = admin_url()."plugins.php?page=MyWebsiteAdvisor";
			$links[] = '<a href="'.$install_url.'" target="_blank" title="Click Here to Install More Free Plugins!">More Plugins</a>';
			
			$rate_url = 'http://wordpress.org/support/view/plugin-reviews/' . $this->plugin_name . '?rate=5#postform';
			$links[] = '<a href="'.$rate_url.'" target="_blank" title="Click Here to Rate and Review this Plugin on WordPress.org">Rate This Plugin</a>';
		}
		
		return $links;
	}
	

	public function get_popular_products(){
		
		global $wpdb;
	
		//query standard products
		$product_query = "
			SELECT prodid as ID, SUM(quantity) as Count 
			FROM {$wpdb->prefix}wpsc_cart_contents 
			GROUP BY prodid 
			ORDER BY SUM(quantity) DESC 
			LIMIT 100";
		
		//query product variations
		$variation_query = "
			SELECT post_parent as ID, SUM(quantity) as Count 
			FROM {$wpdb->prefix}wpsc_cart_contents  
			LEFT JOIN  {$wpdb->posts} ON ({$wpdb->prefix}wpsc_cart_contents.prodid = {$wpdb->posts}.ID) 
			WHERE post_parent != 0  
			GROUP BY post_parent 
			ORDER BY SUM(quantity) DESC 
			LIMIT 100";
			
		//combine the results of the 2 queries and sort 
		$union_query = "
			SELECT * FROM ($product_query) t1 
			UNION ALL 
			SELECT * FROM ($variation_query) t2 
			ORDER BY Count DESC  ";
	
		
		$popular_products = $wpdb -> get_results ( $union_query, ARRAY_A);
		
		$results = array();
		
		if(isset($popular_products) && count($popular_products) > 0){
			foreach ( $popular_products as $item) {
				$key = $item['ID'];
				$value = $item['Count'];
				
				$results[$key] = $value;
			}
		}
		
		return $results;
		
	}
	
	
	
	
	public function get_popular_product_ids(){

		$popular_products = $this->get_popular_products();
		
		$results = array();
		
		if(isset($popular_products) && count($popular_products) > 0){
			foreach ( $popular_products as $key => $val ) {
				$results[] = $key;
			}
		}
		
		return $results;
		
	}

	
	
	public function popular_products_shortcode ( $atts ) {
	
		global $wpdb;
		
		extract( shortcode_atts( array(
			'limit' 						=> '5',
			'show_old_price' 		=> false,
			'show_savings' 		=> false,
			'show_description'	=> false,
			'show_thumbnails'	=> false,
		), $atts ) );
		
		$popular = new WP_E_Commerce_Popular_Products;

		$args = array(
			'post_type'           		=> 'wpsc-product',
			'ignore_sticky_posts'	=> 1,
			'post_status'         		=> 'publish',
			'post_parent'         		=> 0,
			'posts_per_page'      	=> $limit,
			'no_found_rows'      	=> true,
			'orderby'					=> 'post__in',
			'post__in' 					=> $popular->get_popular_product_ids()
		);
		
	
		add_filter( 'posts_join', '_wpsc_filter_popular_widget_join' );
		add_filter( 'posts_where', '_wpsc_filter_popular_widget_where' );
		add_filter( 'posts_groupby', '_wpsc_filter_popular_widget_group' );
			
		$popular_products = new WP_Query( $args );
	
		remove_filter( 'posts_join', '_wpsc_filter_popular_widget_join' );
		remove_filter( 'posts_where', '_wpsc_filter_popular_widget_where' );
		remove_filter( 'posts_groupby', '_wpsc_filter_popular_widget_group' );
		
		
		echo "<style> 
			.shortocde_widget h4 { margin: 0px;  } 
			.shortocde_widget p { margin: 0px;  } 
		</style>";
		
		echo "<div class='shortocde_widget'>";
		
		$popular_products_data = $this->get_popular_products();
		
		
		while ( $popular_products->have_posts() ) {
			$popular_products->the_post();
			
			$id = wpsc_the_product_id();
			$count = $popular_products_data[$id];

			 ?>
			<p>
				<h4><strong><a class="wpsc_product_title" href="<?php echo wpsc_product_url( wpsc_the_product_id(), false ); ?>"><?php echo wpsc_the_product_title(); ?></a> (<?php echo $count; ?>)</strong></h4>
                
                <?php if ( $show_description ): ?>
                    <div class="wpsc-special-description">
                        <?php echo wpsc_the_product_description(); ?>
                    </div>
                <?php endif; // close show description ?>
                
                
				<?php if( $show_thumbnails ):
					if ( wpsc_the_product_thumbnail() ) : ?>
						<a rel="<?php echo str_replace(array(" ", '"',"'", '&quot;','&#039;'), array("_", "", "", "",''), wpsc_the_product_title()); ?>" href="<?php echo wpsc_the_product_permalink(); ?>">
							<img class="product_image" id="product_image_<?php echo wpsc_the_product_id(); ?>" alt="<?php echo wpsc_the_product_title(); ?>" title="<?php echo wpsc_the_product_title(); ?>" src="<?php echo wpsc_the_product_thumbnail(); ?>"/></a>
					<?php else : ?>
						<a href="<?php echo wpsc_the_product_permalink(); ?>">
							<img class="no-image" id="product_image_<?php echo wpsc_the_product_id(); ?>" alt="No Image" title="<?php echo wpsc_the_product_title(); ?>" src="<?php echo WPSC_URL; ?>/wpsc-theme/wpsc-images/noimage.png" width="<?php esc_attr_e( get_option('product_image_width') ); ?>" height="<?php esc_attr_e( get_option('product_image_height') ); ?>" /></a>
					<?php endif; ?>
					<br />
				<?php endif; // close show thumbnails ?>
                

                <div id="special_product_price_<?php echo wpsc_the_product_id(); ?>">
                    <?php
                        wpsc_the_product_price_display(
                            array(
                                'output_old_price' => $show_old_price,
                                'output_you_save'  => $show_savings,
                            )
                        );
						?>
				</div>
					
			</p>
			<br />
		<?php 
		}
		
		echo "</div>";
	
	}


}



$wp_e_commerce_popular_products = new WP_E_Commerce_Popular_Products;

?>
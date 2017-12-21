<?php
//When plugins gets activated, used to store tables
function mi7_activate() {
	global $wpdb;
	global $mi7_version;
	$mi7CategoryTable = $wpdb->prefix.'mi7_category';
	$mi7ItemTable = $wpdb->prefix.'mi7_item';
	$charset_collate = $wpdb->get_charset_collate();

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
	$sql = "CREATE TABLE IF NOT EXISTS $mi7CategoryTable (
		id int(11) NOT NULL AUTO_INCREMENT,
		category_name varchar(255) NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";
	
	dbDelta( $sql );
	
	//TO-DO :: Add foreign key relation
	$sql = "CREATE TABLE IF NOT EXISTS $mi7ItemTable (
		id int(11) NOT NULL AUTO_INCREMENT,
		item_category int(11) NOT NULL,
		item_name varchar(255) NOT NULL,
		item_day varchar(10) NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";
		
	dbDelta( $sql );

	add_option('mi7_version', $mi7_version);
}

//deactivation action
function mi7_deactivate() {
}

//uninstall action
function mi7_uninstall() {
	//TO-DO :: Remove tables & option
}

//on reactivate updates db
function mi7_update_check() {
}

//on initialize including js,css and slugs
function mi7_initialize() {
	add_action('admin_menu','mi7_admin_menu_actions');
	mi7_register_assets();
	add_action('admin_enqueue_scripts', 'mi7_enqueue_backend_scripts');
	mi7_load_ajax_scripts();
	add_shortcode('MENU-ITEMS-7','mi7_render_menu_items_on_frontend');
	add_action('wp_enqueue_scripts','mi7_enqueue_frontend_scripts');
}

function mi7_admin_menu_actions() {
	add_menu_page(__( 'Menu Items', 'menu-items-7' ),
		__( 'Menu Items', 'menu-items-7' ),
		'manage_options', 'mi7_settings',
		'mi7_render_menu_items_on_backend', 'dashicons-email');
}

function mi7_register_assets() {	
	wp_register_style('mi7-style', MI7_URL . 'assets/css/style.css');
	wp_register_style('mi7-front-style', MI7_URL . 'assets/css/front.css');
	wp_register_style('mi7-ui', MI7_URL . 'assets/css/jquery-ui-1.9.0.custom.css');
	wp_register_style('mi7-ui-combobox', MI7_URL . 'assets/css/jquery.combobox.css', array('mi7-ui'));	
	wp_register_script('mi7-setting', MI7_URL . 'assets/js/setting.js', array('jquery'));	
	wp_register_script('mi7-ui-theme', '//code.jquery.com/ui/1.10.1/jquery-ui.min.js', array('jquery','jquery-ui-core'));
	wp_register_script('mi7-combobox', MI7_URL . 'assets/js/jquery.combobox.js', array('mi7-ui-theme'));
	wp_register_script('mi7-form', MI7_URL . 'assets/js/jquery.form.js', array('jquery'));
	wp_register_script('mi7-validate', MI7_URL . 'assets/js/jquery.validate.js', array('jquery'));
	wp_localize_script('mi7-setting', 'MI7AJAX', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'menu_item' => __('Menu Item','menu-items-7'),
		'saveitemmsg' => __('Item saved successfully.', 'menu-items-7')
	));
}

function mi7_enqueue_frontend_scripts() {
	wp_enqueue_style('mi7-front-style');
}

function mi7_enqueue_backend_scripts() {
	wp_enqueue_style('mi7-style');
	wp_enqueue_style('mi7-ui');
	wp_enqueue_style('mi7-ui-combobox');
	wp_enqueue_script('mi7-setting');
	wp_enqueue_script('mi7-combobox');
	wp_enqueue_script('mi7-ui-theme');
	wp_enqueue_script('mi7-form');
	wp_enqueue_script('mi7-validate');
}

function mi7_load_ajax_scripts() {
	add_action("wp_ajax_mi7_load_item", "mi7_load_item"); //nopriv removed
	add_action("wp_ajax_mi7_get_categories", "mi7_get_categories"); //nopriv removed
	add_action("wp_ajax_mi7_get_items", "mi7_get_items"); //nopriv removed
	add_action("wp_ajax_mi7_save_item", "mi7_save_item"); //nopriv removed
	add_action("wp_ajax_mi7_render_items_on_backend", "mi7_render_items"); //nopriv removed
	add_action("wp_ajax_mi7_delete_item", "mi7_delete_item"); //nopriv removed
}

function mi7_load_item() {
	$output = '';
	$selectedDay = $_GET['selectedDay'];
	$output .= '<form id="load_item_form" action="'.admin_url( 'admin-ajax.php' ).'" method="post">';
	$output .= '<div class="load_item_wrapper">';
		$output .= '<div class="load_item_field_wrapper">';
			$output .= '<label for="category">'.__('Category','menu-items-7').'</label>';
			$output .= '<input type="text" id="category" name="category" class="mi7_category_combo required" value="" placeholder="'.__('select/add category','menu-items-7').'" />';
		$output .= '</div>';
		$output .= '<div class="load_item_field_wrapper">';
			$output .= '<label for="item">'.__('Item','menu-items-7').'</label>';
			$output .= '<input type="text" id="item" name="item" class="mi7_item_combo required" value="" placeholder="'.__('select/add item','menu-items-7').'" />';
		$output .= '</div>';
		$output .= '<div class="load_item_field_wrapper">';
			$output .= '<button class="load_item_button">'.__('Save','menu-items-7').'</button>';
		$output .= '</div>';
	$output .= '</div>';
	$output .= '<input type="hidden" name="item_day" id="item_day" value="'.$selectedDay.'">';
	$output .= '<div id="load_item_resp" class="msg_wrapper"></div>';
	$output .= '</form>';
	
	print json_encode(array(
		'output' => $output
	));
	exit;
}

function mi7_fetch_matched_categories($categoryTerms) {
	global $wpdb;
	return $wpdb->get_results("SELECT category_name FROM ".$wpdb->prefix."mi7_category WHERE category_name LIKE '%".$categoryTerms."%' ORDER BY category_name ASC LIMIT 0,5");
}

function mi7_get_categories() {
	$categoryTerms = $_GET['term'];
	$categories = mi7_fetch_matched_categories($categoryTerms);
	$categoriesResponse = array();
	foreach($categories as $category) {
		$categoriesResponse[] = $category->category_name;
	}	
	print json_encode($categoriesResponse); //It is necessary to print the result
	exit;
}

function mi7_get_category_id_by_name($name) {
	global $wpdb;
	return $wpdb->get_row("SELECT id FROM ".$wpdb->prefix."mi7_category WHERE category_name = '".$name."'");
}

function mi7_get_matched_items($cat='',$item_day='') {
	global $wpdb;
	$sql = "SELECT item_name FROM ".$wpdb->prefix."mi7_item WHERE 1=1";
	if(isset($cat) && !empty($cat)) {
		$categoryId = mi7_get_category_id_by_name($cat);
		if($categoryId->id) {
			$sql .= " AND item_category = '".$categoryId->id."'";
		}
	}
	if(isset($item_day) && !empty($item_day)) {
		$sql .= " AND item_day = '".$item_day."'";
	}
	$sql .= " ORDER BY item_name";
	return $wpdb->get_results($sql);
}

function mi7_get_items() {
	$category = $_GET['cat'];
	$item_day = $_GET['item_day'];
	$items = mi7_get_matched_items($category,$item_day);
	$itemsResponse = array();
	foreach($items as $item) {
		$itemsResponse[] = $item->item_name;
	}	
	print json_encode($itemsResponse); //It is necessary to print the result
	exit;
}

function mi7_check_category_exists($selectedCat) {
	global $wpdb;
	$categoryId = $wpdb->get_row("SELECT id FROM ".$wpdb->prefix."mi7_category WHERE category_name = '".$selectedCat."'");
	return $categoryId->id?$categoryId->id:false;
}

function mi7_delete_item() {
	global $wpdb;
	$error = true;
	if(isset($_GET['item_id']) && !empty($_GET['item_id'])) {
		if(isset($_GET['item_day']) && !empty($_GET['item_day'])) {
			$sql = "DELETE FROM ".$wpdb->prefix."mi7_item WHERE id = '".$_GET['item_id']."' AND item_day = '".strtoupper($_GET['item_day'])."'";
			//print $sql;
			$wpdb->query($sql);
			$error = false;
		}
	}
	print(json_encode(array('error' => $error)));
	exit;
}

function mi7_render_items($item_day) {
	global $wpdb;
	if(isset($_GET['is_ajax']) && !empty($_GET['is_ajax'])) {
		if(isset($_GET['item_day']) && !empty($_GET['item_day'])) {
			$item_day = $_GET['item_day'];
		}
	}
	if(isset($item_day) && !empty($item_day)) {
		$sql = "SELECT category.category_name, item.id AS item_id, item.item_name, item.item_category, item.item_day FROM ".$wpdb->prefix."mi7_item AS item LEFT JOIN ".$wpdb->prefix."mi7_category AS category ON category.id = item.item_category WHERE item.item_day = '".$item_day."' ORDER BY category.category_name";
		//print $sql;
		$results = $wpdb->get_results($sql);
		$items = array();
		$itemNames = array();
		if(count($results) > 0) {
			$j = 1;
			for($i=0;$i<count($results);$i++) {
				$items[$results[$i]->item_category] = array();
				$items[$results[$i]->item_category]['category_id'] = $results[$i]->item_category;
				$items[$results[$i]->item_category]['name'] = $results[$i]->category_name;
				$items[$results[$i]->item_category]['total_items'] = $j;
				$itemNames[$results[$i]->item_category][$j]['name'] = $results[$i]->item_name;
				$itemNames[$results[$i]->item_category][$j]['id'] = $results[$i]->item_id;
				$itemNames[$results[$i]->item_category][$j]['item_day'] = $results[$i]->item_day;
				if($results[$i]->item_category == $results[$i+1]->item_category) {
					$j++;
				} else {
					$j=1;
				}				
			}
		}
		$output = array(
			'categories' => $items,
			'items' => $itemNames
		);
		if(isset($_GET['is_ajax']) && !empty($_GET['is_ajax'])) {
			$itemHtml = mi7_backend_template($output);
			print(json_encode(array('html' => $itemHtml)));
			exit;
		}
		if(count($items) > 0) {
			return $output;
		}
		return false;
	}
}

function mi7_render_menu_items_on_backend() {
	return mi7_settings(true);
}

function mi7_backend_template($itemRenders) {
	$output = '';
	if(count($itemRenders['categories']) > 0) {
		foreach($itemRenders['categories'] as $category) {
			$output .= '<h4>'.$category['name'].'</h4>';
			$output .= '<ul class="menu_items">';
			foreach($itemRenders['items'] as $itemKey => $itemValue) {
				if($itemKey == $category['category_id']) {
					foreach($itemValue as $value) {
						$output .= '<li>'.$value['name'].'<span><a href="javascipt:;" class="item-remove" id="'.strtolower($value['item_day']).'-'.$value['id'].'">'.__('X','menu-items-7').'</a></span></li>';
					}
				}
			}
			$output .= '</ul>';
		}
	}
	return $output;
}

function mi7_render_menu_items_on_frontend() {
	return mi7_settings(false);
}

function mi7_frontend_template($itemRenders) {
	$output = '';
	if(count($itemRenders['categories']) > 0) {
		foreach($itemRenders['categories'] as $category) {
			$output .= '<h4>'.$category['name'].'</h4>';
			$output .= '<ul class="menu_items">';
			foreach($itemRenders['items'] as $itemKey => $itemValue) {
				if($itemKey == $category['category_id']) {
					foreach($itemValue as $value) {
						$output .= '<li>'.$value['name'].'</li>';
					}
				}
			}
			$output .= '</ul>';
		}
	}
	return $output;
}

function mi7_save_item() {
	global $wpdb;
	$error = true;
	if(isset($_POST['category']) && !empty($_POST['category'])) {
		$selectedCategoryId = mi7_check_category_exists($_POST['category']);
		if(!$selectedCategoryId) {
			$wpdb->insert($wpdb->prefix.'mi7_category', array(
				'category_name' => $_POST['category']
			));
			$selectedCategoryId = $wpdb->insert_id;
		}
		
		if((isset($_POST['item']) && !empty($_POST['item']))
			&& (isset($_POST['item_day']) && !empty($_POST['item_day']))) {
			$error = false;
			$items = array(
				'item_category' => $selectedCategoryId,
				'item_name' => $_POST['item'],
				'item_day' => $_POST['item_day']
			);
			$wpdb->query("DELETE FROM ".$wpdb->prefix."mi7_item WHERE item_category = '".$_GET['delete']."' AND item_name = '".$_POST['item']."' AND item_day = '".$_POST['item_day']."'");
			$wpdb->insert($wpdb->prefix.'mi7_item', $items);
		}		
	}
	print json_encode(array(
		'error' => $error
	));
	exit;
}

function mi7_settings($backend = true) {
	$output = '';
	$output .= '<div class="mi7_wrapper">';
		$output .= '<h2>'.__('Menu','menu-items-7').'</h2>';
		$output .= '<ul class="mi7_days_wrapper">';
		
			$output .= '<li>';
				$output .= '<h3>'.__('Monday','menu-items-7').'</h3>';
				$output .= '<div class="mi7_col_wrapper" id="monday-item">';
					$output .= '<div class="mi7_col">';
						$itemRenders = mi7_render_items('MONDAY');
						if($backend) {
							$output .= mi7_backend_template($itemRenders);
						} else {
							$output .= mi7_frontend_template($itemRenders);
						}
					$output .= '</div>';
					if($backend) {
						$output .= '<a class="mi7_add" href="javascript:;">'.__('+','menu-items-7').'</a>';
					}
				$output .= '</div>';
			$output .= '</li>';
			
			$output .= '<li>';
				$output .= '<h3>'.__('Tuesday','menu-items-7').'</h3>';
				$output .= '<div class="mi7_col_wrapper" id="tuesday-item">';
					$output .= '<div class="mi7_col">';
						$itemRenders = mi7_render_items('TUESDAY');
						if($backend) {
							$output .= mi7_backend_template($itemRenders);
						} else {
							$output .= mi7_frontend_template($itemRenders);
						}
					$output .= '</div>';
					if($backend) {
						$output .= '<a class="mi7_add" href="javascript:;">'.__('+','menu-items-7').'</a>';
					}
				$output .= '</div>';
			$output .= '</li>';
			
			$output .= '<li>';
				$output .= '<h3>'.__('Wednesday','menu-items-7').'</h3>';
				$output .= '<div class="mi7_col_wrapper" id="wednesday-item">';
					$output .= '<div class="mi7_col">';
						$itemRenders = mi7_render_items('WEDNESDAY');
						if($backend) {
							$output .= mi7_backend_template($itemRenders);
						} else {
							$output .= mi7_frontend_template($itemRenders);
						}
					$output .= '</div>';
					if($backend) {
						$output .= '<a class="mi7_add" href="javascript:;">'.__('+','menu-items-7').'</a>';
					}
				$output .= '</div>';
			$output .= '</li>';
			
			$output .= '<li>';
				$output .= '<h3>'.__('Thursday','menu-items-7').'</h3>';
				$output .= '<div class="mi7_col_wrapper" id="thursday-item">';
					$output .= '<div class="mi7_col">';
						$itemRenders = mi7_render_items('THURSDAY');
						if($backend) {
							$output .= mi7_backend_template($itemRenders);
						} else {
							$output .= mi7_frontend_template($itemRenders);
						}
					$output .= '</div>';
					if($backend) {
						$output .= '<a class="mi7_add" href="javascript:;">'.__('+','menu-items-7').'</a>';
					}
				$output .= '</div>';
			$output .= '</li>';
			
			$output .= '<li>';
				$output .= '<h3>'.__('Friday','menu-items-7').'</h3>';
				$output .= '<div class="mi7_col_wrapper" id="friday-item">';
					$output .= '<div class="mi7_col">';
						$itemRenders = mi7_render_items('FRIDAY');
						if($backend) {
							$output .= mi7_backend_template($itemRenders);
						} else {
							$output .= mi7_frontend_template($itemRenders);
						}
					$output .= '</div>';
					if($backend) {
						$output .= '<a class="mi7_add" href="javascript:;">'.__('+','menu-items-7').'</a>';
					}
				$output .= '</div>';
			$output .= '</li>';
			
			$output .= '<li>';
				$output .= '<h3>'.__('Saturday','menu-items-7').'</h3>';
				$output .= '<div class="mi7_col_wrapper" id="saturday-item">';
					$output .= '<div class="mi7_col">';
						$itemRenders = mi7_render_items('SATURDAY');
						if($backend) {
							$output .= mi7_backend_template($itemRenders);
						} else {
							$output .= mi7_frontend_template($itemRenders);
						}
					$output .= '</div>';
					if($backend) {
						$output .= '<a class="mi7_add" href="javascript:;">'.__('+','menu-items-7').'</a>';
					}
				$output .= '</div>';
			$output .= '</li>';
			
			$output .= '<li>';
				$output .= '<h3>'.__('Sunday','menu-items-7').'</h3>';
				$output .= '<div class="mi7_col_wrapper" id="sunday-item">';
					$output .= '<div class="mi7_col">';
						$itemRenders = mi7_render_items('SUNDAY');
						if($backend) {
							$output .= mi7_backend_template($itemRenders);
						} else {
							$output .= mi7_frontend_template($itemRenders);
						}
					$output .= '</div>';
					if($backend) {
						$output .= '<a class="mi7_add" href="javascript:;">'.__('+','menu-items-7').'</a>';
					}
				$output .= '</div>';
			$output .= '</li>';
			
		$output .= '</ul>';
	$output .= '</div>';
	
	$defaultCategories = array();
	$defaultItems = array();
	
	$output .= '<script type="text/javascript">';
		$output .= 'var default_categories = '.json_encode($defaultCategories).';';
		$output .= 'var default_items = '.json_encode($defaultItems).';';
	$output .= '</script>';
	
	$output .= '<div id="load_item"></div>';
	
	print $output;
	exit;
}
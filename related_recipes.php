<?php
/*
Plugin Name: Related Recipes
Plugin URI: https://github.com/nmorse/related_recipes.git
Description: A simple 'related posts' plugin that lets you select related posts manually.
Version: 1.4.1
Author: nmorse
Author URI: https://github.com/nmorse/related_recipes.git
Text Domain: related recipes
Domain Path: /lang/


Copyright 2010-2012  Matthias Siegel  (email: matthias.siegel@gmail.com)
Copyright 2013       Marcel Pol       (email: marcel@timelord.nl)
Copyright 2014       NMorse       (email: n8morse@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/



if (!class_exists('RelatedRecipes')) :
	class RelatedRecipes {

		// Constructor
		public function __construct() {

			// Set some helpful constants
			$this->defineConstants();

			// Register hook to save the related posts when saving the post
			add_action('save_post', array(&$this, 'save'));

			// Start the plugin
			add_action('admin_menu', array(&$this, 'start'));

			// Adds an option page for the plugin
			add_action('admin_menu', array(&$this, 'related_recipes_options'));
		}


		// Defines a few static helper values we might need
		protected function defineConstants() {

			define('RELATED_RECIPES_VERSION', '1.4.1.1');
			define('RELATED_RECIPES_HOME', 'https://github.com/nmorse/related_recipes.git');
			define('RELATED_RECIPES_FILE', plugin_basename(dirname(__FILE__)));
			define('RELATED_RECIPES_ABSPATH', str_replace('\\', '/', WP_PLUGIN_DIR . '/' . plugin_basename(dirname(__FILE__))));
			define('RELATED_RECIPES_URLPATH', WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__)));
		}


		// Main function
		public function start() {

			// Load the scripts
			add_action('admin_print_scripts', array(&$this, 'loadScripts'));

			// Load the CSS
			add_action('admin_print_styles', array(&$this, 'loadCSS'));

			// Adds a meta box for related posts to the edit screen of each post type in WordPress
			$related_recipes_show = get_option('related_recipes_show');
			$related_recipes_show = json_decode( $related_recipes_show );
			if ( empty( $related_recipes_show ) ) {
				$related_recipes_show = array();
				$related_recipes_show[] = 'any';
			} else {
				foreach ( $related_recipes_show as $post_type ) {
					if ( $post_type == 'any' ) {
						$related_recipes_show = array();
						$related_recipes_show[] = 'any';
						break;
					}
				}
			}
			if ( $related_recipes_show[0] == 'any' ) {
				foreach (get_post_types() as $post_type) :
					add_meta_box($post_type . '-related-recipes-box', __('Related Recipes', 'related_recipes' ), array(&$this, 'displayMetaBox'), $post_type, 'normal', 'high');
				endforeach;
			} else {
				foreach ($related_recipes_show as $post_type) :
					add_meta_box($post_type . '-related-recipes-box', __('Related Recipes', 'related_recipes' ), array(&$this, 'displayMetaBox'), $post_type, 'normal', 'high');
				endforeach;
			}

		}


		// Load Javascript
		public function loadScripts() {

			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-sortable');
			wp_enqueue_script('related-recipes-scripts', RELATED_RECIPES_URLPATH .'/scripts.js', false, RELATED_RECIPES_VERSION);
		}


		// Load CSS
		public function loadCSS() {

			wp_enqueue_style('related-recipes-css', RELATED_RECIPES_URLPATH .'/styles.css', false, RELATED_RECIPES_VERSION, 'all');
		}


		// Save related posts when saving the post
		public function save($id) {

			global $wpdb;

			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

			if (!isset($_POST['related-recipes']) || empty($_POST['related-recipes'])) :
				delete_post_meta($id, 'related_recipes');
			else :
				update_post_meta($id, 'related_recipes', $_POST['related-recipes']);
			endif;
		}


		// Creates the output on the post screen
		public function displayMetaBox() {

			global $post;

			$post_id = $post->ID;
			echo '<div id="related-recipes">';

			// Get related posts if existing
			$related_recipes = get_post_meta($post_id, 'related_recipes', true);

			if (!empty($related_recipes)) :
				foreach($related_recipes as $r) :
					$args=array(
						'name' => $r,
						'post_type' => 'post',
						'post_status' => 'publish',
						'posts_per_page' => 1
					);
					$p = get_posts( $args );
					echo '
						<div class="related-recipes" id="related-recipes-' . $r . '">
							<input type="hidden" name="related-recipes[]" value="' . $r . '">
							<span class="related-recipes-title">' . $r . " " . $p[0]->post_title . '</span>
							<a href="#">' . __('Delete', 'related_recipes' ) . '</a>
						</div>';
				endforeach;
			endif;

			echo '
				</div>
				<p>
					<select class="related-recipes-select" name="related-recipes-select">
						<option value="0">' . __('Select', 'related_recipes' ) . '</option>';

			$related_recipes_list = get_option('related_recipes_list');
			$related_recipes_list = json_decode( $related_recipes_list );
			if ( empty( $related_recipes_list ) ) {
				$related_recipes_list = array();
				$related_recipes_list[] = 'any';
			} else {
				foreach ( $related_recipes_list as $post_type ) {
					if ( $post_type == 'any' ) {
						$related_recipes_list = array();
						$related_recipes_list[] = 'any';
						break;
					}
				}
			}

            //$query = new WP_Query( 'pagename=the slug' );

			$query = array(
				'nopaging' => true,
				'post__not_in' => array($post_id),
				'post_status' => 'publish',
				'posts_per_page' => -1,
				'post_type' => $related_recipes_list,
				'orderby' => 'title',
				'order' => 'ASC'
			);

			$p = new WP_Query($query);

			$count = count($p->posts);
			$counter = 1;
			foreach ($p->posts as $thePost) {
				if ( is_int( $counter / 5000 ) ) {
					echo '
						</select>
					</p>
					<p>
						<select class="related-recipes-select" name="related-recipes-select">
							<option value="0">' . __('Select', 'related_recipes' ) . '</option>';
				}
				?>
				<option value="<?php
					echo $thePost->post_name; ?>"><?php echo
					$thePost->post_title.' ('.ucfirst(get_post_type($thePost->ID)).')'; ?></option>
				<?php
				$counter++;
			}

			wp_reset_query();
			wp_reset_postdata();

			echo '
					</select>
				</p>
				<p>' .
					__('Select related posts from the list. Drag selected ones to change order.', 'related_recipes' )
				. '</p>';
		}


		// The frontend function that is used to display the related post list
		public function show($id, $return = false) {

			global $wpdb;

			if (!empty($id) && is_numeric($id)) :
				$related_recipes = get_post_meta($id, 'related_recipes', true);

				if (!empty($related_recipes)) :
					$rel = array();
					foreach ($related_recipes as $r) :
						$args=array(
							'name' => $r,
							'post_type' => 'post',
							'post_status' => 'publish',
							'posts_per_page' => 1
						);
						$p = get_posts( $args );
						$rel[] = $p[0];
					endforeach;

					// If value should be returned as array, return it
					if ($return) :
						return $rel;

					// Otherwise return a formatted list
					else :
						$list = '<ul class="related-recipes">';
						foreach ($rel as $r) :
							$list .= '<li><a href="' . get_permalink($r->ID) . '">' . $r->post_title . '</a></li>';
						endforeach;
						$list .= '</ul>';

						return $list;
					endif;
				else :
					return false;
				endif;
			else :
				return __('Invalid post ID specified', 'related_recipes' );
			endif;
		}

		// Adds an option page to Settings.
		function related_recipes_options() {
			add_options_page(__('Related Recipes', 'related_recipes'), __('Related Recipes', 'related_recipes'), 'manage_options', 'related.php', array(&$this, 'related_recipes_options_page'));
		}
		function related_recipes_options_page() {
			// Handle the POST
			if ( isset( $_POST['form'] ) ) {
				if ( function_exists('current_user_can') && !current_user_can('manage_options') ) {
					die(__('sour&#8217; uh?'));
				}
				if ( $_POST['form'] == 'show' ) {
					$showkeys = array();
					foreach ($_POST as $key => $value) {
						if ( $key == 'form' ) {
							continue;
						}
						$showkeys[] = str_replace('show_', '', $key);
					}
					$showkeys = json_encode($showkeys);
					update_option( 'related_recipes_show', $showkeys );
				} else if ( $_POST['form'] == 'list' ) {
					$listkeys = array();
					foreach ($_POST as $key => $value) {
						if ( $key == 'form' ) {
							continue;
						}
						$listkeys[] = str_replace('list_', '', $key);
					}
					$listkeys = json_encode($listkeys);
					update_option( 'related_recipes_list', $listkeys );
				}
			}

			// Make a form to submit

			echo '<div id="poststuff" class="metabox-holder">
					<div class="widget related-recipes-widget">
						<h3 class="widget-top">' . __('Post Types to show the Related Recipes form on.', 'related_recipes') . '</h3>';

			$related_recipes_show = get_option('related_recipes_show');
			$related_recipes_show = json_decode( $related_recipes_show );
			$any = '';
			if ( empty( $related_recipes_show ) ) {
				$related_recipes_show = array();
				$related_recipes_show[] = 'any';
				$any = 'checked="checked';
			} else {
				foreach ( $related_recipes_show as $key ) {
					if ( $key == 'any' ) {
						$any = 'checked="checked"';
					}
				}
			}
			?>

			<div class="misc-pub-section">
			<p><?php _e('If Any is selected, it will show on any Post Type. If none are selected, Any will still apply.', 'related_recipes'); ?></p>
			<form name="related_recipes_options_page_show" action="" method="POST">
				<ul>
				<li><label for="show_any">
					<input name="show_any" type="checkbox" id="show_any" <?php echo $any; ?>  />
					any
				</label></li>
				<?php
				$post_types = get_post_types( '', 'names' );
				$checked = '';
				foreach ( $post_types as $post_type ) {
					if ( $post_type == "revision" || $post_type == "nav_menu_item" ) {
						continue;
					}

					foreach ( $related_recipes_show as $key ) {
						if ( $key == $post_type ) {
							$checked = 'checked="checked"';
						}
					}
					?>
					<li><label for="show_<?php echo $post_type; ?>">
						<input name="show_<?php echo $post_type; ?>" type="checkbox" id="show_<?php echo $post_type; ?>" <?php echo $checked; ?>  />
						<?php echo $post_type; ?>
					</label></li>
					<?php
					$checked = ''; // reset
				}
				?>
				<input type="hidden" class="form" value="show" name="form" />
				<li><input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Submit' ); ?>"/></li>
				</ul>
			</form>
			</div>
			</div>
			<?php

			echo '<div class="widget related-recipes-widget">
						<h3 class="widget-top">' . __('Post Types to list on the Related Recipes forms.', 'related_recipes') . '</h3>';
			$any = ''; // reset
			$related_recipes_list = get_option('related_recipes_list');
			$related_recipes_list = json_decode( $related_recipes_list );
			if ( empty( $related_recipes_list ) ) {
				$related_recipes_list = array();
				$related_recipes_list[] = 'any';
				$any = 'checked';
			} else {
				foreach ( $related_recipes_list as $key ) {
					if ( $key == 'any' ) {
						$any = 'checked="checked"';
					}
				}
			}
			?>

			<div class="misc-pub-section">
			<p><?php _e('If Any is selected, it will list any Post Type. If none are selected, it will still list any Post Type.', 'related_recipes'); ?></p>
			<form name="related_recipes_options_page_listed" action="" method="POST">
				<ul>
				<li><label for="list_any">
					<input name="list_any" type="checkbox" id="list_any" <?php echo $any; ?>  />
					any
				</label></li>
				<?php
				$post_types = get_post_types( '', 'names' );
				foreach ( $post_types as $post_type ) {
					if ( $post_type == "revision" || $post_type == "nav_menu_item" ) {
						continue;
					}

					foreach ( $related_recipes_list as $key ) {
						if ( $key == $post_type ) {
							$checked = 'checked="checked"';
						}
					}
					?>
					<li><label for="list_<?php echo $post_type; ?>">
						<input name="list_<?php echo $post_type; ?>" type="checkbox" id="list_<?php echo $post_type; ?>" <?php echo $checked; ?>  />
						<?php echo $post_type; ?>
					</label></li>
					<?php
					$checked = ''; // reset
				}
				?>
				<input type="hidden" class="form" value="list" name="form" />
				<li><input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Submit' ); ?>"/></li>
				</ul>
			</form>
			</div>
			</div></div>
			<?php
		}
	}

endif;

/* Include widget */
include( 'related-recipes-widget.php' );

/*
 * related_init
 * Function called at initialisation.
 * - Loads language files
 * - Make an instance of RelatedRecipes()
 */

function related_recipes_init() {
 	load_plugin_textdomain('related_recipes', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/');

	// Start the plugin
	global $related_recipes;
	$related_recipes = new RelatedRecipes();
}
add_action('plugins_loaded', 'related_recipes_init');


?>

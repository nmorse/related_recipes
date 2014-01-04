<?php

class Related_Recipes_Widget extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'related_recipes_widget', 'description' => __('Related Recipes Widget','related_recipes') );
		parent::__construct('related_recipes_widget', 'Related Recipes Widget', $widget_ops);
		$this->alt_option_name = 'related_recipes_widget';

		add_action( 'save_post', array(&$this, 'flush_widget_cache') );
		add_action( 'deleted_post', array(&$this, 'flush_widget_cache') );
		add_action( 'switch_theme', array(&$this, 'flush_widget_cache') );
	}

	function widget($args, $instance) {
		$cache = wp_cache_get('related_recipes_widget', 'widget');

		if ( !is_array($cache) )
			$cache = array();

		if ( ! isset( $args['widget_id'] ) )
			$args['widget_id'] = $this->id;

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return;
		}

		ob_start();
		extract($args);

		$title	= apply_filters('widget_title', empty($instance['title']) ? 'Related Recipes' : $instance['title'], $instance, $this->id_base);

		if ( is_singular() ) {
			global $related_recipes;
			$related_recipes_str = $related_recipes->show( get_the_ID() );

			if ( ! empty( $related_recipes_str ) ) {
				echo $before_widget;
				if ( $title ) echo $before_title . $title . $after_title;

				echo $related_recipes_str;

				echo $after_widget;
			}
		}

		$cache[$args['widget_id']] = ob_get_flush();
		wp_cache_set('related_recipes_widget', $cache, 'widget');
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']	= strip_tags($new_instance['title']);
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['related_recipes_widget']) )
			delete_option('related_recipes_widget');

		return $instance;
	}

	function flush_widget_cache() {
		wp_cache_delete('related_recipes_widget', 'widget');
	}

	function form( $instance ) {
    	/*
    	 * Set Default Value for widget form
    	 */
    	$default_value	=	array( "title"=> "Related Recipes" );
    	$instance		=	wp_parse_args( (array) $instance, $default_value );
        $path           =   $instance['path'];
		$title = isset($instance['title']) ? esc_attr($instance['title']) : ''; ?>
		<p><label for="<?php echo $path; ?>"><?php _e('Title', 'related_recipes'); ?></label>
		<input class="widefat" id="<?php echo $path; ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p><?php
	}
}


function related_recipes_widget() {
	register_widget('Related_Recipes_Widget');
}
add_action('widgets_init', 'related_recipes_widget' );

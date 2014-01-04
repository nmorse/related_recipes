<?php

class Related_Articles_Widget extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'related_articles_widget', 'description' => __('Related Articles Widget','related_articles') );
		parent::__construct('related_articles_widget', 'Related Articles Widget', $widget_ops);
		$this->alt_option_name = 'related_articles_widget';

		add_action( 'save_post', array(&$this, 'flush_widget_cache') );
		add_action( 'deleted_post', array(&$this, 'flush_widget_cache') );
		add_action( 'switch_theme', array(&$this, 'flush_widget_cache') );
	}

	function widget($args, $instance) {
		$cache = wp_cache_get('related_articles_widget', 'widget');

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

		$title	= apply_filters('widget_title', empty($instance['title']) ? 'Related Articles' : $instance['title'], $instance, $this->id_base);

		if ( is_singular() ) {
			global $related_articles;
			$related_articles_str = $related_articles->show( get_the_ID() );

			if ( ! empty( $related_articles_str ) ) {
				echo $before_widget;
				if ( $title ) echo $before_title . $title . $after_title;

				echo $related_articles_str;

				echo $after_widget;
			}
		}

		$cache[$args['widget_id']] = ob_get_flush();
		wp_cache_set('related_articles_widget', $cache, 'widget');
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']	= strip_tags($new_instance['title']);
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['related_articles_widget']) )
			delete_option('related_articles_widget');

		return $instance;
	}

	function flush_widget_cache() {
		wp_cache_delete('related_articles_widget', 'widget');
	}

	function form( $instance ) {
    	/*
    	 * Set Default Value for widget form
    	 */
    	$default_value	=	array( "title"=> "Related Articles" );
    	$instance		=	wp_parse_args( (array) $instance, $default_value );
        $path           =   $instance['path'];
		$title = isset($instance['title']) ? esc_attr($instance['title']) : ''; ?>
		<p><label for="<?php echo $path; ?>"><?php _e('Title', 'related_articles'); ?></label>
		<input class="widefat" id="<?php echo $path; ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p><?php
	}
}


function related_articles_widget() {
	register_widget('Related_Articles_Widget');
}
add_action('widgets_init', 'related_articles_widget' );

<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// --------------------------------------------------------------
// Page Builder Framework: Remove meta boxes from sidebar
// --------------------------------------------------------------

// Remove meta boxes from sidebar
function prefix_remove_pbf_metaboxes_funerals() {
  remove_meta_box( 'wpbf' , 'funeral-notice' , 'side' );
  remove_meta_box( 'wpbf_header' , 'funeral-notice' , 'side' );
  remove_meta_box( 'wpbf_sidebar' , 'funeral-notice' , 'side' );
  remove_meta_box( 'tagsdiv-funeral-location' , 'funeral-notice' , 'side' );
  remove_meta_box( 'generate_layout_options_meta_box' , 'funeral-notice' , 'side' );
  
}
add_action( 'admin_head' , 'prefix_remove_pbf_metaboxes_funerals' );


// Allow private pages in themer frontend
add_action( 'pre_get_posts', function( $query ) {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		$query->set( 'post_status', array( 'private', 'publish' ) );
	}
});

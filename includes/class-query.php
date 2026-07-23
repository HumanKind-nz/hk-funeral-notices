<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * BB Post Grids Query Sorting
 *
 * @since 1.3.0 changes
 */
add_filter( 'fl_builder_loop_query_args', function( $query_args ) {
	$today = current_time('Ymd');
	$id = $query_args['settings']->id;

	switch ( $id ) {
		case 'weave-funeral-archive':
		$query_args['meta_query'] = array(
			'relation' => 'AND',
			array(
				'key' => 'wfn_details_group_funeral_date',
				'value' => $today,
				'type' => 'DATE',
				'compare' => '<' // Past dates
			),
			array(
				'key' => 'wfn_details_group_funeral_time',
				'compare' => 'EXISTS' // Ensure time exists
			)
		);
		
		$query_args['orderby'] = array(
			'wfn_details_group_funeral_date' => 'DESC',
			'wfn_details_group_funeral_time' => 'DESC'
		);
		
		break;

		case 'weave-future-funerals':
		$query_args['meta_query'] = array(
			'relation' => 'AND',
			array(
				'key' => 'wfn_details_group_funeral_date',
				'value' => $today,
				'type' => 'DATE',
				'compare' => '>=' // Future dates
			),
			array(
				'key' => 'wfn_details_group_funeral_time',
				'compare' => 'EXISTS' // Ensure time exists
			)
		);
		
		$query_args['orderby'] = array(
			'wfn_details_group_funeral_date' => 'ASC',
			'wfn_details_group_funeral_time' => 'ASC'
		);
		
		break;

		case 'weave-last-x-days':
		$no_of_days = get_field('hkfn_number_of_days_on_main_page', 'options');
		// Ensure $no_of_days is valid and sanitized
		$query_args['meta_query'] = array(
			'relation' => 'AND',
			array(
				'key' => 'wfn_details_group_funeral_date',
				'value' => date('Ymd', strtotime('-' . $no_of_days . ' days')),
				'type' => 'date_picker',
				'compare' => '>='
			),
			array(
				'key' => 'wfn_details_group_funeral_date',
				'value' => $today,
				'type' => 'date_picker',
				'compare' => '<'
			)
		);
		
		$query_args['orderby'] = array(
			'wfn_details_group_funeral_date' => 'DESC',
			'wfn_details_group_funeral_time' => 'DESC' // Higher times first for the same date
		);
		
		break;
	}

	return $query_args;
});

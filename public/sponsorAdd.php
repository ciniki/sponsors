<?php
//
// Description
// -----------
// This method will add a new sponsor for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to add the sposnor to.
// title:			The title/name of the sponsor.
// permalink:		(optional) The permalink to the sponsor, otherwise title will be made into permalink.
// level_id:		(optional) Either the level_id or level must be specified.  If level_id > 0 it 
//					will be used, otherwise level will be added.
// level:			(optional) The level name.
// sequence:		The position in the list of sponsors for that level.
// webflags:		The flags for display on the website.
//
//					0x01 - Hidden (do not show on website)
//					0x02 - 
//					0x04 - 
//					0x08 - 
//
// url:				(optional) The URL for the sponsor website.
// description:		(optional) The description for the event.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_sponsors_sponsorAdd(&$ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'title'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Title'), 
		'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'), 
		'level_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Level'), 
		'level'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Level'), 
		'sequence'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'name'=>'Sequence'), 
		'webflags'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Options'), 
		'url'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'URL'), 
		'primary_image_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Image'), 
		'excerpt'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Description'), 
		'content'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Content'), 
		'notes'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Notes'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'checkAccess');
	$ac = ciniki_sponsors_checkAccess($ciniki, $args['business_id'], 'ciniki.sponsors.sponsorAdd');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}
	$modules = $ac['modules'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');

	//
	// Create permalink if doesn't already exist
	//
	if( !isset($args['permalink']) || $args['permalink'] == '' ) {
		$args['permalink'] = ciniki_core_makePermalink($ciniki, $args['title']);
	}

	//
	// Check the permalink doesn't already exist
	//
	$strsql = "SELECT id FROM ciniki_sponsors "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' " 
		. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'sponsor');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( $rc['num_rows'] > 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1686', 'msg'=>'You already have a sponsor with this title, please choose another title.'));
	}

	//
	// Start the transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sponsors');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Check if level was specified
	//
	if( isset($modules['ciniki.sponsors']['flags']) && ($modules['ciniki.sponsors']['flags']&0x01) > 0 ) {
		if( !isset($args['level_id']) || $args['level_id'] == '' || $args['level_id'] == '0' ) {
			if( isset($args['level']) && $args['level'] != '' ) {
				//
				// Check if level exists
				//
				$strsql = "SELECT id "
					. "FROM ciniki_sponsor_levels "
					. "WHERE name = '" . ciniki_core_dbQuote($ciniki, $args['level']) . "' "
					. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "";
				$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'level');
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sponsors');
					return $rc;
				}
				if( isset($rc['level']) && isset($rc['level']['id']) ) {
					$args['level_id'] = $rc['level']['id'];
				} elseif( $rc['num_rows'] > 1 ) {
					$args['level_id'] = $rc['rows']['0']['id'];
				} else {
					//
					// Add level
					//
					$largs = array('name'=>$args['level'],
						'permalink'=>ciniki_core_makePermalink($ciniki, $args['level']),
						'sequence'=>1,
						);
					$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.sponsors.level', $largs, 0x04);
					if( $rc['stat'] != 'ok' ) {	
						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sponsors');
						return $rc;
					}
					$args['level_id'] = $rc['id'];
				}
			} else {
				// None specified, set to zero
				$args['level_id'] = 0;
			}
		}
	} else {
		// Levels aren't enabled, set to zero
		$args['level_id'] = 0;
	}

	//
	// Add the sponsor to the database
	//
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.sponsors.sponsor', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sponsors');
		return $rc;
	}
	$sponsor_id = $rc['id'];

	//
	// Commit the transaction
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.sponsors');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sponsors');
		return $rc;
	}
	
	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'sponsors');

	return array('stat'=>'ok', 'id'=>$sponsor_id);
}
?>

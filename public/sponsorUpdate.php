<?php
//
// Description
// ===========
// This method will update an sponsor in the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the sponsor is attached to.
// name:			(optional) The new name of the sponsor.
// url:				(optional) The new URL for the sponsor website.
// description:		(optional) The new description for the sponsor.
// start_date:		(optional) The new date the sponsor starts.  
// end_date:		(optional) The new date the sponsor ends, if it's longer than one day.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sponsors_sponsorUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'sponsor_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Sponsor'), 
		'title'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Title'), 
		'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'), 
		'level_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Level'), 
		'level'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Level'), 
		'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sequence'), 
		'webflags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'), 
		'url'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'URL'), 
		'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'), 
		'excerpt'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'), 
		'content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Content'), 
		'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'checkAccess');
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['business_id'], 'ciniki.sponsors.sponsorUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the existing sponsor details
	//
	$strsql = "SELECT uuid FROM ciniki_sponsors "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['sponsor_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'sponsor');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['sponsor']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1689', 'msg'=>'Sponsor not found'));
	}
	$sponsor = $rc['sponsor'];

	if( isset($args['title']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
		$args['permalink'] = ciniki_core_makePermalink($ciniki, $args['title']);

		//
		// Make sure the permalink is unique
		//
		$strsql = "SELECT id, title, permalink FROM ciniki_sponsors "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
			. "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['sponsor_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'sponsor');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( $rc['num_rows'] > 0 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1690', 'msg'=>'You already have an sponsor with this title, please choose another title'));
		}
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
	}

	//
	// Update the sponsor in the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sponsors.sponsor', $args['sponsor_id'], $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sponsors');
		return $rc;
	}

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

	return array('stat'=>'ok');
}
?>

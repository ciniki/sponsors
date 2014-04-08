<?php
//
// Description
// -----------
// This method will return the list of sponsors for a business.  
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get sponsors for.
//
// Returns
// -------
//
function ciniki_sponsors_sponsorList($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'level_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Level'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'checkAccess');
    $ac = ciniki_sponsors_checkAccess($ciniki, $args['business_id'], 'ciniki.sponsors.sponsorList');
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }
	$modules = $ac['modules'];

	//
	// Load the sponsors
	//
	$strsql = "SELECT id, title  "
		. "FROM ciniki_sponsors "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	if( ($modules['ciniki.sponsors']['flags']&0x01) > 0 
		&& isset($args['level_id']) && $args['level_id'] != '' ) {
		$strsql .= "AND level_id = '" . ciniki_core_dbQuote($ciniki, $args['level_id']) . "' ";
	}
	$strsql	.= "ORDER BY ciniki_sponsors.sequence DESC, ciniki_sponsors.title "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sponsors', array(
		array('container'=>'sponsors', 'fname'=>'id', 'name'=>'sponsor',
			'fields'=>array('id', 'title')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['sponsors']) ) {
		return array('stat'=>'ok', 'sponsors'=>array());
	}
	$sponsors = $rc['sponsors'];

	return array('stat'=>'ok', 'sponsors'=>$sponsors);
}
?>

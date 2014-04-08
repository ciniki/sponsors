<?php
//
// Description
// -----------
// This method will delete a sponsorship level from the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business the level is attached to.
// level_id:			The ID of the level_id to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_sponsors_levelDelete(&$ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'level_id'=>array('required'=>'yes', 'default'=>'', 'blank'=>'yes', 'name'=>'Level'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'checkAccess');
	$ac = ciniki_sponsors_checkAccess($ciniki, $args['business_id'], 'ciniki.sponsors.levelDelete');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// Check if any sponsors still in this level
	//
	$strsql = "SELECT 'sponsors', COUNT(*) "
		. "FROM ciniki_sponsors "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND level_id = '" . ciniki_core_dbQuote($ciniki, $args['level_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
	$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sponsors', 'num');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if isset($rc['num']['sponsors']) && $rc['num']['sponsors'] > 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1692', 'msg'=>'All sponsors must be removed from the sponsorship level before it can be removed.'));
	}

	//
	// Get the uuid of the level to be deleted
	//
	$strsql = "SELECT uuid FROM ciniki_sponsor_levels "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['level_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'level');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['level']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1627', 'msg'=>'The sponsorship level does not exist.'));
	}
	$level_uuid = $rc['level']['uuid'];

	//
	// Remove the level
	//
	return ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.sponsors.level', 
		$args['level_id'], $level_uuid);
}
?>

<?php
//
// Description
// ===========
// This method will return all the information about an sponsorship level.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the sponsor is attached to.
// level_id:		The ID of the sponsor to get the details for.
// 
// Returns
// -------
//
function ciniki_sponsors_levelGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'level_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Level'), 
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
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['business_id'], 'ciniki.sponsors.levelGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	$strsql = "SELECT ciniki_sponsor_levels.id, "
		. "ciniki_sponsor_levels.name, "
		. "ciniki_sponsor_levels.permalink, "
		. "ciniki_sponsor_levels.sequence, "
		. "ciniki_sponsor_levels.size "
		. "FROM ciniki_sponsor_levels "
		. "WHERE ciniki_sponsor_levels.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_sponsor_levels.id = '" . ciniki_core_dbQuote($ciniki, $args['level_id']) . "' "
		. "";
	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sponsors', array(
		array('container'=>'levels', 'fname'=>'id', 'name'=>'level',
			'fields'=>array('id', 'name', 'permalink', 
				'sequence', 'size')),
	));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['levels']) || !isset($rc['levels'][0]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1687', 'msg'=>'Unable to find sponsorship level.'));
	}
	$level = $rc['levels'][0]['level'];
	
	return array('stat'=>'ok', 'level'=>$level);
}
?>

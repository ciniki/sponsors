<?php
//
// Description
// ===========
// This method will return all the information about an sponsor.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the sponsor is attached to.
// ref_id:			The ID of the object ref sponsor to get the details for.
// 
// Returns
// -------
//
function ciniki_sponsors_sponsorRefGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'ref_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Reference'), 
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
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['business_id'], 'ciniki.sponsors.sponsorRefGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	$modules = $rc['modules'];

	$strsql = "SELECT ciniki_sponsor_objrefs.id AS ref_id, "
		. "ciniki_sponsors.id AS sponsor_id, "
		. "ciniki_sponsors.title, "
		. "ciniki_sponsors.permalink, "
		. "ciniki_sponsors.level_id, "
		. "ciniki_sponsor_objrefs.object AS object, "
		. "ciniki_sponsor_objrefs.object_id AS object_id, "
		. "ciniki_sponsor_objrefs.sequence AS ref_sequence, "
		. "ciniki_sponsor_objrefs.webflags AS ref_webflags, "
		. "ciniki_sponsors.sequence, "
		. "ciniki_sponsors.webflags, "
		. "ciniki_sponsors.url, "
		. "ciniki_sponsors.primary_image_id, "
		. "ciniki_sponsors.excerpt, "
		. "ciniki_sponsors.content, "
		. "ciniki_sponsors.notes "
		. "FROM ciniki_sponsor_objrefs "
		. "LEFT JOIN ciniki_sponsors ON ( "
			. "ciniki_sponsor_objrefs.sponsor_id = ciniki_sponsors.id "
			. "AND ciniki_sponsor_objrefs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_sponsor_levels ON (ciniki_sponsors.level_id = ciniki_sponsor_levels.id "
			. "AND ciniki_sponsor_levels.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_sponsor_objrefs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_sponsor_objrefs.id = '" . ciniki_core_dbQuote($ciniki, $args['ref_id']) . "' "
		. "";
	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sponsors', array(
		array('container'=>'sponsors', 'fname'=>'ref_id', 'name'=>'sponsor',
			'fields'=>array('ref_id', 'sponsor_id', 'title', 'permalink', 'level_id', 
				'object', 'object_id', 'ref_sequence', 'ref_webflags', 
				'sequence', 'webflags', 'url', 'primary_image_id')),
	));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['sponsors']) || !isset($rc['sponsors'][0]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1687', 'msg'=>'Unable to find sponsor'));
	}
	$sponsor = $rc['sponsors'][0]['sponsor'];

	return array('stat'=>'ok', 'sponsor'=>$sponsor);
}
?>

<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sponsors_web_sponsorRefList($ciniki, $settings, $business_id, $object, $object_id) {

	$strsql = "SELECT ciniki_sponsors.id, "
		. "ciniki_sponsors.title, "
		. "ciniki_sponsors.permalink, "
		. "ciniki_sponsors.primary_image_id, "
		. "ciniki_sponsors.url, "
		. "UNIX_TIMESTAMP(ciniki_sponsors.last_updated) AS last_updated "
		. "FROM ciniki_sponsor_objrefs "
		. "LEFT JOIN ciniki_sponsors ON ("
			. "ciniki_sponsor_objrefs.sponsor_id = ciniki_sponsors.id "
			. "AND ciniki_sponsors.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "LEFT JOIN ciniki_sponsor_levels ON ("
			. "ciniki_sponsors.level_id = ciniki_sponsor_levels.id "
			. "AND ciniki_sponsor_levels.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "WHERE ciniki_sponsor_objrefs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_sponsor_objrefs.object = '" . ciniki_core_dbQuote($ciniki, $object) . "' "
		. "AND ciniki_sponsor_objrefs.object_id = '" . ciniki_core_dbQuote($ciniki, $object_id) . "' "
		// Check the participant is visible on the website
		. "AND (ciniki_sponsor_objrefs.webflags&0x01) = 0 "
		. "ORDER BY ciniki_sponsor_objrefs.sequence DESC, title ";
	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sponsors', array(
		array('container'=>'sponsors', 'fname'=>'id', 
			'fields'=>array('id', 'title', 'image_id'=>'primary_image_id', 
				'permalink', 'url', 'last_updated')),
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

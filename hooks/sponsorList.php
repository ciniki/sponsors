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
function ciniki_sponsors_hooks_sponsorList($ciniki, $business_id, $args) {

	if( isset($args['object']) && $args['object'] != '' 
		&& isset($args['object_id']) && $args['object_id'] != '' 
		) {
		$strsql = "SELECT ciniki_sponsor_objrefs.id AS ref_id, "
			. "ciniki_sponsors.id, "
			. "ciniki_sponsors.title "
			. "FROM ciniki_sponsor_objrefs "
			. "LEFT JOIN ciniki_sponsors ON ("
				. "ciniki_sponsor_objrefs.sponsor_id = ciniki_sponsors.id "
				. "AND ciniki_sponsors.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. ") "
			. "WHERE ciniki_sponsor_objrefs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_sponsor_objrefs.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
			. "AND ciniki_sponsor_objrefs.object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "ORDER BY ciniki_sponsor_objrefs.sequence, ciniki_sponsors.title "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sponsors', array(
			array('container'=>'sponsors', 'fname'=>'id', 'name'=>'sponsor',
				'fields'=>array('ref_id', 'sponsor_id'=>'id', 'title')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['sponsors']) ) {
			return array('stat'=>'ok', 'sponsors'=>$rc['sponsors']);	
		} else {
			return array('stat'=>'ok', 'sponsors'=>array());
		}
	}

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2047', 'msg'=>'Unable to get the sponsor list'));
}
?>

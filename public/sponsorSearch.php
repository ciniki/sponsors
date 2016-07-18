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
// business_id:     The ID of the business to get sponsors for.
//
// Returns
// -------
//
function ciniki_sponsors_sponsorSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'checkAccess');
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['business_id'], 'ciniki.sponsors.sponsorSearch');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $modules = $rc['modules'];

    //
    // Load the sponsors
    //
    $strsql = "SELECT ciniki_sponsors.id, ciniki_sponsors.title, "
        . "ciniki_sponsor_levels.name "
        . "FROM ciniki_sponsors "
        . "LEFT JOIN ciniki_sponsor_levels ON ("
            . "ciniki_sponsors.level_id = ciniki_sponsor_levels.id "
            . "AND ciniki_sponsor_levels.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_sponsors.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND (ciniki_sponsors.title LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR ciniki_sponsors.title LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
        . "";
    $strsql .= "ORDER BY ciniki_sponsors.title "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sponsors', array(
        array('container'=>'sponsors', 'fname'=>'id', 'name'=>'sponsor',
            'fields'=>array('id', 'title', 'level_name'=>'name')),
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

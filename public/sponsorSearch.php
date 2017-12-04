<?php
//
// Description
// -----------
// This method will return the list of sponsors for a tenant.  
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get sponsors for.
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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'checkAccess');
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.sponsorSearch');
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
            . "AND ciniki_sponsor_levels.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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

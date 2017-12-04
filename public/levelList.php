<?php
//
// Description
// -----------
// This method will return the list of sponsorship levels for a tenant.  
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get sponsorship levels for.
//
// Returns
// -------
//
function ciniki_sponsors_levelList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'checkAccess');
    $ac = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.levelList');
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }   

    //
    // Load the levels
    //
    $strsql = "SELECT ciniki_sponsor_levels.id, "
        . "ciniki_sponsor_levels.name, "
        . "ciniki_sponsor_levels.sequence, "
        . "COUNT(ciniki_sponsors.id) AS num_sponsors "
        . "FROM ciniki_sponsor_levels "
        . "LEFT JOIN ciniki_sponsors ON (ciniki_sponsor_levels.id = ciniki_sponsors.level_id "
            . "AND ciniki_sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ")" 
        . "WHERE ciniki_sponsor_levels.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "GROUP BY ciniki_sponsor_levels.id "
        . "ORDER BY ciniki_sponsor_levels.sequence DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sponsors', array(
        array('container'=>'levels', 'fname'=>'id', 'name'=>'level',
            'fields'=>array('id', 'name', 'sequence', 'num_sponsors')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['levels']) ) {
        $levels = array();
    } else {
        $levels = $rc['levels'];
    }

    //
    // Check for sponsors with no sponsorship level
    //
    $strsql = "SELECT 'sponsors', COUNT(*) "    
        . "FROM ciniki_sponsors "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND level_id = 0 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sponsors', 'num');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['num']['sponsors']) && $rc['num']['sponsors'] > 0 ) {
        $levels[] = array('level'=>array('id'=>'0', 
            'name'=>'No sponsorship level', 
            'sequence'=>'0', 
            'num_sponsors'=>$rc['num']['sponsors']
            ));
    }

    return array('stat'=>'ok', 'levels'=>$levels);
}
?>

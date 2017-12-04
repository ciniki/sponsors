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
// tnid:     The ID of the tenant the sponsor is attached to.
// sponsor_id:      The ID of the sponsor to get the details for.
// 
// Returns
// -------
//
function ciniki_sponsors_sponsorGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'sponsor_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Sponsor'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'checkAccess');
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.sponsorGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $modules = $rc['modules'];

    $strsql = "SELECT ciniki_sponsors.id, "
        . "ciniki_sponsors.title, "
        . "ciniki_sponsors.permalink, "
        . "ciniki_sponsors.level_id, "
        . "ciniki_sponsors.sequence, "
        . "ciniki_sponsors.webflags, "
        . "ciniki_sponsors.url, "
        . "ciniki_sponsors.primary_image_id, "
        . "ciniki_sponsors.excerpt, "
        . "ciniki_sponsors.content, "
        . "ciniki_sponsors.notes "
        . "FROM ciniki_sponsors "
        . "LEFT JOIN ciniki_sponsor_levels ON (ciniki_sponsors.level_id = ciniki_sponsor_levels.id "
            . "AND ciniki_sponsor_levels.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_sponsors.id = '" . ciniki_core_dbQuote($ciniki, $args['sponsor_id']) . "' "
        . "";
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sponsors', array(
        array('container'=>'sponsors', 'fname'=>'id', 'name'=>'sponsor',
            'fields'=>array('id', 'title', 'permalink', 'level_id', 
                'sequence', 'webflags', 'url', 'primary_image_id', 
                'excerpt', 'content', 'notes')),
    ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['sponsors']) || !isset($rc['sponsors'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.17', 'msg'=>'Unable to find sponsor'));
    }
    $sponsor = $rc['sponsors'][0]['sponsor'];

    //
    // Get the levels available
    //
    if( ($modules['ciniki.sponsors']['flags']&0x01) > 0 ) {
        $strsql = "SELECT id, name "
            . "FROM ciniki_sponsor_levels "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY sequence "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sponsors', array(
            array('container'=>'levels', 'fname'=>'id', 'name'=>'level',
                'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['levels']) ) {
            return array('stat'=>'ok', 'sponsor'=>$sponsor, 'levels'=>$rc['levels']);
        } else {
            return array('stat'=>'ok', 'sponsor'=>$sponsor, 'levels'=>array());
        }
    }
    
    return array('stat'=>'ok', 'sponsor'=>$sponsor);
}
?>

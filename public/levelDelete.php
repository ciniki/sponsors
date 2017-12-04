<?php
//
// Description
// -----------
// This method will delete a sponsorship level from the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the level is attached to.
// level_id:            The ID of the level_id to be removed.
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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'level_id'=>array('required'=>'yes', 'default'=>'', 'blank'=>'yes', 'name'=>'Level'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'checkAccess');
    $ac = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.levelDelete');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Check if any sponsors still in this level
    //
    $strsql = "SELECT 'sponsors', COUNT(*) "
        . "FROM ciniki_sponsors "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND level_id = '" . ciniki_core_dbQuote($ciniki, $args['level_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sponsors', 'num');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if isset($rc['num']['sponsors']) && $rc['num']['sponsors'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.8', 'msg'=>'All sponsors must be removed from the sponsorship level before it can be removed.'));
    }

    //
    // Get the uuid of the level to be deleted
    //
    $strsql = "SELECT uuid FROM ciniki_sponsor_levels "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['level_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'level');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['level']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.9', 'msg'=>'The sponsorship level does not exist.'));
    }
    $level_uuid = $rc['level']['uuid'];

    //
    // Remove the level
    //
    return ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.sponsors.level', 
        $args['level_id'], $level_uuid);
}
?>

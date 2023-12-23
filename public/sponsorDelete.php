<?php
//
// Description
// -----------
// This method will delete a sponsor from the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the sponsor is attached to.
// sponsor_id:          The ID of the sponsor to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_sponsors_sponsorDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'sponsor_id'=>array('required'=>'yes', 'default'=>'', 'blank'=>'yes', 'name'=>'Sponsor'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'checkAccess');
    $ac = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.sponsorDelete');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Get the uuid of the sponsor to be deleted
    //
    $strsql = "SELECT uuid FROM ciniki_sponsors "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['sponsor_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'sponsor');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['sponsor']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.15', 'msg'=>'The sponsor does not exist'));
    }
    $sponsor_uuid = $rc['sponsor']['uuid'];

    //
    // Get the list of categories
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_sponsors_categories "
        . "WHERE sponsor_id = '" . ciniki_core_dbQuote($ciniki, $args['sponsor_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.46', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    $categories = isset($rc['rows']) ? $rc['rows'] : array();

    //
    // Get the list of contacts
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_sponsor_contacts "
        . "WHERE sponsor_id = '" . ciniki_core_dbQuote($ciniki, $args['sponsor_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.46', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    $contacts = isset($rc['rows']) ? $rc['rows'] : array();
    

    //
    // Check if there are any objects still referencing this sponsor
    //
/*    $strsql = "SELECT 'refs', COUNT(*) "
        . "FROM ciniki_sponsor_objrefs "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND sponsor_id = '" . ciniki_core_dbQuote($ciniki, $args['sponsor_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sponsors', 'num');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['num']['refs']) && $rc['num']['refs'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.16', 'msg'=>'All references to this sponsor must be removed before the sponsor can be deleted.'));
    } */

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sponsors');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Get the list of object refs for the sponsor
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_sponsor_objrefs "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND sponsor_id = '" . ciniki_core_dbQuote($ciniki, $args['sponsor_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {
        foreach($rc['rows'] as $row) {
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.sponsors.objref', $row['id'], $row['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sponsors');
                return $rc;
            }
        }
    }

    //
    // Remove from categories
    //
    foreach($categories as $cat) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.sponsors.categorysponsor', $cat['id'], $cat['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sponsors');
            return $rc;
        }
    }

    //
    // Remove the sponsor
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.sponsors.sponsor', $args['sponsor_id'], $sponsor_uuid, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sponsors');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.sponsors');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'sponsors');

    return array('stat'=>'ok');
}
?>

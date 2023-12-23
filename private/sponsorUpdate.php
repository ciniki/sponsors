<?php
//
// Description
// ===========
// This method will update an sponsor in the database.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sponsors__sponsorUpdate(&$ciniki, $tnid, $args) {
    //
    // Get the existing sponsor details
    //
    $strsql = "SELECT uuid FROM ciniki_sponsors "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['sponsor_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'sponsor');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['sponsor']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.5', 'msg'=>'Sponsor not found'));
    }
    $sponsor = $rc['sponsor'];

    if( isset($args['title']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['title']);

        //
        // Make sure the permalink is unique
        //
        $strsql = "SELECT id, title, permalink FROM ciniki_sponsors "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['sponsor_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'sponsor');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.6', 'msg'=>'You already have an sponsor with this title, please choose another title'));
        }
    }

    //
    // Start the transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sponsors');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Check if level was specified
    //
    if( isset($modules['ciniki.sponsors']['flags']) && ($modules['ciniki.sponsors']['flags']&0x01) > 0 ) {
        if( !isset($args['level_id']) || $args['level_id'] == '' || $args['level_id'] == '0' ) {
            if( isset($args['level']) && $args['level'] != '' ) {
                //
                // Check if level exists
                //
                $strsql = "SELECT id "
                    . "FROM ciniki_sponsor_levels "
                    . "WHERE name = '" . ciniki_core_dbQuote($ciniki, $args['level']) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "";
                $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'level');
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sponsors');
                    return $rc;
                }
                if( isset($rc['level']) && isset($rc['level']['id']) ) {
                    $args['level_id'] = $rc['level']['id'];
                } elseif( $rc['num_rows'] > 1 ) {
                    $args['level_id'] = $rc['rows']['0']['id'];
                } else {
                    //
                    // Add level
                    //
                    $largs = array('name'=>$args['level'],
                        'permalink'=>ciniki_core_makePermalink($ciniki, $args['level']),
                        'sequence'=>1,
                        );
                    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.sponsors.level', $largs, 0x04);
                    if( $rc['stat'] != 'ok' ) { 
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sponsors');
                        return $rc;
                    }
                    $args['level_id'] = $rc['id'];
                }
            } else {
                // None specified, set to zero
                $args['level_id'] = 0;
            }
        }
    }

    //
    // Update the sponsor in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sponsors.sponsor', $args['sponsor_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sponsors');
        return $rc;
    }

    //
    // Update the sponsor categories
    //
    if( isset($args['categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'sponsorCategoriesUpdate');
        $rc = ciniki_sponsors_sponsorCategoriesUpdate($ciniki, $args['tnid'], $args['sponsor_id'], $args['categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sponsors');
            return $rc;
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.sponsors');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sponsors');
        return $rc;
    }
    
    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $tnid, 'ciniki', 'sponsors');

    return array('stat'=>'ok');
}
?>

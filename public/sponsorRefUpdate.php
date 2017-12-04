<?php
//
// Description
// ===========
// This method will update an sponsor in the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the sponsor is attached to.
// name:            (optional) The new name of the sponsor.
// url:             (optional) The new URL for the sponsor website.
// description:     (optional) The new description for the sponsor.
// start_date:      (optional) The new date the sponsor starts.  
// end_date:        (optional) The new date the sponsor ends, if it's longer than one day.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sponsors_sponsorRefUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'ref_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Reference'), 
        'sponsor_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Sponsor'), 
        'object'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object'), 
        'object_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object ID'), 
        'ref_webflags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'), 
        'ref_sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sequence'), 
        'title'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Title'), 
        'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'), 
        'level_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Level'), 
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sequence'), 
        'webflags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'), 
        'url'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'URL'), 
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'), 
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
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.sponsorRefUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the existing sponsor ref details
    //
    $strsql = "SELECT uuid, sponsor_id, object, object_id, sequence, webflags "
        . "FROM ciniki_sponsor_objrefs "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['ref_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'ref');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['ref']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.20', 'msg'=>'Reference not found'));
    }
    $ref = $rc['ref'];

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

    if( isset($args['sponsor_id']) && $args['sponsor_id'] == 0 ) {
        //
        // Add the new sponsor
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'public', 'sponsorAdd');
        $rc = ciniki_sponsors_sponsorAdd($ciniki);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $args['sponsor_id'] = $rc['sponsor_id'];
    } else if( isset($args['sponsor_id']) && $args['sponsor_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'sponsorUpdate');
        $rc = ciniki_sponsors__sponsorUpdate($ciniki, $args['tnid'], $args);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    } else if( isset($args['title']) 
        || isset($args['sequence']) 
        || isset($args['webflags']) 
        || isset($args['url']) 
        || isset($args['primary_image_id']) 
        ) {
        $args['sponsor_id'] = $ref['sponsor_id'];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'sponsorUpdate');
        $rc = ciniki_sponsors__sponsorUpdate($ciniki, $args['tnid'], $args);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    if( isset($args['sponsor_id']) && $args['sponsor_id'] == $ref['sponsor_id'] ) {
        unset($args['sponsor_id']);
    }

    if( isset($args['ref_sequence']) ) {
        $args['sequence'] = $args['ref_sequence'];
    } elseif( isset($args['sequence']) ) {
        unset($args['sequence']);
    }
    if( isset($args['ref_webflags']) ) {
        $args['webflags'] = $args['ref_webflags'];
    } elseif( isset($args['webflags']) ) {
        unset($args['webflags']);
    }

    //
    // Update the sponsor in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sponsors.objref', $args['ref_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sponsors');
        return $rc;
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
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'sponsors');

    return array('stat'=>'ok');
}
?>

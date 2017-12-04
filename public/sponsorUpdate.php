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
function ciniki_sponsors_sponsorUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'sponsor_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Sponsor'), 
        'title'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Title'), 
        'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'), 
        'level_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Level'), 
        'level'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Level'), 
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sequence'), 
        'webflags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'), 
        'url'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'URL'), 
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'), 
        'excerpt'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'), 
        'content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Content'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
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
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.sponsorUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
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
    // Update the sponsor
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'sponsorUpdate');
    $rc = ciniki_sponsors__sponsorUpdate($ciniki, $args['tnid'], $args); 
    if( $rc['stat'] != 'ok' ) { 
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

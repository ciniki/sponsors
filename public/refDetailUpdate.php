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
function ciniki_sponsors_refDetailUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'detail_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Reference'), 
        'object'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Object'), 
        'object_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Object ID'), 
        'title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Title'), 
        'content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Content'), 
        'size'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'20', 'name'=>'Size'), 
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
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.refDetailUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Update the sponsor in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sponsors.objrefdetail', $args['detail_id'], $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>

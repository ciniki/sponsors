<?php
//
// Description
// -----------
// This method will add a new sponsor for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to add the sposnor to.
//
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_sponsors_refDetailAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'object'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Object'), 
        'object_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Object ID'), 
        'title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Title'), 
        'content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Content'), 
        'size'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'20', 'name'=>'Size'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'checkAccess');
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.refDetailAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $modules = $rc['modules'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');

    //
    // Add the ref to the database
    //
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.sponsors.objrefdetail', $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $detail_id = $rc['id'];

    return array('stat'=>'ok', 'id'=>$detail_id);
}
?>

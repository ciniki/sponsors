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
// business_id:     The ID of the business the sponsor is attached to.
// detail_id:       The ID of the object ref sponsor to get the details for.
// 
// Returns
// -------
//
function ciniki_sponsors_refDetailGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'detail_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Detail'), 
        'object'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object'), 
        'object_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object ID'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'checkAccess');
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['business_id'], 'ciniki.sponsors.refDetailGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $modules = $rc['modules'];

    $strsql = "SELECT ciniki_sponsor_objrefdetails.id, "
        . "ciniki_sponsor_objrefdetails.object, "
        . "ciniki_sponsor_objrefdetails.object_id, "
        . "ciniki_sponsor_objrefdetails.title, "
        . "ciniki_sponsor_objrefdetails.content, "
        . "ciniki_sponsor_objrefdetails.size "
        . "FROM ciniki_sponsor_objrefdetails "
        . "WHERE ciniki_sponsor_objrefdetails.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    if( isset($args['detail_id']) && $args['detail_id'] != '' ) {
        $strsql .= "AND ciniki_sponsor_objrefdetails.id = '" . ciniki_core_dbQuote($ciniki, $args['detail_id']) . "' ";
    } elseif( isset($args['object']) && $args['object'] != '' 
        && isset($args['object_id']) && $args['object_id'] != '' ) {
        $strsql .= "AND ciniki_sponsor_objrefdetails.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
            . "AND ciniki_sponsor_objrefdetails.object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
    } else {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2063', 'msg'=>'Unable to get details'));
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sponsors', array(
        array('container'=>'details', 'fname'=>'id', 'name'=>'detail',
            'fields'=>array('id', 'title', 'content', 'size',
                'object', 'object_id')),
    ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['details']) || !isset($rc['details'][0]) ) {
        if( isset($args['detail_id']) && $args['detail_id'] != '' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2064', 'msg'=>'Unable to find details'));
        } 
        $detail = array(
            'id'=>0,
            'object'=>$args['object'],
            'object_id'=>$args['object_id'],
            'title'=>'',
            'content'=>'',
            'size'=>'20',
            'sponsors'=>array(),
            );
    } else {
        $detail = $rc['details'][0]['detail'];
    }

    //
    // Get the sponsors for the object
    //
    $strsql = "SELECT ciniki_sponsor_objrefs.id AS ref_id, "
        . "ciniki_sponsor_objrefs.object AS ref_object, "
        . "ciniki_sponsor_objrefs.object_id AS ref_object_id, "
        . "ciniki_sponsor_objrefs.sponsor_id, "
        . "ciniki_sponsor_objrefs.sequence AS ref_sequence, "
        . "IFNULL(ciniki_sponsors.title, '') AS title "
        . "FROM ciniki_sponsor_objrefs "
        . "LEFT JOIN ciniki_sponsors ON ( "
            . "ciniki_sponsor_objrefs.sponsor_id = ciniki_sponsors.id "
            . "AND ciniki_sponsors.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_sponsor_objrefs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_sponsor_objrefs.object = '" . ciniki_core_dbQuote($ciniki, $detail['object']) . "' "
        . "AND ciniki_sponsor_objrefs.object_id = '" . ciniki_core_dbQuote($ciniki, $detail['object_id']) . "' "
        . "ORDER BY ciniki_sponsor_objrefs.sequence, ciniki_sponsors.title "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sponsors', array(
        array('container'=>'sponsors', 'fname'=>'ref_id', 'name'=>'sponsor',
            'fields'=>array('ref_id', 'sponsor_id', 'title', 
                'object'=>'ref_object', 'object_id'=>'ref_object_id', 'ref_sequence')),
    ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['sponsors']) ) {
        $detail['sponsors'] = array();
    } else {
        $detail['sponsors'] = $rc['sponsors'];
    }

    return array('stat'=>'ok', 'detail'=>$detail);
}
?>

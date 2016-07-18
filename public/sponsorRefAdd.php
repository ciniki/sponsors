<?php
//
// Description
// -----------
// This method will add a new sponsor for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business to add the sposnor to.
// title:           The title/name of the sponsor.
// permalink:       (optional) The permalink to the sponsor, otherwise title will be made into permalink.
// level_id:        (optional) Either the level_id or level must be specified.  If level_id > 0 it 
//                  will be used, otherwise level will be added.
// level:           (optional) The level name.
// sequence:        The position in the list of sponsors for that level.
// webflags:        The flags for display on the website.
//
//                  0x01 - Hidden (do not show on website)
//                  0x02 - 
//                  0x04 - 
//                  0x08 - 
//
// url:             (optional) The URL for the sponsor website.
// description:     (optional) The description for the event.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_sponsors_sponsorRefAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'sponsor_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Sponsor'), 
        'object'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Object'), 
        'object_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Object ID'), 
        'ref_sequence'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'name'=>'Object Sequence'), 
        'ref_webflags'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Object Options'), 
        'title'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Title'), 
        'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'), 
        'level_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Level'), 
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'name'=>'Sequence'), 
        'webflags'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Options'), 
        'url'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'URL'), 
        'primary_image_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Image'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'checkAccess');
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['business_id'], 'ciniki.sponsors.sponsorRefAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $modules = $rc['modules'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');

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

    if( $args['sponsor_id'] == 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'public', 'sponsorAdd');
        $rc = ciniki_sponsors_sponsorAdd($ciniki);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $args['sponsor_id'] = $rc['id'];
    } elseif( $args['sponsor_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'sponsorUpdate');
        $rc = ciniki_sponsors__sponsorUpdate($ciniki, $args['business_id'], $args);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
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
    // Add the ref to the database
    //
    $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.sponsors.objref', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sponsors');
        return $rc;
    }
    $ref_id = $rc['id'];

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.sponsors');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sponsors');
        return $rc;
    }
    
    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'sponsors');

    return array('stat'=>'ok', 'id'=>$ref_id);
}
?>

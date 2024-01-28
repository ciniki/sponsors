<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sponsors_packageUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'package_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Sponsor Package'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'subname'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Subname'),
        'permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'),
        'invoice_code'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice Code'),
        'invoice_name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice Name'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'object'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object'),
        'object_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object ID'),
        'attached_to'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object and ID'),
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Accounting Category'),
        'subcategory'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Accounting Subcategory'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'),
        'amount'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Amount'),
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'),
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
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
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.packageUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load current package
    //
    $strsql = "SELECT id, object, object_id "
        . "FROM ciniki_sponsor_packages "
        . "WHERE ciniki_sponsor_packages.id = '" . ciniki_core_dbQuote($ciniki, $args['package_id']) . "' "
        . "AND ciniki_sponsor_packages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'package');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.23', 'msg'=>'Unable to load package', 'err'=>$rc['err']));
    }
    if( !isset($rc['package']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.24', 'msg'=>'Unable to find requested package'));
    }
    $package = $rc['package'];
   
    //
    // Check if changes to object
    //
    if( isset($args['attached_to']) ) {
        if( $args['attached_to'] == '' ) {
            $args['object'] = '';
            $args['object_id'] = '';
        } elseif( preg_match("/^([^\.]*)\.([^\.]*)\.([^\.]*)\.(.*)$/", $args['attached_to'], $m) ) {
            $object = $m[1] . '.' . $m[2] . '.' . $m[3];
            if( !isset($args['object']) || $args['object'] != $object ) {
                $args['object'] = $object;
            }
            if( !isset($args['object_id']) || $args['object_id'] != $m[4] ) {
                $args['object_id'] = $m[4];
            }
        }
    }

    if( isset($args['name']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
        //
        // Make sure the permalink is unique
        //
        $strsql = "SELECT id, name, permalink "
            . "FROM ciniki_sponsor_packages "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['package_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.37', 'msg'=>'You already have an sponsor package with this name, please choose another.'));
        }
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sponsors');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the Sponsor Package in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sponsors.package', $args['package_id'], $args, 0x04);
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

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.sponsors.package', 'object_id'=>$args['package_id']));

    return array('stat'=>'ok');
}
?>

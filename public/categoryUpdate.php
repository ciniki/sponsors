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
function ciniki_sponsors_categoryUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'category_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Sponsor Categories'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'),
        'start_dt'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Start Date'),
        'end_dt'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'End Date'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Return Category List'),
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
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.categoryUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($args['category_id']) && $args['category_id'] == 0
        && isset($args['categories']) && $args['categories'] == 'yes' 
        ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.sponsors.68', 'msg'=>'Unable to move No Category'));
    }

    //
    // Load the section
    //
    $strsql = "SELECT ciniki_sponsor_categories.id, "
        . "ciniki_sponsor_categories.name, "
        . "ciniki_sponsor_categories.sequence, "
        . "ciniki_sponsor_categories.start_dt, "
        . "ciniki_sponsor_categories.end_dt "
        . "FROM ciniki_sponsor_categories "
        . "WHERE ciniki_sponsor_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_sponsor_categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'category');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.62', 'msg'=>'Unable to load category', 'err'=>$rc['err']));
    }
    if( !isset($rc['category']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.63', 'msg'=>'Unable to find requested category'));
    }
    $category = $rc['category'];

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
    // Update the Sponsor Categories in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sponsors.category', $args['category_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sponsors');
        return $rc;
    }

    //
    // Update the section sequences
    //
    if( isset($args['sequence']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'categorySequencesUpdate');
        $rc = ciniki_sponsors_categorySequencesUpdate($ciniki, $args['tnid'], $args['category_id'], $args['sequence']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.62', 'msg'=>'Unable to move section', 'err'=>$rc['err']));
        }
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.sponsors.category', 'object_id'=>$args['category_id']));

    $rsp = array('stat'=>'ok');

    if( isset($args['categories']) && $args['categories'] == 'yes' ) {
        $strsql = "SELECT categories.id, "
            . "categories.name, "
            . "categories.sequence, "
            . "COUNT(sponsors.id) AS num_sponsors "
            . "FROM ciniki_sponsor_categories AS categories "
            . "LEFT JOIN ciniki_sponsors_categories AS scats ON ("
                . "categories.id = scats.category_id "
                . "AND scats.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_sponsors AS sponsors ON ("
                . "scats.sponsor_id = sponsors.id "
                . "AND sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ")" 
            . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY categories.id "
            . "ORDER BY categories.sequence "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
            array('container'=>'categories', 'fname'=>'id', 'name'=>'level',
                'fields'=>array('id', 'name', 'sequence', 'num_sponsors')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['categories'] = isset($rc['categories']) ? $rc['categories'] : array();

        //
        // Check for sponsors with no sponsorship level
        //
        $strsql = "SELECT 'sponsors', "
            . "COUNT(sponsors.id) AS num_sponsors, "
            . "IFNULL(scats.sponsor_id, '') AS sid "    
            . "FROM ciniki_sponsors AS sponsors "
            . "LEFT JOIN ciniki_sponsors_categories AS scats ON ("
                . "sponsors.id = scats.sponsor_id "
                . "AND scats.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY sid "
            . "HAVING sid = '' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sponsors', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num']['sponsors']) && $rc['num']['sponsors'] > 0 ) {
            $rsp['categories'][] = array(
                'id'=>'0', 
                'name'=>'No Category', 
                'sequence'=>'0', 
                'num_sponsors'=>$rc['num']['sponsors']
                );
        }
    }

    return $rsp;
}
?>

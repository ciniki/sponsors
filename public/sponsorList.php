<?php
//
// Description
// -----------
// This method will return the list of sponsors for a tenant.  
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get sponsors for.
//
// Returns
// -------
//
function ciniki_sponsors_sponsorList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'level_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Level'), 
        'category_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'checkAccess');
    $ac = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.sponsorList');
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }
    $modules = $ac['modules'];

    //
    // Load the sponsors
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sponsors', 0x04) && isset($args['category_id']) ) {
        $strsql = "SELECT sponsors.id, "
            . "sponsors.title, "
            . "IFNULL(scats.category_id, 0) AS cid "
            . "FROM ciniki_sponsors AS sponsors "
            . "LEFT JOIN ciniki_sponsors_categories AS scats ON ("
                . "sponsors.id = scats.sponsor_id "
                . "AND scats.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        if( $args['category_id'] > 0 ) {
            $strsql .= "HAVING cid = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' ";
        } else {
            $strsql .= "HAVING cid = 0 ";
        }
        $strsql .= "ORDER BY sponsors.sequence, sponsors.title ";
    } else {
        $strsql = "SELECT sponsors.id, "
            . "sponsors.title  "
            . "FROM ciniki_sponsors AS sponsors "
            . "WHERE sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sponsors', 0x01) && isset($args['level_id']) && $args['level_id'] != '' ) {
            $strsql .= "AND sponsors.level_id = '" . ciniki_core_dbQuote($ciniki, $args['level_id']) . "' ";
        }
        $strsql .= "ORDER BY sponsors.sequence, sponsors.title "
            . "";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
        array('container'=>'sponsors', 'fname'=>'id', 
            'fields'=>array('id', 'title')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sponsors = isset($rc['sponsors']) ? $rc['sponsors'] : array();

    $rsp = array('stat'=>'ok', 'sponsors'=>$sponsors);

    //
    // Get the list of levels
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sponsors', 0x01) ) {
        $strsql = "SELECT levels.id, "
            . "levels.name, "
            . "levels.sequence, "
            . "COUNT(sponsors.id) AS num_sponsors "
            . "FROM ciniki_sponsor_levels AS levels "
            . "LEFT JOIN ciniki_sponsors AS sponsors ON ("
                . "levels.id = sponsors.level_id "
                . "AND sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ")" 
            . "WHERE levels.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY levels.id "
            . "ORDER BY levels.sequence "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
            array('container'=>'levels', 'fname'=>'id', 'name'=>'level',
                'fields'=>array('id', 'name', 'sequence', 'num_sponsors')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['levels'] = isset($rc['levels']) ? $rc['levels'] : array();

        //
        // Check for sponsors with no sponsorship level
        //
        $strsql = "SELECT 'sponsors', COUNT(*) "    
            . "FROM ciniki_sponsors "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND level_id = 0 "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sponsors', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num']['sponsors']) && $rc['num']['sponsors'] > 0 ) {
            $rsp['levels'][] = array(
                'id'=>'0', 
                'name'=>'No sponsorship level', 
                'sequence'=>'0', 
                'num_sponsors'=>$rc['num']['sponsors']
                );
        }
    }

    return $rsp;
}
?>

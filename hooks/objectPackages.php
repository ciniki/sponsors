<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sponsors_hooks_objectPackages($ciniki, $tnid, $args) {

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Check for expenses
    //
    if( isset($args['object']) && $args['object'] != '' 
        && isset($args['object_id']) && $args['object_id'] != ''
        ) {

        //
        // Get the list of packages
        //
        $strsql = "SELECT ciniki_sponsor_packages.id, "
            . "ciniki_sponsor_packages.name, "
            . "ciniki_sponsor_packages.subname, "
            . "ciniki_sponsor_packages.permalink, "
            . "ciniki_sponsor_packages.invoice_code, "
            . "ciniki_sponsor_packages.invoice_name, "
            . "ciniki_sponsor_packages.flags, "
            . "IF((flags&0x01) = 0x01, 'yes', 'no') AS visible, "
            . "ciniki_sponsor_packages.object, "
            . "ciniki_sponsor_packages.object_id, "
            . "ciniki_sponsor_packages.category, "
            . "ciniki_sponsor_packages.sequence, "
            . "ciniki_sponsor_packages.amount "
            . "FROM ciniki_sponsor_packages "
            . "WHERE ciniki_sponsor_packages.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
            . "AND object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
            array('container'=>'packages', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'subname', 'permalink', 'invoice_code', 'invoice_name', 'flags', 'visible',
                    'object', 'object_id', 'category', 'sequence', 'amount'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $packages = isset($rc['packages']) ? $rc['packages'] : array();

        return array('stat'=>'ok', 'packages'=>$packages);
    }

    return array('stat'=>'ok');
}
?>

<?php
//
// Description
// -----------
// This method will return the list of Sponsor Packages for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Sponsor Package for.
//
// Returns
// -------
//
function ciniki_sponsors_packageList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'checkAccess');
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.packageList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

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
        . "WHERE ciniki_sponsor_packages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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
    $package_ids = array();
    foreach($packages as $iid => $package) {
        $package_ids[] = $package['id'];
    }

    return array('stat'=>'ok', 'packages'=>$packages, 'nplist'=>$package_ids);
}
?>

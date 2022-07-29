<?php
//
// Description
// -----------
// This method searchs for a Sponsor Packages for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Sponsor Package for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_sponsors_packageSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'checkAccess');
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.packageSearch');
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
        . "ciniki_sponsor_packages.object, "
        . "ciniki_sponsor_packages.object_id, "
        . "ciniki_sponsor_packages.category, "
        . "ciniki_sponsor_packages.sequence, "
        . "ciniki_sponsor_packages.amount "
        . "FROM ciniki_sponsor_packages "
        . "WHERE ciniki_sponsor_packages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
        array('container'=>'packages', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'subname', 'permalink', 'invoice_code', 'invoice_name', 'flags', 'object', 'object_id', 'category', 'sequence', 'amount')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['packages']) ) {
        $packages = $rc['packages'];
        $package_ids = array();
        foreach($packages as $iid => $package) {
            $package_ids[] = $package['id'];
        }
    } else {
        $packages = array();
        $package_ids = array();
    }

    return array('stat'=>'ok', 'packages'=>$packages, 'nplist'=>$package_ids);
}
?>

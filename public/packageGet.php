<?php
//
// Description
// ===========
// This method will return all the information about an sponsor package.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the sponsor package is attached to.
// package_id:          The ID of the sponsor package to get the details for.
//
// Returns
// -------
//
function ciniki_sponsors_packageGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'package_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Sponsor Package'),
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
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.packageGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Sponsor Package
    //
    if( $args['package_id'] == 0 ) {
        $package = array('id'=>0,
            'name'=>'',
            'subname'=>'',
            'permalink'=>'',
            'invoice_code'=>'',
            'invoice_name'=>'',
            'flags'=>'0',
            'attached_to'=>'',
            'object'=>'',
            'object_id'=>'',
            'category'=>'',
            'sequence'=>'',
            'amount'=>'',
            'primary_image_id'=>'0',
            'synopsis'=>'',
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Sponsor Package
    //
    else {
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
            . "ciniki_sponsor_packages.amount, "
            . "ciniki_sponsor_packages.primary_image_id, "
            . "ciniki_sponsor_packages.synopsis, "
            . "ciniki_sponsor_packages.description "
            . "FROM ciniki_sponsor_packages "
            . "WHERE ciniki_sponsor_packages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_sponsor_packages.id = '" . ciniki_core_dbQuote($ciniki, $args['package_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
            array('container'=>'packages', 'fname'=>'id', 
                'fields'=>array('name', 'subname', 'permalink', 'invoice_code', 'invoice_name', 'flags', 'object', 'object_id', 'category', 'sequence', 'amount', 'primary_image_id', 'synopsis', 'description'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.27', 'msg'=>'Sponsor Package not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['packages'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.28', 'msg'=>'Unable to find Sponsor Package'));
        }
        $package = $rc['packages'][0];
        if( $package['object'] != '' ) {
            $package['attached_to'] = $package['object'] . '.' . $package['object_id'];
        } else {
            $package['attached_to'] = '';
        }
    }

    $rsp = array('stat'=>'ok', 'package'=>$package);

    //
    // Load the list of available objects
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'sponsorshipObjectsLoad');
    $rc = ciniki_sponsors_sponsorshipObjectsLoad($ciniki, $args['tnid'], array(
        'object' => $package['object'],
        'object_id' => $package['object_id'],
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.30', 'msg'=>'Unable to load objects', 'err'=>$rc['err']));
    }
    $rsp['objects'] = isset($rc['objects']) ? $rc['objects'] : array();

    //
    // Add the None option
    //
    array_unshift($rsp['objects'], array(
        'id' => 0,
        'object' => '',
        'object_id' => '',
        'full_name' => 'None',
        'name' => 'None',
        ));

    return $rsp;
}
?>

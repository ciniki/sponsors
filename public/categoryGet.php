<?php
//
// Description
// ===========
// This method will return all the information about an sponsor categories.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the sponsor categories is attached to.
// category_id:          The ID of the sponsor categories to get the details for.
//
// Returns
// -------
//
function ciniki_sponsors_categoryGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'category_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Sponsor Categories'),
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
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.categoryGet');
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
    // Return default for new Sponsor Categories
    //
    if( $args['category_id'] == 0 ) {
        $category = array('id'=>0,
            'name'=>'',
            'sequence'=>'1',
        );
    }

    //
    // Get the details for an existing Sponsor Categories
    //
    else {
        $strsql = "SELECT ciniki_sponsor_categories.id, "
            . "ciniki_sponsor_categories.name, "
            . "ciniki_sponsor_categories.sequence "
            . "FROM ciniki_sponsor_categories "
            . "WHERE ciniki_sponsor_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_sponsor_categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
            array('container'=>'categories', 'fname'=>'id', 
                'fields'=>array('name', 'sequence'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.52', 'msg'=>'Sponsor Categories not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['categories'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.53', 'msg'=>'Unable to find Sponsor Categories'));
        }
        $category = $rc['categories'][0];
    }

    return array('stat'=>'ok', 'category'=>$category);
}
?>

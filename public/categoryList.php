<?php
//
// Description
// -----------
// This method will return the list of Sponsor Categoriess for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Sponsor Categories for.
//
// Returns
// -------
//
function ciniki_sponsors_categoryList($ciniki) {
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
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.categoryList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of categories
    //
    $strsql = "SELECT ciniki_sponsor_categories.id, "
        . "ciniki_sponsor_categories.name, "
        . "ciniki_sponsor_categories.sequence "
        . "FROM ciniki_sponsor_categories "
        . "WHERE ciniki_sponsor_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
        array('container'=>'categories', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'sequence')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $categories = isset($rc['categories']) ? $rc['categories'] : array();
    $category_ids = array();
    foreach($categories as $iid => $category) {
        $category_ids[] = $category['id'];
    }

    return array('stat'=>'ok', 'categories'=>$categories, 'nplist'=>$category_ids);
}
?>

<?php
//
// Description
// -----------
// This function will update the categories a sponsor is a part of
//
// Arguments
// ---------
//
function ciniki_sponsors_sponsorCategoriesUpdate(&$ciniki, $tnid, $sponsor_id, $categories) {

    //
    // Get the existing list of categories for the sponsor
    //
    $strsql = "SELECT id, uuid, category_id "
        . "FROM ciniki_sponsors_categories "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND sponsor_id = '" . ciniki_core_dbQuote($ciniki, $sponsor_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sponsors', array(
        array('container'=>'categories', 'fname'=>'category_id', 'fields'=>array('id', 'uuid', 'category_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['categories']) ) {
        $existing_categories = $rc['categories'];
    } else {
        $existing_categories = array();
    }

    //
    // Check for any new categories that need to be added
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    foreach($categories as $category_id) {
        if( !isset($existing_categories[$category_id]) ) {
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.sponsors.categorysponsor', array(
                'category_id'=>$category_id,
                'sponsor_id'=>$sponsor_id,
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Check for any categories that need to be removed
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    foreach($existing_categories as $category_id => $category) {
        if( !in_array($category_id, $categories) ) {
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.sponsors.categorysponsor', $category['id'], $category['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>

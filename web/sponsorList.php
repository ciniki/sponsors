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
function ciniki_sponsors_web_sponsorList($ciniki, $settings, $business_id) {

    $strsql = "SELECT ciniki_sponsors.id, "
        . "'' as category, "
        . "ciniki_sponsors.title AS name, "
        . "ciniki_sponsors.permalink, "
        . "IFNULL(ciniki_sponsor_levels.id, '0') AS level_id, "
        . "IFNULL(ciniki_sponsor_levels.size, '30') AS size, "
        . "IFNULL(ciniki_sponsor_levels.name, 'Sponsors') AS level_name, "
        . "ciniki_sponsors.excerpt, "
        . "ciniki_sponsors.primary_image_id, "
        . "ciniki_sponsors.url "
        . "FROM ciniki_sponsors "
        . "LEFT JOIN ciniki_sponsor_levels ON ("
            . "ciniki_sponsors.level_id = ciniki_sponsor_levels.id "
            . "AND ciniki_sponsor_levels.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE ciniki_sponsors.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        // Check the participant is visible on the website
        . "AND (ciniki_sponsors.webflags&0x01) = 0 "
        . "ORDER BY ciniki_sponsor_levels.sequence DESC, ciniki_sponsors.sequence DESC, title ";
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sponsors', array(
        array('container'=>'levels', 'fname'=>'level_id', 'name'=>'level',
            'fields'=>array('number'=>'size', 'name'=>'level_name')),
        array('container'=>'categories', 'fname'=>'category', 'name'=>'category',
            'fields'=>array('name'=>'category')),
        array('container'=>'sponsors', 'fname'=>'id', 'name'=>'sponsor',
            'fields'=>array('id', 'name', 'image_id'=>'primary_image_id', 
                'permalink', 'description'=>'excerpt', 'url')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['levels']) ) {
        return array('stat'=>'ok', 'levels'=>array());
    }
    $levels = $rc['levels'];

    return array('stat'=>'ok', 'levels'=>$levels);
}
?>

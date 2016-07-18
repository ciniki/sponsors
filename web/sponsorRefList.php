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
function ciniki_sponsors_web_sponsorRefList($ciniki, $settings, $business_id, $object, $object_id) {

    $strsql = "SELECT ciniki_sponsors.id, "
        . "IFNULL(ciniki_sponsor_objrefdetails.title, '') AS d_title, "
        . "IFNULL(ciniki_sponsor_objrefdetails.content, '') AS d_content, "
        . "IFNULL(ciniki_sponsor_objrefdetails.size, '20') AS d_size, "
        . "ciniki_sponsors.title, "
        . "ciniki_sponsors.permalink, "
        . "ciniki_sponsors.primary_image_id, "
        . "ciniki_sponsors.url, "
        . "CONCAT_WS('-', ciniki_sponsor_objrefs.sequence, ciniki_sponsors.id) AS uid, "
        . "UNIX_TIMESTAMP(ciniki_sponsors.last_updated) AS last_updated "
        . "FROM ciniki_sponsor_objrefs "
        . "LEFT JOIN ciniki_sponsor_objrefdetails ON ("
            . "ciniki_sponsor_objrefs.object = ciniki_sponsor_objrefdetails.object "
            . "AND ciniki_sponsor_objrefs.object_id = ciniki_sponsor_objrefdetails.object_id "
            . "AND ciniki_sponsor_objrefdetails.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "LEFT JOIN ciniki_sponsors ON ("
            . "ciniki_sponsor_objrefs.sponsor_id = ciniki_sponsors.id "
            . "AND ciniki_sponsors.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "LEFT JOIN ciniki_sponsor_levels ON ("
            . "ciniki_sponsors.level_id = ciniki_sponsor_levels.id "
            . "AND ciniki_sponsor_levels.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE ciniki_sponsor_objrefs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_sponsor_objrefs.object = '" . ciniki_core_dbQuote($ciniki, $object) . "' "
        . "AND ciniki_sponsor_objrefs.object_id = '" . ciniki_core_dbQuote($ciniki, $object_id) . "' "
        // Check the participant is visible on the website
        . "AND (ciniki_sponsor_objrefs.webflags&0x01) = 0 "
        . "ORDER BY ciniki_sponsor_objrefs.sequence, ciniki_sponsors.title ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sponsors', array(
        array('container'=>'sponsors', 'fname'=>'d_title', 'name'=>'detail',
            'fields'=>array('title'=>'d_title', 'content'=>'d_content', 'size'=>'d_size')),
        array('container'=>'sponsors', 'fname'=>'uid', 'name'=>'sponsor',
            'fields'=>array('id', 'title', 'image_id'=>'primary_image_id', 
                'permalink', 'url', 'last_updated')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['sponsors']) ) {
        return array('stat'=>'ok', 'sponsors'=>array());
    }
    $sponsors = array_pop($rc['sponsors']);

    return array('stat'=>'ok', 'sponsors'=>$sponsors['detail']);
}
?>

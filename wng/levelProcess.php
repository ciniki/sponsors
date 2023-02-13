<?php
//
// Description
// -----------
// This function will check for an existing cart to load into the session
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sponsors_wng_levelProcess($ciniki, $tnid, &$request, $section) {

    $s = isset($section['settings']) ? $section['settings'] : array();

    $blocks = array();

    if( !isset($s['level-id']) || $s['level-id'] == '' || $s['level-id'] == '' ) {
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }


    //
    // Load the list of sponsors
    //
    $strsql = "SELECT id, "
        . "primary_image_id, "
        . "url "
        . "FROM ciniki_sponsors "
        . "WHERE ciniki_sponsors.level_id = '" . ciniki_core_dbQuote($ciniki, $s['level-id']) . "' "
        . "AND ciniki_sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
        array('container'=>'sponsors', 'fname'=>'id', 
            'fields'=>array('id', 'image-id' => 'primary_image_id', 'url'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.45', 'msg'=>'Unable to load sponsors', 'err'=>$rc['err']));
    }
    $sponsors = isset($rc['sponsors']) ? $rc['sponsors'] : array();

    if( count($sponsors) > 0 ) {
        $levelclass = '';
        if( isset($s['title']) && $s['title'] != '' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
            $levelclass = ciniki_core_makePermalink($ciniki, $s['title']);
            $blocks[] = array(
                'type' => 'title',
                'class' => 'aligncenter',
                'title' => $s['title'],
                );
        }
        $blocks[] = array(
            'type' => 'imagebuttons',
            'class' => 'sponsors-level sponsors-level-' . $levelclass,
            'image-format' => 'padded',
            'image-ratio' => '4-3',
            'items' => $sponsors,
            );
    }


    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>

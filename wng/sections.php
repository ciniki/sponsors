<?php
//
// Description
// -----------
// This function will return the list of available sections to the ciniki.wng module.
//
// Arguments
// ---------
// ciniki:
// tnid:     
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_sponsors_wng_sections(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.sponsors']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.33', 'msg'=>"I'm sorry, the section you requested does not exist."));
    }

    $sections = array();

    //
    // Load the list of levels
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_sponsor_levels "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY sequence, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
        array('container'=>'levels', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.44', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
    }
    $levels = isset($rc['levels']) ? $rc['levels'] : array();

    //
    // Display sponsors
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sponsors', 0x01) && count($levels) > 0 ) {
        $sections['ciniki.sponsors.level'] = array(
            'name'=>'Images',
            'module' => 'Sponsors',
            'settings'=>array(
                'title' => array('label' => 'Title', 'type' => 'text'),
                'level-id' => array('label' => 'Level', 'type' => 'select',
                    'complex_options' => array('value'=>'id', 'name'=>'name'),
                    'options' => $levels,
                    ),
                'layout' => array('label' => 'Layout', 'type' => 'toggle', 'default'=>'imagebuttons',
                    'toggles' => array('imagebuttons' => 'Image Buttons', 'flexcards' => 'Flex Cards'),
                    ),
                ),
            );
    }


    //
    // Sponsorship packages
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sponsors', 0x10) ) {
        $sections['ciniki.sponsors.packages'] = array(
            'name'=>'Sponsorship Packages',
            'module' => 'Sponsors',
            'settings'=>array(
                'title' => array('label' => 'Title', 'type' => 'text'),
                ),
            );
    }



    return array('stat'=>'ok', 'sections'=>$sections);
}
?>

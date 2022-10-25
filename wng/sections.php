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
    // Image, Menu with no drop downs/submenus
    //
    $sections['ciniki.sponsors.packages'] = array(
        'name'=>'Sponsorship Packages',
        'module' => 'Sponsors',
        'settings'=>array(
            'title' => array('label' => 'Title', 'type' => 'text'),
            ),
        );

    return array('stat'=>'ok', 'sections'=>$sections);
}
?>

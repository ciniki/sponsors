<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
// ciniki:
// modules:         The modules that are enabled for the tenant.
//
// Returns
// -------
//
function ciniki_sponsors_flags($ciniki, $modules) {
    $flags = array(
        // 0x01
        array('flag'=>array('bit'=>'1', 'name'=>'Levels')),
        array('flag'=>array('bit'=>'2', 'name'=>'References')),
//      array('flag'=>array('bit'=>'3', 'name'=>'')),
//      array('flag'=>array('bit'=>'4', 'name'=>'')),
        // 0x10
        array('flag'=>array('bit'=>'5', 'name'=>'Packages')),       // SAPOS integration
//      array('flag'=>array('bit'=>'6', 'name'=>'')),
//      array('flag'=>array('bit'=>'7', 'name'=>'')),
//      array('flag'=>array('bit'=>'8', 'name'=>'')),
        );

    return array('stat'=>'ok', 'flags'=>$flags);
}
?>

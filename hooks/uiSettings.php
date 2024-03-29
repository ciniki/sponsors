<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get sponsors for.
//
// Returns
// -------
//
function ciniki_sponsors_hooks_uiSettings($ciniki, $tnid, $args) {

    //
    // Setup the default response
    //
    $rsp = array('stat'=>'ok', 'menu_items'=>array());

    //
    // Check permissions for what menu items should be available
    //
    if( isset($ciniki['tenant']['modules']['ciniki.sponsors'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>1700,
            'label'=>'Sponsors', 
            'edit'=>array('app'=>'ciniki.sponsors.main'),
            );
        $rsp['menu_items'][] = $menu_item;
    } 

    //
    // Sponsor packages
    //
    if( isset($ciniki['tenant']['modules']['ciniki.sponsors'])
        && isset($ciniki['tenant']['modules']['ciniki.sapos'])
        && ciniki_core_checkModuleFlags($ciniki, 'ciniki.sponsors', 0x10)
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $rsp['settings_menu_items'][] = array('priority'=>1700, 'label'=>'Sponsors', 'edit'=>array('app'=>'ciniki.sponsors.settings'));
    }

    return $rsp;
}
?>

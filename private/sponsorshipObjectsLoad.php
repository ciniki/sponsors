<?php
//
// Description
// -----------
// Load the object list from available modules.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_sponsors_sponsorshipObjectsLoad(&$ciniki, $tnid, $args) {

    //
    // Go through each module for the tenant and see if sponsorshipObjects exists in hooks
    //
    $objects = array();
    foreach($ciniki['tenant']['modules'] as $module) {
        //
        // Check if the module has a file reporting/blocks.php 
        //
        $rc = ciniki_core_loadMethod($ciniki, $module['package'], $module['module'], 'hooks', 'sponsorshipObjects');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $tnid, $args);
            if( $rc['stat'] == 'ok' ) {
                $objects = array_merge($objects, $rc['objects']);
            }
        }
    }

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>

<?php
//
// Description
// -----------
// Process the section request for WNG
//
// Arguments
// ---------
// ciniki:
// tnid:            The ID of the tenant.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_sponsors_wng_process(&$ciniki, $tnid, &$request, $section) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.sponsors']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.39', 'msg'=>"Content not available."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.40', 'msg'=>"No section specified."));
    }

    //
    // Check which section to process
    //
    if( $section['ref'] == 'ciniki.sponsors.packages' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'wng', 'packagesProcess');
        return ciniki_sponsors_wng_packagesProcess($ciniki, $tnid, $request, $section);
    }

    return array('stat'=>'ok');
}
?>

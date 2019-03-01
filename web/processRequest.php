<?php
//
// Description
// -----------
// This function will process a web request for the sponsors module.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get sponsors for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_sponsors_web_processRequest(&$ciniki, $settings, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.sponsors']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.sponsors.21', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }
    $page = array(
        'title'=>$args['page_title'],
        'breadcrumbs'=>$args['breadcrumbs'],
        'blocks'=>array(),
        );

    ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processURL');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processSponsors');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'web', 'sponsorList');
    $rc = ciniki_sponsors_web_sponsorList($ciniki, $settings, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $page_content = ''; 
    if( isset($rc['levels']) && count($rc['levels']) > 1 ) {
        $sponsors = $rc['levels'];
        foreach($sponsors as $lnum => $level) {
            $rc = ciniki_web_processSponsors($ciniki, $settings, $level['level']['number'], $level['level']['categories']);
            if( $rc['stat'] == 'ok' ) {
                $page['blocks'][] = array('type'=>'content', 'title'=>$level['level']['name'], 'html'=>$rc['content']);
            }
        }
    } else {
        $sponsors = $rc['levels'][0]['level']['categories'];
        $rc = ciniki_web_processSponsors($ciniki, $settings, 30, $sponsors);
        if( $rc['stat'] == 'ok' ) {
            $page['blocks'][] = array('type'=>'content', 'html'=>$rc['content']);
        }
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>

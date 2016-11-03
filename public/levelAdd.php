<?php
//
// Description
// -----------
// This method will add a new sponsorship level for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business to add the level to.
// name:            The title/name for the sponsorship level.
// permalink:       (optional) The permalink to the level, otherwise title will be made into permalink.
// sequence:        The position in the list of the level.
// size:            The size for the logo on the website.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_sponsors_levelAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
        'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'), 
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'name'=>'Sequence'), 
        'size'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'10', 'name'=>'Size'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'checkAccess');
    $ac = ciniki_sponsors_checkAccess($ciniki, $args['business_id'], 'ciniki.sponsors.levelAdd');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }
    $modules = $ac['modules'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');

    //
    // Create permalink if doesn't already exist
    //
    if( !isset($args['permalink']) || $args['permalink'] == '' ) {
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
    }

    //
    // Check the permalink doesn't already exist
    //
    $strsql = "SELECT id FROM ciniki_sponsor_levels "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' " 
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'level');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.7', 'msg'=>'You already have a sponsorship level with this name, please choose another name.'));
    }

    //
    // Add the level to the database
    //
    return ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.sponsors.level', $args);
}
?>

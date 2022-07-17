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

    //
    // Difference layout for theme twenty one
    //
    if( isset($settings['site-layout']) && $settings['site-layout'] == 'twentyone' ) {
        $strsql = "SELECT ciniki_sponsors.id, "
            . "'' as category, "
            . "ciniki_sponsors.title AS name, "
            . "ciniki_sponsors.permalink, "
            . "IFNULL(ciniki_sponsor_levels.id, '0') AS level_id, "
            . "IFNULL(ciniki_sponsor_levels.size, '30') AS size, "
            . "IFNULL(ciniki_sponsor_levels.name, 'Sponsors') AS level_name, "
            . "ciniki_sponsors.excerpt, "
            . "ciniki_sponsors.primary_image_id, "
            . "ciniki_sponsors.url "
            . "FROM ciniki_sponsors "
            . "LEFT JOIN ciniki_sponsor_levels ON ("
                . "ciniki_sponsors.level_id = ciniki_sponsor_levels.id "
                . "AND ciniki_sponsor_levels.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            // Check the participant is visible on the website
            . "AND (ciniki_sponsors.webflags&0x01) = 0 "
            . "ORDER BY ciniki_sponsor_levels.sequence DESC, ciniki_sponsors.sequence DESC, title ";
        
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
            array('container'=>'levels', 'fname'=>'level_id', 'fields'=>array('number'=>'size', 'name'=>'level_name')),
            array('container'=>'sponsors', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'image_id'=>'primary_image_id', 
                    'permalink', 'description'=>'excerpt', 'url')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['levels']) ) {
            return array('stat'=>'ok', 'levels'=>array());
        }
        $levels = $rc['levels'];

        foreach($levels as $level) {
            $page['blocks'][] = array(
                'type' => 'sponsors',
                'sponsors' => array('sponsors'=>$level['sponsors']),
                'image_version' => 'original',
                'thumbnail_format' => 'square-padded',
                'thumbnail_padding_color' => '#ffffff',
                'base_url' => '',
                );
            $page['blocks'][] = array(
                'type' => 'tradingcards',
                'cards' => $level['sponsors'],
                'image_version' => 'original',
                'thumbnail_format' => 'square-padded',
                'thumbnail_padding_color' => '#ffffff',
                'base_url' => '',
                );
        }

        return array('stat'=>'ok', 'page'=>$page);
    } 

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

<?php
//
// Description
// ===========
// This method returns the list of objects that can be returned
// as invoice items.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_sponsors_sapos_objectList($ciniki, $tnid) {

    $objects = array(
        //
        // this object should only be added to carts
        //
//        'ciniki.sponsors.package.ciniki.events' => array(
//            'name' => 'All Events Sponsorship',
//            ),
        'ciniki.sponsors.package' => array(
            'name' => 'Sponsorship Package',
            ),
//        'ciniki.sponsors.package.ciniki.courses' => array(
//            'name' => 'All Programs Sponsorship',
//            ),
//        'ciniki.sponsors.package.ciniki.courses.course' => array(
//            'name' => 'Program Sponsorship',
//            ),
//        'ciniki.sponsors.package.ciniki.ags' => array(
//            'name' => 'Gallery Sponsorship',
//            ),
//        'ciniki.sponsors.package.ciniki.ags.exhibit' => array(
//            'name' => 'Exhibit Sponsorship',
//            ),
        );

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>

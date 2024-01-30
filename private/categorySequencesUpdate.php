<?php
//
// Description
// -----------
// Update the category sequences 
// 
// To update all sequences and make sure in order, pass with category_id=0, new_seq = 0;
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_sponsors_categorySequencesUpdate(&$ciniki, $tnid, $category_id, $new_seq) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    //
    // Get the current categories 
    //
    $strsql = "SELECT categories.id, "
        . "categories.sequence "
        . "FROM ciniki_sponsor_categories AS categories "
        . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY sequence "
        . "";
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.sponsors', 'categories');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.63', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
    }
    $categories = isset($rc['categories']) ? $rc['categories'] : array();

    if( $category_id > 0 && !isset($categories[$category_id]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.64', 'msg'=>'Section not found'));
    }
    
    $cur_num = 1;
    foreach($categories as $cid => $sequence) {
        //
        // If this is where new category is to be, then skip sequence
        //
        if( $cur_num == $new_seq && $cid != $category_id ) {
            // 
            // Make sure the specified category was not already moved, or added to correct position
            //
            if( $category_id > 0 && $categories[$category_id] != $cur_num ) {
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sponsors.category', $category_id, array('sequence'=>$cur_num), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.65', 'msg'=>'Unable to update the category', 'err'=>$rc['err']));
                }
            }
            $cur_num++;
        } 
        // If this category is found before it's new sequence, skip
        elseif( $cid == $category_id ) {
            continue;
        }
        if( $sequence != $cur_num ) {
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sponsors.category', $cid, array('sequence'=>$cur_num), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.66', 'msg'=>'Unable to update the category', 'err'=>$rc['err']));
            }
        }
        $cur_num++;
    }
    if( $new_seq >= $cur_num && $category_id > 0 && $categories[$category_id] != $cur_num ) {
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sponsors.category', $category_id, array('sequence'=>$cur_num), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.67', 'msg'=>'Unable to update the category', 'err'=>$rc['err']));
        }
    }

    return array('stat'=>'ok');
}
?>

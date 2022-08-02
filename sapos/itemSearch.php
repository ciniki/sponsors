<?php
//
// Description
// ===========
// This function will search the sponsorship packages for the ciniki.sapos module.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_sponsors_sapos_itemSearch($ciniki, $tnid, $args) {

    if( $args['start_needle'] == '' ) {
        return array('stat'=>'ok', 'items'=>array());
    }

//    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
//    $date_format = ciniki_users_dateFormat($ciniki);

    $args['start_needle'] = preg_replace("/ +/", '%', $args['start_needle']);

    
    //
    // Search for packages that match the name
    //
    $strsql = "SELECT packages.id, "
        . "packages.name, "
        . "packages.invoice_code, "
        . "packages.invoice_name, "
        . "packages.flags, "
        . "packages.category, "
        . "packages.object, "
        . "packages.object_id, "
        . "packages.amount "
        . "FROM ciniki_sponsor_packages AS packages "
        . "WHERE packages.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "HAVING ("
            . "packages.category LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR packages.category LIKE '%" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR packages.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR packages.name LIKE '%" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR packages.invoice_name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR packages.invoice_name LIKE '%" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
        . "ORDER BY packages.invoice_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sponsors', array(
        array('container'=>'packages', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'invoice_code', 'invoice_name', 'flags', 'category', 'object', 'object_id', 'amount')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.29', 'msg'=>'Unable to load packages', 'err'=>$rc['err']));
    }
    $packages = isset($rc['packages']) ? $rc['packages'] : array();

    $items = array();
    foreach($packages as $pid => $package) {
        $items[] = array('item'=>array(
            'status'=>0,
            'category' => $package['category'],
            'code' => $package['invoice_code'],
            'description' => ($package['invoice_name'] != '' ? $package['invoice_name'] : $package['name']),
            'object'=>'ciniki.sponsors.package',
            //'object'=>'ciniki.sponsors.package.' . $package['object'],
            'object_id'=>$package['id'],
            'description'=>$package['invoice_name'],
            'quantity'=>1,
            'flags'=>0x08,
            'unit_amount'=>$package['amount'],
            'unit_discount_amount'=>0,
            'unit_discount_percentage'=>0,
            'taxtype_id'=>0, 
            'price_id'=>0,
            'notes'=>'',
            ));
    }

    return array('stat'=>'ok', 'items'=>$items);        
}
?>

<?php
//
// Description
// ===========
// This function will be a callback when an item is added to ciniki.sapos.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_sponsors_sapos_itemLookup($ciniki, $tnid, $args) {

    if( !isset($args['object']) || $args['object'] == ''
        || !isset($args['object_id']) || $args['object_id'] == '' 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.32', 'msg'=>'No sponsor package specified.'));
    }

    //
    // An event was added to an invoice item, get the details and see if we need to 
    // create a registration for this event
    //
    //if( strncmp($args['object'], 'ciniki.sponsors.package.', 24) == 0 ) {
    if( $args['object'] == 'ciniki.sponsors.package' ) {
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
            . "AND packages.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'package');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.34', 'msg'=>'Unable to load package', 'err'=>$rc['err']));
        }
        if( !isset($rc['package']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.35', 'msg'=>'Unable to find requested package'));
        }
        $package = $rc['package'];
        
        $item = array(
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
            );

        return array('stat'=>'ok', 'item'=>$item);
    }

    return array('stat'=>'ok');
}
?>

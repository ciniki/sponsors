<?php
//
// Description
// ===========
// Since the donation packages are part of the accounting module, they must be in here as a hook even though 
// it's called from the same module it makes the code cleaner.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_sponsors_sapos_cartItemLookup($ciniki, $tnid, $customer, $args) {

    if( !isset($args['object']) || $args['object'] == '' || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.36', 'msg'=>'No product specified.'));
    }


    //
    // Lookup the requested product if specified along with a price_id
    //
    if( $args['object'] == 'ciniki.sponsors.package' 
        && isset($args['object_id']) && is_numeric($args['object_id']) && $args['object_id'] > 0 
        ) {
        
        //
        // Get the details about the donation package
        //
        $strsql = "SELECT packages.id, "
            . "packages.name, "
            . "packages.subname, "
            . "packages.permalink, "
            . "packages.invoice_name, "
            . "packages.flags, "
            . "packages.category, "
            . "packages.sequence, "
            . "packages.amount, "
            . "packages.primary_image_id, "
            . "packages.synopsis, "
            . "packages.description "
            . "FROM ciniki_sponsor_packages AS packages "
            . "WHERE packages.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND packages.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND (flags&0x01) = 0x01 "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'package');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.41', 'msg'=>'Sponsorship Package not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['package']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.42', 'msg'=>'Unable to find Sponsorship Package'));
        }
        $package = $rc['package'];

        //
        // Setup the product
        //
        $product = array(
            'flags'=>0x8000,
            'price_id'=>0,
            'code'=>'',
            'description'=>$package['invoice_name'],
            'quantity'=>1,
            'object'=>'ciniki.sponsors.package',
            'object_id'=>$package['id'],
            'unit_discount_amount'=>0,
            'unit_discount_percentage'=>0,
            'taxtype_id'=>0,
            );
        if( ($package['flags']&0x02) == 0x02 ) {    
            $product['unit_amount'] = $package['amount'];
        } elseif( isset($args['user_amount']) && is_numeric($args['user_amount']) ) {
            $product['unit_amount'] = $args['user_amount'];
        } else {
            $product['unit_amount'] = 0;
        }

        return array('stat'=>'ok', 'item'=>$product);
    }

    error_log('ERR: Invalid Object: ' . $args['object'] . ' ID: ' . $args['object_id']);
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.38', 'msg'=>'No product specified.'));        
}
?>

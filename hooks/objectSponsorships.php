<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sponsors_hooks_objectSponsorships($ciniki, $tnid, $args) {

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Check for expenses
    //
    if( isset($args['object']) && $args['object'] != '' 
        && isset($args['object_id']) && $args['object_id'] != ''
        ) {

        //
        // Get the list of sponsorships via packages
        //
        $strsql = "SELECT items.id, "
            . "invoices.invoice_date, "
            . "customers.display_name, "
            . "items.total_amount "
            . "FROM ciniki_sponsor_packages AS packages "
            . "INNER JOIN ciniki_sapos_invoice_items AS items ON ("
                . "packages.id = items.object_id "
                . "AND items.object = 'ciniki.sponsors.package' "
                . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_sapos_invoices AS invoices ON ("
                . "items.invoice_id = invoices.id "
                . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIn ciniki_customers AS customers ON ("
                . "invoices.customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE packages.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND packages.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
            . "AND packages.object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
            array('container'=>'sponsorships', 'fname'=>'id', 
                'fields'=>array('id', 'invoice_date', 'display_name', 'total_amount'),
                'utctotz'=>array('invoice_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $sponsorships = isset($rc['sponsorships']) ? $rc['sponsorships'] : array();

        $total_amount = 0;
        foreach($sponsorships as $sid => $sponsorship) {
            $total_amount += $sponsorship['total_amount'];
//            $sponsorships[$sid]['total_amount_display'] = '$' . number_format($sponsorship['total_amount'], 2);
        }

        return array('stat'=>'ok', 'sponsorships'=>$sponsorships, 'total'=>$total_amount);
    }

    return array('stat'=>'ok');
}
?>

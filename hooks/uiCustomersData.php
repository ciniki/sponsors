<?php
//
// Description
// -----------
// This function will return the data for customer(s) to be displayed in the IFB display panel.
// The request might be for 1 individual, or multiple customer ids for a family.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_sponsors_hooks_uiCustomersData($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    //
    // Get the time information for tenant and user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'ags', 'private', 'maps');
    $rc = ciniki_ags_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];
    
    //
    // Setup current date in tenant timezone
    //
    $cur_date = new DateTime('now', new DateTimeZone($intl_timezone));

    //
    // Default response
    //
    $rsp = array('stat'=>'ok', 'tabs'=>array());

    $strsql = "SELECT items.id, "
        . "invoices.id AS invoice_id, "
        . "invoices.invoice_number, "
        . "invoices.invoice_date, "
        . "packages.name, "
        . "packages.object, "
        . "packages.object_id, "
        . "items.total_amount " 
        . "FROM ciniki_sapos_invoices AS invoices "
        . "INNER JOIN ciniki_sapos_invoice_items AS items ON ("
            . "invoices.id = items.invoice_id "
            . "AND items.object = 'ciniki.sponsors.package' "
            . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_sponsor_packages AS packages ON ("
            . "items.object_id = packages.id "
            . "AND packages.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE invoices.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY invoices.invoice_date DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
        array('container'=>'sponsorships', 'fname'=>'id', 
            'fields'=>array('id', 'invoice_number', 'invoice_id', 'invoice_date', 'name', 'object', 'object_id', 'total_amount'),
            'utctotz'=>array('invoice_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sponsorships = isset($rc['sponsorships']) ? $rc['sponsorships'] : array();

    foreach($sponsorships as $sid => $sponsorship) {
        $sponsorships[$sid]['total_amount'] = '$' . number_format($sponsorship['total_amount'], 2);
        if( $sponsorship['object'] != '' ) {
            list($pkg, $mod, $obj) = explode('.', $sponsorship['object']);
            $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'sponsorshipObjects');
            if( $rc['stat'] == 'ok' && isset($rc['function_call']) ) {
                $fn = $rc['function_call'];
                $rc = $fn($ciniki, $tnid, array(
                    'object' => $sponsorship['object'],
                    'object_id' => $sponsorship['object_id'],
                    ));
                if( $rc['stat'] == 'ok' && isset($rc['objects']["{$sponsorship['object']}.{$sponsorship['object_id']}"]) ) {
                    $sponsorships[$sid]['attached_to'] = $rc['objects']["{$sponsorship['object']}.{$sponsorship['object_id']}"]['name'];
                }
            }
        }
    }

    $sections = array(
        'ciniki.sponsors.sponsorships' => array(
            'label' => 'Sponsorships',
            'type' => 'simplegrid', 
            'num_cols' => 5,
            'headerValues' => array('Invoice #', 'Date', 'Package', 'For', 'Amount'),
            'cellClasses' => array('', ''),
            'noData' => 'No sponsorships',
            'data' => $sponsorships,
            'cellValues' => array(
                '0' => 'd.invoice_number;',
                '1' => 'd.invoice_date;',
                '2' => 'd.name;',
                '3' => 'd.attached_to;',
                '4' => 'd.total_amount;',
                ),
            ),
        );

    //
    // Add a tab the customer UI data screen with the certificate list
    //
    $tab = array(
        'id' => 'ciniki.sponsors.sponsorships',
        'label' => 'Sponsorships',
        'sections' => $sections,
        );

    $rsp['tabs'][] = $tab;
    return $rsp;
}
?>

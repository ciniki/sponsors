<?php
//
// Description
// ===========
// This method will return all the information about an sponsor.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the sponsor is attached to.
// sponsor_id:      The ID of the sponsor to get the details for.
// 
// Returns
// -------
//
function ciniki_sponsors_sponsorGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'sponsor_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Sponsor'), 
        'sponsorships'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sponsorships'), 
        'donateditems'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Donated Items'), 
        'output'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Output'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'checkAccess');
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.sponsorGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $modules = $rc['modules'];

    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');


    if( $args['sponsor_id'] == 0 ) {
        $sponsor = array(
            'id' => 0,
            'title' => '',
            'permalink' => '',
            'level_id' => 0,
            'customer_id' => 0,
            'sequence' => 1,
            'summary' => '',
            'webflags' => 0,
            'url' => '',
            'primary_image_id' => 0,
            'excerpt' => '',
            'content' => '',
            'notes' => '',
            );
    } else {
        $strsql = "SELECT ciniki_sponsors.id, "
            . "ciniki_sponsors.title, "
            . "ciniki_sponsors.permalink, "
            . "ciniki_sponsors.level_id, "
            . "ciniki_sponsors.customer_id, "
            . "ciniki_sponsors.sequence, "
            . "ciniki_sponsors.summary, "
            . "ciniki_sponsors.webflags, "
            . "ciniki_sponsors.url, "
            . "ciniki_sponsors.primary_image_id, "
            . "ciniki_sponsors.excerpt, "
            . "ciniki_sponsors.content, "
            . "ciniki_sponsors.notes "
            . "FROM ciniki_sponsors "
            . "LEFT JOIN ciniki_sponsor_levels ON (ciniki_sponsors.level_id = ciniki_sponsor_levels.id "
                . "AND ciniki_sponsor_levels.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_sponsors.id = '" . ciniki_core_dbQuote($ciniki, $args['sponsor_id']) . "' "
            . "";
        
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sponsors', array(
            array('container'=>'sponsors', 'fname'=>'id', 'name'=>'sponsor',
                'fields'=>array('id', 'title', 'permalink', 'level_id', 'customer_id',
                    'sequence', 'summary', 'webflags', 'url', 'primary_image_id', 
                    'excerpt', 'content', 'notes',
                    )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['sponsors']) || !isset($rc['sponsors'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.17', 'msg'=>'Unable to find sponsor'));
        }
        $sponsor = $rc['sponsors'][0]['sponsor'];

        //
        // Get the list of categories the sponsor is in
        //
        $strsql = "SELECT category_id "
            . "FROM ciniki_sponsors_categories "
            . "WHERE ciniki_sponsors_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_sponsors_categories.sponsor_id = '" . ciniki_core_dbQuote($ciniki, $args['sponsor_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.foodmarket', 'categories', 'category_id');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $sponsor['categories'] = isset($rc['categories']) ? $rc['categories'] : array();

        if( $sponsor['customer_id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
            $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], array(
                'customer_id' => $sponsor['customer_id'],
                'membership' => 'yes',
                'phones' => 'yes',
                'addresses' => 'yes',
                'companydetails' => 'yes', 
                ));
            $sponsor['customer_details'] = isset($rc['details']) ? $rc['details'] : array();
        }

        //
        // Get the list of employees
        //
        $strsql = "SELECT contacts.id, "
            . "contacts.customer_id, "
            . "contacts.label, "
            . "customers.display_name "
            . "FROM ciniki_sponsor_contacts AS contacts "
            . "LEFT JOIN ciniki_customers AS customers ON ("
                . "contacts.customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE contacts.sponsor_id = '" . ciniki_core_dbQuote($ciniki, $args['sponsor_id']) . "' "
            . "AND contacts.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY customers.sort_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
            array('container'=>'contacts', 'fname'=>'id', 
                'fields'=>array(
                    'id', 'customer_id', 'label', 'display_name'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.60', 'msg'=>'Unable to load contacts', 'err'=>$rc['err']));
        }
        $sponsor['contacts'] = isset($rc['contacts']) ? $rc['contacts'] : array();

        //
        // Get the list of sponsorships
        //
        if( isset($args['sponsorships']) && $args['sponsorships'] == 'yes' 
            && ciniki_core_checkModuleFlags($ciniki, 'ciniki.sponsors', 0x10) // Packages enabled
            ) {
            $strsql = "SELECT items.id, "
                . "invoices.id AS invoice_id, "
                . "invoices.invoice_date, "
                . "packages.name, "
                . "packages.object, "
                . "packages.object_id, "
                . "items.total_amount " 
                . "FROM ciniki_sapos_invoices AS invoices "
                . "INNER JOIN ciniki_sapos_invoice_items AS items ON ("
                    . "invoices.id = items.invoice_id "
                    . "AND items.object = 'ciniki.sponsors.package' "
                    . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "INNER JOIN ciniki_sponsor_packages AS packages ON ("
                    . "items.object_id = packages.id "
                    . "AND packages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE invoices.customer_id = '" . ciniki_core_dbQuote($ciniki, $sponsor['customer_id']) . "' "
                . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY invoices.invoice_date DESC "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
                array('container'=>'sponsorships', 'fname'=>'id', 
                    'fields'=>array('id', 'invoice_id', 'invoice_date', 'name', 'object', 'object_id', 'total_amount'),
                    'utctotz'=>array('invoice_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $sponsor['sponsorships'] = isset($rc['sponsorships']) ? $rc['sponsorships'] : array();

            foreach($sponsor['sponsorships'] as $sid => $sponsorship) {
                $sponsor['sponsorships'][$sid]['total_amount_display'] = '$' . number_format($sponsorship['total_amount'], 2);
                if( $sponsorship['object'] != '' ) {
                    list($pkg, $mod, $obj) = explode('.', $sponsorship['object']);
                    $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'sponsorshipObjects');
                    if( $rc['stat'] == 'ok' && isset($rc['function_call']) ) {
                        $fn = $rc['function_call'];
                        $rc = $fn($ciniki, $args['tnid'], array(
                            'object' => $sponsorship['object'],
                            'object_id' => $sponsorship['object_id'],
                            ));
                        if( $rc['stat'] == 'ok' && isset($rc['objects']["{$sponsorship['object']}.{$sponsorship['object_id']}"]) ) {
                            $sponsor['sponsorships'][$sid]['attached_to'] = $rc['objects']["{$sponsorship['object']}.{$sponsorship['object_id']}"]['name'];
                        }
                    }
                }
            }
        }

        //
        // Get the list of donated items
        //
        if( isset($args['donateditems']) && $args['donateditems'] == 'yes' 
            && ciniki_core_checkModuleActive($ciniki, 'ciniki.iks') 
            && $sponsor['customer_id'] > 0 
            ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'iks', 'hooks', 'uiCustomersData');
            $rc = ciniki_iks_hooks_uiCustomersData($ciniki, $args['tnid'], array(
                'customer_id' => $sponsor['customer_id'],
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.54', 'msg'=>'Unable to load donated items', 'err'=>$rc['err']));
            }
            $sponsor['donateditems'] = array();
            if( isset($rc['tabs'][0]['sections']['ciniki.iks.donations']['data']) ) {
                $sponsor['donateditems'] = $rc['tabs'][0]['sections']['ciniki.iks.donations']['data'];
            }
        }
    }

    $rsp = array('stat'=>'ok', 'sponsor'=>$sponsor, 'levels'=>array(), 'categories'=>array());

    //
    // Get the levels available
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sponsors', 0x01) ) {
        $strsql = "SELECT id, name "
            . "FROM ciniki_sponsor_levels "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY sequence "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
            array('container'=>'levels', 'fname'=>'id', 'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['levels'] = isset($rc['levels']) ? $rc['levels'] : array();
    }

    //
    // Get the categories available
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sponsors', 0x04) ) {
        $strsql = "SELECT id, name "
            . "FROM ciniki_sponsor_categories "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY sequence "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
            array('container'=>'categories', 'fname'=>'id', 'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['categories'] = isset($rc['categories']) ? $rc['categories'] : array();
    }
    
    //
    // Check if PDF output
    //
    if( isset($args['output']) && $args['output'] == 'pdf' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'templates', 'sponsorsPDF');
        $rc = ciniki_sponsors_templates_sponsorsPDF($ciniki, $args['tnid'], array(
            'title' => '',
            'sponsors' => array($rsp['sponsor']),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.71', 'msg'=>'Unable to generate PDF', 'err'=>$rc['err']));
        }
        if( isset($rc['pdf']) ) {
            $rc['pdf']->Output($rc['filename'] . '.pdf', 'I');
            return array('stat'=>'exit');
        }
    }

    return $rsp;
}
?>

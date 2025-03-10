<?php
//
// Description
// -----------
// This method will return the list of sponsors for a tenant.  
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get sponsors for.
//
// Returns
// -------
//
function ciniki_sponsors_sponsors($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'level_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Level'), 
        'category_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'), 
        'output'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Output'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'checkAccess');
    $ac = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.sponsors');
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }
    $modules = $ac['modules'];

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    //
    // Load the sponsors
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sponsors', 0x04) && isset($args['category_id']) ) {
        $strsql = "SELECT sponsors.id, "
            . "sponsors.customer_id, "
            . "sponsors.title, "
            . "sponsors.summary, "
            . "IFNULL(scats.category_id, 0) AS cid "
            . "FROM ciniki_sponsors AS sponsors "
            . "LEFT JOIN ciniki_sponsors_categories AS scats ON ("
                . "sponsors.id = scats.sponsor_id "
                . "AND scats.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        if( $args['category_id'] > 0 ) {
            $strsql .= "HAVING cid = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' ";
        } else {
            $strsql .= "HAVING cid = 0 ";
        }
        $strsql .= "ORDER BY sponsors.sequence, sponsors.title ";
    } else {
        $strsql = "SELECT sponsors.id, "
            . "sponsors.customer_id,  "
            . "sponsors.title,  "
            . "sponsors.summary  "
            . "FROM ciniki_sponsors AS sponsors "
            . "WHERE sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sponsors', 0x01) && isset($args['level_id']) && $args['level_id'] != '' ) {
            $strsql .= "AND sponsors.level_id = '" . ciniki_core_dbQuote($ciniki, $args['level_id']) . "' ";
        }
        $strsql .= "ORDER BY sponsors.sequence, sponsors.title "
            . "";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
        array('container'=>'sponsors', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'title', 'summary')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sponsors = isset($rc['sponsors']) ? $rc['sponsors'] : array();
    $totals = array('sponsorship_amount'=>0, 'donations_amount'=>0, 'inkind_value'=>0, 'inkind_amount'=>0);

    //
    // Load the category and sum the sponsorships and in kind donations
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sponsors', 0x04) && isset($args['category_id']) && $args['category_id'] > 0 ) {
        $strsql = "SELECT categories.id, "
            . "categories.name, "
            . "categories.start_dt, "
            . "categories.end_dt "
            . "FROM ciniki_sponsor_categories AS categories "
            . "WHERE categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sponsors', 'category');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.69', 'msg'=>'Unable to load category', 'err'=>$rc['err']));
        }
        if( !isset($rc['category']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.70', 'msg'=>'Unable to find requested category'));
        }
        $category = $rc['category'];
      
/*        if( $category['start_dt'] != '0000-00-00 00:00:00' 
            && $category['end_dt'] != '0000-00-00 00:00:00' 
            ) { */
            //
            // Get the sponsorships between the date
            //
            $strsql = "SELECT items.id, "
                . "invoices.customer_id, "
                . "invoices.id AS invoice_id, "
                . "invoices.invoice_date, "
                . "packages.name, "
                . "packages.object, "
                . "packages.object_id, "
                . "SUM(items.total_amount) AS total_amount " 
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
                . "WHERE invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
            if( $category['start_dt'] != '0000-00-00 00:00:00' 
                && $category['end_dt'] != '0000-00-00 00:00:00' 
                ) {
                $strsql .= "AND invoices.invoice_date >= '" . ciniki_core_dbQuote($ciniki, $category['start_dt']) . "' "
                    . "AND invoices.invoice_date < '" . ciniki_core_dbQuote($ciniki, $category['end_dt']) . "' ";
            }
            $strsql .= "GROUP BY invoices.customer_id "
                . "ORDER BY invoices.invoice_date DESC "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
            $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sponsors', array(
                array('container'=>'sponsorships', 'fname'=>'customer_id', 
                    'fields'=>array('id', 'invoice_id', 'invoice_date', 'name', 'object', 'object_id', 'total_amount'),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $sponsorships = isset($rc['sponsorships']) ? $rc['sponsorships'] : array();

            //
            // Get the donations
            //
            $strsql = "SELECT invoices.customer_id, "
                . "items.id AS item_id, "
                . "items.flags AS item_flags, "
                . "items.quantity, "
                . "items.unit_donation_amount, "
                . "items.total_amount "
                . "FROM ciniki_sapos_invoices AS invoices "
                . "INNER JOIN ciniki_sapos_invoice_items AS items ON ("
                    . "invoices.id = items.invoice_id "
                    . "AND (items.flags&0x8800) > 0 "   // Full or partial donation
                    . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND invoices.invoice_type IN (10,30) "
                . "";
            if( $category['start_dt'] != '0000-00-00 00:00:00' 
                && $category['end_dt'] != '0000-00-00 00:00:00' 
                ) {
                $strsql .= "AND invoices.invoice_date >= '" . ciniki_core_dbQuote($ciniki, $category['start_dt']) . "' "
                    . "AND invoices.invoice_date < '" . ciniki_core_dbQuote($ciniki, $category['end_dt']) . "' ";
            }
            $strsql .= "ORDER BY invoices.customer_id "
                . "";
            $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sapos', array(
                array('container'=>'customers', 'fname'=>'customer_id', 
                    'fields'=>array('id'=>'customer_id'),
                    ), 
                array('container'=>'items', 'fname'=>'item_id', 
                    'fields'=>array('id'=>'item_id', 'flags'=>'item_flags', 'quantity', 'unit_donation_amount', 'total_amount'),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $donations = isset($rc['customers']) ? $rc['customers'] : array();
            foreach($donations as $iid => $cust) {
                $donations[$iid]['donation_amount'] = 0;
                if( isset($cust['items']) ) {
                    foreach($cust['items'] as $item) {
                        if( ($item['flags']&0x0800) == 0x0800 ) {
                            $donations[$iid]['donation_amount'] = bcadd($donations[$iid]['donation_amount'], ($item['quantity'] * $item['unit_donation_amount']), 6);
                        } elseif( ($item['flags']&0x8000) == 0x8000 ) {
                            $donations[$iid]['donation_amount'] = bcadd($donations[$iid]['donation_amount'], $item['total_amount'], 6);
                        } 
                    }
                }
            }

            //
            // Get the inkind donations between the date range
            //
/*            $strsql = "SELECT items.donor_customer_id, "
                . "SUM(items.unit_amount) AS value, "
                . "SUM(IFNULL(sales.tenant_amount, '0')) AS tenant_amount, "
                . "SUM(IFNULL(sales.total_amount, '0')) AS total_amount "
                . "FROM ciniki_ags_items AS items "
//                . "LEFT JOIN ciniki_ags_exhibits AS exhibits ON ("
//                    . "sales.exhibit_id = exhibits.id "
//                    . "AND exhibits.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//                    . ") "
                . "LEFT JOIN ciniki_ags_item_sales AS sales ON ("
                    . "sales.item_id = items.id "
                    . "AND sales.sell_date >= '" . ciniki_core_dbQuote($ciniki, $category['start_dt']) . "' "
                    . "AND sales.sell_date <= '" . ciniki_core_dbQuote($ciniki, $category['end_dt']) . "' "
                    . "AND sales.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND (items.flags&0x60) > 0 "
                . "AND items.donor_customer_id > 0 "
                . "AND (tenant_amount > 0 "
                    . "OR ("
                        . "items.date_added >= '" . ciniki_core_dbQuote($ciniki, $category['start_dt']) . "' "
                        . "AND items.date_added <= '" . ciniki_core_dbQuote($ciniki, $category['end_dt']) . "' "
                        . ") "
                    . ") "
                . "GROUP BY items.donor_customer_id "
                . "ORDER BY sales.receipt_number, sales.sell_date, items.code, items.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
            $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.ags', array(
                array('container'=>'items', 'fname'=>'donor_customer_id', 
                    'fields'=>array('value', 'tenant_amount', 'total_amount')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.ags.367', 'msg'=>'', 'err'=>$rc['err']));
            }
            $inkind = isset($rc['items']) ? $rc['items'] : array();
*/
            //
            // Get the inkind donations between the date range
            //
            $strsql = "SELECT items.customer_id, "
                . "SUM(items.value_amount) AS value, "
                . "SUM(items.sold_amount) AS total_amount "
                . "FROM ciniki_iks_items AS items "
                . "LEFT JOIN ciniki_iks_products AS products ON ("
                    . "items.product_id = products.id "
                    . "AND products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_iks_event_products AS ep ON ("
                    . "products.id = ep.product_id "
                    . "AND ep.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_iks_events AS events ON ("
                    . "ep.event_id = events.id "
                    . "AND events.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND items.customer_id > 0 ";
            if( $category['start_dt'] != '0000-00-00 00:00:00' 
                && $category['end_dt'] != '0000-00-00 00:00:00' 
                ) {
                $strsql .= "AND (("
                    . "items.status < 70 "
                    . "AND items.donated_date >= '" . ciniki_core_dbQuote($ciniki, $category['start_dt']) . "' "
                    . "AND items.donated_date <= '" . ciniki_core_dbQuote($ciniki, $category['end_dt']) . "' "
                    . ") OR ("
                    . "events.sales_start_dt >= '" . ciniki_core_dbQuote($ciniki, $category['start_dt']) . "' "
                    . "AND events.sales_end_dt <= '" . ciniki_core_dbQuote($ciniki, $category['end_dt']) . "' "
                    . ")) ";
            }
            $strsql .= "GROUP BY items.customer_id "
                . "ORDER BY items.customer_id, products.code, products.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
            $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.iks', array(
                array('container'=>'items', 'fname'=>'customer_id', 'fields'=>array('value', 'total_amount')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.ags.367', 'msg'=>'', 'err'=>$rc['err']));
            }
            $inkind = isset($rc['items']) ? $rc['items'] : array();

            //
            // Link the sponsorships and in kind to sponsors
            //
            foreach($sponsors as $sid => $sponsor) {
                $sponsors[$sid]['sponsorship_amount_display'] = '';
                $sponsors[$sid]['donations_amount_display'] = '';
                $sponsors[$sid]['inkind_value_display'] = '';
                $sponsors[$sid]['inkind_amount_display'] = '';
                if( $sponsor['customer_id'] > 0 && isset($sponsorships[$sponsor['customer_id']]) ) {
                    $sponsors[$sid]['sponsorship_amount'] = $sponsorships[$sponsor['customer_id']]['total_amount'];
                    $sponsors[$sid]['sponsorship_amount_display'] = '$' . number_format($sponsorships[$sponsor['customer_id']]['total_amount'], 0);
                    $totals['sponsorship_amount'] += $sponsorships[$sponsor['customer_id']]['total_amount'];
                }
                if( $sponsor['customer_id'] > 0 && isset($donations[$sponsor['customer_id']]) ) {
                    $sponsors[$sid]['donations_amount'] = $donations[$sponsor['customer_id']]['donation_amount'];
                    $sponsors[$sid]['donations_amount_display'] = '$' . number_format($donations[$sponsor['customer_id']]['donation_amount'], 0);
                    $totals['donations_amount'] += $donations[$sponsor['customer_id']]['donation_amount'];
                }
                if( $sponsor['customer_id'] > 0 && isset($inkind[$sponsor['customer_id']]) && $inkind[$sponsor['customer_id']]['value'] > 0 ) {
                    $sponsors[$sid]['inkind_value'] = $inkind[$sponsor['customer_id']]['value'];
                    $sponsors[$sid]['inkind_value_display'] = '$' . number_format($inkind[$sponsor['customer_id']]['value'], 0);
                    $totals['inkind_value'] += $inkind[$sponsor['customer_id']]['value'];
                }
                if( $sponsor['customer_id'] > 0 && isset($inkind[$sponsor['customer_id']]) && $inkind[$sponsor['customer_id']]['total_amount'] > 0 ) {
                    $sponsors[$sid]['inkind_amount'] = $inkind[$sponsor['customer_id']]['total_amount'];
                    $sponsors[$sid]['inkind_amount_display'] = '$' . number_format($inkind[$sponsor['customer_id']]['total_amount'], 0);
                    $totals['inkind_amount'] += $inkind[$sponsor['customer_id']]['total_amount'];
                }
            }
            //
            // Format totals
            //
            foreach($totals as $k => $v) {
                $totals[$k . '_display'] = '$' . number_format($v, 0);
            }
//        }

        //
        // Output to excel
        //
        if( isset($args['output']) && $args['output'] == 'excel' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'templates', 'sponsorsExcel');
            $rc = ciniki_sponsors_templates_sponsorsExcel($ciniki, $args['tnid'], array(
                'sponsors' => $sponsors,
                'category' => $category,
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['excel']) ) {
                //
                // Output the excel file
                //
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="' . $rc['filename'] . '.xls"');
                header('Cache-Control: max-age=0');
                
                $objWriter = PHPExcel_IOFactory::createWriter($rc['excel'], 'Excel5');
                $objWriter->save('php://output');

                return array('stat'=>'exit');
            }
        }



    }
    $rsp = array('stat'=>'ok', 'sponsors'=>$sponsors, 'totals'=>$totals);

    //
    // Get the list of levels
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sponsors', 0x01) ) {
        $strsql = "SELECT levels.id, "
            . "levels.name, "
            . "levels.sequence, "
            . "COUNT(sponsors.id) AS num_sponsors "
            . "FROM ciniki_sponsor_levels AS levels "
            . "LEFT JOIN ciniki_sponsors AS sponsors ON ("
                . "levels.id = sponsors.level_id "
                . "AND sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ")" 
            . "WHERE levels.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY levels.id "
            . "ORDER BY levels.sequence "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
            array('container'=>'levels', 'fname'=>'id', 'name'=>'level',
                'fields'=>array('id', 'name', 'sequence', 'num_sponsors')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['levels'] = isset($rc['levels']) ? $rc['levels'] : array();

        //
        // Check for sponsors with no sponsorship level
        //
        $strsql = "SELECT 'sponsors', COUNT(*) "    
            . "FROM ciniki_sponsors "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND level_id = 0 "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sponsors', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num']['sponsors']) && $rc['num']['sponsors'] > 0 ) {
            $rsp['levels'][] = array(
                'id'=>'0', 
                'name'=>'No sponsorship level', 
                'sequence'=>'0', 
                'num_sponsors'=>$rc['num']['sponsors']
                );
        }
    }

    //
    // Get the list of categories
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sponsors', 0x04) ) {
        $strsql = "SELECT categories.id, "
            . "categories.name, "
            . "categories.sequence, "
            . "categories.start_dt, "
            . "categories.end_dt, "
            . "COUNT(sponsors.id) AS num_sponsors "
            . "FROM ciniki_sponsor_categories AS categories "
            . "LEFT JOIN ciniki_sponsors_categories AS scats ON ("
                . "categories.id = scats.category_id "
                . "AND scats.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_sponsors AS sponsors ON ("
                . "scats.sponsor_id = sponsors.id "
                . "AND sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ")" 
            . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY categories.id "
            . "ORDER BY categories.sequence "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
            array('container'=>'categories', 'fname'=>'id', 'name'=>'level',
                'fields'=>array('id', 'name', 'sequence', 'start_dt', 'end_dt', 'num_sponsors')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['categories'] = isset($rc['categories']) ? $rc['categories'] : array();

        foreach($rsp['categories'] as $cid => $cat) {
            $rsp['categories'][$cid]['date_range'] = '';
            if( $cat['start_dt'] != '' && $cat['end_dt'] != '' 
                && $cat['start_dt'] != '0000-00-00 00:00:00' && $cat['end_dt'] != '0000-00-00 00:00:00' 
                ) {
                $start_dt = new DateTime($cat['start_dt'], new DateTimezone('UTC'));
                $start_dt->setTimezone(new DateTimezone($intl_timezone));
                $end_dt = new DateTime($cat['end_dt'], new DateTimezone('UTC'));
                $end_dt->setTimezone(new DateTimezone($intl_timezone));
                if( $start_dt->format('Y') == $end_dt->format('Y')) {
                    $rsp['categories'][$cid]['date_range'] = $start_dt->format('M j') . ' - ' . $end_dt->format('M j, Y');
                } else {
                    $rsp['categories'][$cid]['date_range'] = $start_dt->format('M j, Y') . ' - ' . $end_dt->format('M j, Y');
                }
            }
        }

        //
        // Check for sponsors with no sponsorship level
        //
        $strsql = "SELECT 'sponsors', "
            . "COUNT(sponsors.id) AS num_sponsors, "
            . "IFNULL(scats.sponsor_id, '') AS sid "    
            . "FROM ciniki_sponsors AS sponsors "
            . "LEFT JOIN ciniki_sponsors_categories AS scats ON ("
                . "sponsors.id = scats.sponsor_id "
                . "AND scats.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY sid "
            . "HAVING sid = '' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sponsors', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num']['sponsors']) && $rc['num']['sponsors'] > 0 ) {
            $rsp['categories'][] = array(
                'id'=>'0', 
                'name'=>'No Category', 
                'sequence'=>'0', 
                'num_sponsors'=>$rc['num']['sponsors']
                );
        }
    }


    return $rsp;
}
?>

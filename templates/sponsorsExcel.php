<?php
//
// Description
// ===========
// This function will produce a PDF of 1-? of submissions.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sponsors_templates_sponsorsExcel(&$ciniki, $tnid, $args) {

    $filename = "Sponsors - " . $args['category']['name'];

    //
    // Load tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
    $rc = ciniki_tenants_tenantDetails($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {   
        $tenant_details = $rc['details'];
    } else {
        $tenant_details = array();
    }

    //
    // Create excel file
    //
    require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
    $objPHPExcel = new PHPExcel();
    $objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

    $col = 0;
    $row = 1;
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Sponsor', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Sponsorships', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'In Kind Value', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Sold Amount', false);
    $objPHPExcelWorksheet->getStyle('A1:D1')->getFont()->setBold(true);
    $col = 0;
    $row++;

    $num = 0;
    foreach($args['sponsors'] as $sponsor) {
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $sponsor['title'], false);
        if( isset($sponsor['sponsorship_amount']) ) {
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $sponsor['sponsorship_amount'], false);
        } else {
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, '', false);
        }
        if( isset($sponsor['inkind_value']) ) {
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $sponsor['inkind_value'], false);
        } else {
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, '', false);
        }
        if( isset($sponsor['inkind_amount']) ) {
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $sponsor['inkind_amount'], false);
        } else {
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, '', false);
        }
        $col = 0;
        $row++;
    }

    //
    // Add totals
    //
    $col = 0;
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Totals', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, "=SUM(B2:B" . ($row-1) . ')', false); 
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, "=SUM(C2:C" . ($row-1) . ')', false); 
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, "=SUM(D2:D" . ($row-1) . ')', false); 
    $objPHPExcelWorksheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
    $row++;

    for($i = 0; $i < 4; $i++) {
        $objPHPExcelWorksheet->getColumnDimension(chr($i+65))->setAutoSize(true);
    }
    $objPHPExcelWorksheet->freezePaneByColumnAndRow(0, 2);
    $objPHPExcelWorksheet->getStyle('B2:B' . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
    $objPHPExcelWorksheet->getStyle('C2:C' . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
    $objPHPExcelWorksheet->getStyle('D2:D' . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);

    $filename = preg_replace("/[^a-zA-Z0-9\-]/", '', $filename);
    return array('stat'=>'ok', 'filename'=>$filename, 'excel'=>$objPHPExcel);
}
?>

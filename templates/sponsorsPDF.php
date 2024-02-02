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
function ciniki_sponsors_templates_sponsorsPDF(&$ciniki, $tnid, $args) {

    $filename = "Sponsors";

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
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class SPONSORS_PDF extends TCPDF {
        public $left_margin = 18;
        public $right_margin = 18;
        public $top_margin = 8;
        //Page header
        public $title = 'Sponsors';
        public $header_image = null;
        public $header_name = '';
        public $header_addr = array();
        public $header_details = array();
        public $header_height = 7; // height of title
        public $tenant_details = array();
        public $courses_settings = array();
        public $footer_text = '';

        public function Header() {  
            if( $this->title != '' ) {
                $this->setFont('', 'B', 14);
                $this->MultiCell(180, 10, $this->title, 0, 'C', 0, 1, '', '', true, 0, false, true, 10, 'T');
            }
        }

        // Page footer
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(150, 10, $this->footer_text,
                0, false, 'L', 0, '', 0, false, 'T', 'M');
            $this->Cell(0, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 
                0, false, 'R', 0, '', 0, false, 'T', 'M');
        }
    }

    //
    // Start a new document
    //
    $pdf = new SPONSORS_PDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    $pdf->tenant_details = $tenant_details;

    //
    // Setup the title
    //
    if( isset($args['title']) ) {
        $pdf->title = $args['title'];
    }

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    if( $pdf->title == '' ) {
        $pdf->SetTitle('Sponsors - ' . $args['sponsors'][0]['title']);
    } else {
        $pdf->SetTitle($pdf->title);
    }
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->top_margin + $pdf->header_height, $pdf->right_margin);
    $pdf->SetHeaderMargin($pdf->top_margin);

    // set font and pdf features
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetCellPaddings(0, 1, 0, 1);
    $pdf->SetFillColor(235);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(215);
    $pdf->SetLineWidth(0.10);

    //
    // Output the sponsors
    //
    foreach($args['sponsors'] as $sponsor) {
        $pdf->title = $sponsor['title'];

        //
        // Create the PDF of the submission
        //
        $pdf->AddPage();

        //
        // Output Main Contant info
        //
        $pdf->SetCellPaddings(2, 1, 1, 1);
        $pdf->SetFont('', 'B', 12);
        $pdf->MultiCell(180, 0, 'Account', 0, 'L', 0, 1);
        $pdf->SetFont('', '', 10);
        $pdf->SetCellPadding(2);
        $w = array(40, 140);
        foreach($sponsor['customer_details'] as $detail ) {
            $pdf->SetFont('', 'B', 10);
            $lh = $pdf->getStringHeight($w[1], $detail['value']);
            $pdf->MultiCell($w[0], $lh, $detail['label'], 1, 'R', 1, 0);
            $pdf->SetFont('', '', 10);
            $pdf->MultiCell($w[1], $lh, $detail['value'], 1, 'L', 0, 1);
        }

        //
        // FIXME: Output other contacts
        //
       
        //
        // Output notes
        //
        $pdf->Ln(5);
        $pdf->SetCellPadding(1);
        $pdf->SetFont('', 'B', 12);
        $pdf->MultiCell(180, 0, 'Notes', 0, 'L', 0, 1);
        $pdf->SetFont('', '', 10);
        $pdf->MultiCell(180, 0, $sponsor['notes'], 0, 'L');

        //
        // Output sponsorships
        //
        if( isset($sponsor['sponsorships']) && count($sponsor['sponsorships']) > 0 ) {
            $pdf->Ln(5);
            $pdf->SetCellPadding(1);
            $pdf->SetFont('', 'B', 12);
            $pdf->MultiCell(180, 0, 'Sponsorships', 0, 'L', 0, 1);
            $pdf->SetFont('', '', 10);
            $w = array(25, 55, 80, 25);
            $lh = 0;
            $pdf->SetCellPadding(2);
            $pdf->SetFont('', 'B', 10);
            $pdf->MultiCell($w[0], $lh, 'Date', 1, 'L', 1, 0);
            $pdf->MultiCell($w[1], $lh, 'Package', 1, 'L', 1, 0);
            $pdf->MultiCell($w[2], $lh, 'Attached To', 1, 'L', 1, 0);
            $pdf->MultiCell($w[3], $lh, 'Amount', 1, 'L', 1, 1);
            $pdf->SetFont('', '', 10);
            foreach($sponsor['sponsorships'] as $item) {    
                $lh = $pdf->getStringHeight($w[0], $item['invoice_date']);
                if( $lh < $pdf->getStringHeight($w[1], $item['name']) ) {
                    $lh = $pdf->getStringHeight($w[1], $item['name']);
                }
                if( $lh < $pdf->getStringHeight($w[2], $item['attached_to']) ) {
                    $lh = $pdf->getStringHeight($w[2], $item['attached_to']);
                }
                if( $lh < $pdf->getStringHeight($w[3], $item['total_amount_display']) ) {
                    $lh = $pdf->getStringHeight($w[3], $item['total_amount_display']);
                }
                $pdf->MultiCell($w[0], $lh, $item['invoice_date'], 1, 'L', 0, 0);
                $pdf->MultiCell($w[1], $lh, $item['name'], 1, 'L', 0, 0);
                $pdf->MultiCell($w[2], $lh, $item['attached_to'], 1, 'L', 0, 0);
                $pdf->MultiCell($w[3], $lh, $item['total_amount_display'], 1, 'L', 0, 1);
            }
        }

        //
        // Output in kind donations
        //
        if( isset($sponsor['donateditems']) && count($sponsor['donateditems']) > 0 ) {
            $pdf->Ln(5);
            $pdf->SetCellPadding(1);
            $pdf->SetFont('', 'B', 12);
            $pdf->MultiCell(180, 0, 'In Kind Donations', 0, 'L', 0, 1);
            $pdf->SetFont('', '', 10);
            $w = array(55, 21, 58, 21, 25);
            $lh = 0;
            $pdf->SetCellPadding(2);
            $pdf->SetFont('', 'B', 10);
            $pdf->MultiCell($w[0], $lh, 'Item', 1, 'L', 1, 0);
            $pdf->MultiCell($w[1], $lh, 'Value', 1, 'L', 1, 0);
            $pdf->MultiCell($w[2], $lh, 'Exhibit', 1, 'L', 1, 0);
            $pdf->MultiCell($w[3], $lh, 'Amount', 1, 'L', 1, 0);
            $pdf->MultiCell($w[4], $lh, 'Date', 1, 'L', 1, 1);
            $pdf->SetFont('', '', 10);
            foreach($sponsor['donateditems'] as $item) {    
                $lh = $pdf->getStringHeight($w[0], $item['value']);
                if( $lh < $pdf->getStringHeight($w[1], $item['value_display']) ) {
                    $lh = $pdf->getStringHeight($w[1], $item['value_display']);
                }
                if( $lh < $pdf->getStringHeight($w[2], $item['exhibit_name']) ) {
                    $lh = $pdf->getStringHeight($w[2], $item['exhibit_name']);
                }
                if( $lh < $pdf->getStringHeight($w[3], $item['total_amount_display']) ) {
                    $lh = $pdf->getStringHeight($w[3], $item['total_amount_display']);
                }
                if( $lh < $pdf->getStringHeight($w[4], $item['sell_date_display']) ) {
                    $lh = $pdf->getStringHeight($w[4], $item['sell_date_display']);
                }
                $pdf->MultiCell($w[0], $lh, $item['name'], 1, 'L', 0, 0);
                $pdf->MultiCell($w[1], $lh, $item['value_display'], 1, 'L', 0, 0);
                $pdf->MultiCell($w[2], $lh, $item['exhibit_name'], 1, 'L', 0, 0);
                $pdf->MultiCell($w[3], $lh, $item['total_amount_display'], 1, 'L', 0, 0);
                $pdf->MultiCell($w[4], $lh, $item['sell_date_display'], 1, 'L', 0, 1);
            }
        }

//        $pdf->MultiCell(180, 10, print_r($sponsor, true), 0, 'L');
    }


    if( count($args['sponsors']) == 1 ) {
        $filename = 'Sponsors - ' . $args['sponsors'][0]['title'];
    }

    return array('stat'=>'ok', 'filename'=>$filename, 'pdf'=>$pdf);
}
?>

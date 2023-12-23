<?php
//
// Description
// ===========
// This method will return all the information about an contact.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the contact is attached to.
// contact_id:          The ID of the contact to get the details for.
//
// Returns
// -------
//
function ciniki_sponsors_contactGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'contact_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Contact'),
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
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.contactGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Contact
    //
    if( $args['contact_id'] == 0 ) {
        $contact = array('id'=>0,
            'sponsor_id'=>'',
            'customer_id'=>'',
            'label'=>'',
            'sequence'=>'1',
        );
    }

    //
    // Get the details for an existing Contact
    //
    else {
        $strsql = "SELECT ciniki_sponsor_contacts.id, "
            . "ciniki_sponsor_contacts.sponsor_id, "
            . "ciniki_sponsor_contacts.customer_id, "
            . "ciniki_sponsor_contacts.label, "
            . "ciniki_sponsor_contacts.sequence "
            . "FROM ciniki_sponsor_contacts "
            . "WHERE ciniki_sponsor_contacts.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_sponsor_contacts.id = '" . ciniki_core_dbQuote($ciniki, $args['contact_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
            array('container'=>'contacts', 'fname'=>'id', 
                'fields'=>array('sponsor_id', 'customer_id', 'label', 'sequence'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.58', 'msg'=>'Contact not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['contacts'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sponsors.59', 'msg'=>'Unable to find Contact'));
        }
        $contact = $rc['contacts'][0];
    }

    return array('stat'=>'ok', 'contact'=>$contact);
}
?>

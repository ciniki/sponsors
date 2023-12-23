<?php
//
// Description
// -----------
// This method will return the list of Contacts for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Contact for.
//
// Returns
// -------
//
function ciniki_sponsors_contactList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'private', 'checkAccess');
    $rc = ciniki_sponsors_checkAccess($ciniki, $args['tnid'], 'ciniki.sponsors.contactList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of contacts
    //
    $strsql = "SELECT ciniki_sponsor_contacts.id, "
        . "ciniki_sponsor_contacts.sponsor_id, "
        . "ciniki_sponsor_contacts.customer_id, "
        . "ciniki_sponsor_contacts.label, "
        . "ciniki_sponsor_contacts.sequence "
        . "FROM ciniki_sponsor_contacts "
        . "WHERE ciniki_sponsor_contacts.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sponsors', array(
        array('container'=>'contacts', 'fname'=>'id', 
            'fields'=>array('id', 'sponsor_id', 'customer_id', 'label', 'sequence')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $contacts = isset($rc['contacts']) ? $rc['contacts'] : array();
    $contact_ids = array();
    foreach($contacts as $iid => $contact) {
        $contact_ids[] = $contact['id'];
    }

    return array('stat'=>'ok', 'contacts'=>$contacts, 'nplist'=>$contact_ids);
}
?>

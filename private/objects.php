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
function ciniki_sponsors_objects($ciniki) {
    
    $objects = array();
    $objects['sponsor'] = array(
        'name'=>'Sponsor',
        'sync'=>'yes',
        'table'=>'ciniki_sponsors',
        'fields'=>array(
            'title'=>array(),
            'permalink'=>array(),
            'level_id'=>array('ref'=>'ciniki.sponsors.level'),
            'sequence'=>array(),
            'webflags'=>array(),
            'url'=>array(),
            'primary_image_id'=>array('ref'=>'ciniki.images.image'),
            'excerpt'=>array(),
            'content'=>array(),
            'notes'=>array(),
            ),
        'history_table'=>'ciniki_sponsor_history',
        );
    $objects['level'] = array(
        'name'=>'Sponsor Level',
        'sync'=>'yes',
        'table'=>'ciniki_sponsor_levels',
        'fields'=>array(
            'name'=>array(),
            'permalink'=>array(),
            'sequence'=>array(),
            'size'=>array(),
            ),
        'history_table'=>'ciniki_sponsor_history',
        );
    $objects['objref'] = array(
        'name'=>'Object Reference',
        'sync'=>'yes',
        'table'=>'ciniki_sponsor_objrefs',
        'fields'=>array(
            'sponsor_id'=>array(),
            'object'=>array(),
            'object_id'=>array(),
            'sequence'=>array(),
            'webflags'=>array(),
            ),
        'history_table'=>'ciniki_sponsor_history',
        );
    $objects['objrefdetail'] = array(
        'name'=>'Object Reference Detail',
        'sync'=>'yes',
        'table'=>'ciniki_sponsor_objrefdetails',
        'fields'=>array(
            'object'=>array(),
            'object_id'=>array(),
            'title'=>array(),
            'content'=>array(),
            'size'=>array(),
            ),
        'history_table'=>'ciniki_sponsor_history',
        );
    $objects['package'] = array(
        'name' => 'Sponsor Package',
        'sync' => 'yes',
        'o_name' => 'package',
        'o_container' => 'packages',
        'table' => 'ciniki_sponsor_packages',
        'fields' => array(
            'name' => array('name'=>'Name'),
            'subname' => array('name'=>'Subname', 'default'=>''),
            'permalink' => array('name'=>'Permalink', 'default'=>''),
            'invoice_code' => array('name'=>'Code', 'default'=>''),
            'invoice_name' => array('name'=>'Invoice Name', 'default'=>''),
            'flags' => array('name'=>'Options', 'default'=>0),
            'object' => array('name'=>'Object', 'default'=>''),
            'object_id' => array('name'=>'Object ID', 'default'=>''),
            'category' => array('name'=>'Accounting Category', 'default'=>''),
            'sequence' => array('name'=>'Order', 'default'=>''),
            'amount' => array('name'=>'Amount', 'default'=>''),
            'primary_image_id' => array('name'=>'Image', 'default'=>'0', 'ref'=>'ciniki.images.image'),
            'synopsis' => array('name'=>'Synopsis', 'default'=>''),
            'description' => array('name'=>'Description', 'default'=>''),
            ),
        'history_table' => 'ciniki_sponsor_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>

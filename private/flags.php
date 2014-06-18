<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sponsors_flags($ciniki, $modules) {
	$flags = array(
		array('flag'=>array('bit'=>'1', 'name'=>'Levels')),
		);

	return array('stat'=>'ok', 'flags'=>$flags);
}
?>

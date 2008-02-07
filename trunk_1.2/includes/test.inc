<?php
/*
 * Created on Dec 13, 2007
 * 
 * SVN INFORMATION:::
 * ------------------
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$

 */
 

$invoiceData = array(
	'invoice_id'	=> '10015',
	'poc'			=> 'Johnny Jackson',
	'company'		=> 'Llama Soft, Inc.',
	'address1'		=> '1555 North Georgia Ave.',
	'address2'		=> 'Suite 1411',
	'phone'			=> '(701) 555-3389 ext. 1911',
	'fax'			=> '(701) 555-9193',
	'city'			=> 'St. Louis',
	'state'			=> 'Missouri',
	'zip'			=> '55454'
);

$invoiceItemData = array(
	91	=> array(
		'invoice_item_id'	=> 91,
		'invoice_id'		=> 10015,
		'description'		=> 'Contract Downpayment',
		'unit_price'		=> 3000,
		'quantity'			=> 1
	),
	93	=> array(
		'invoice_item_id'	=> 93,
		'invoice_id'		=> 10015,
		'description'		=> 'Remote Software Development',
		'unit_price'		=> 75,
		'quantity'			=> 40
	),
	94	=> array(
		'invoice_item_id'	=> 94,
		'invoice_id'		=> 10015,
		'description'		=> 'Remote Software Development (overtime hours)',
		'unit_price'		=> 112.5,
		'quantity'			=> 12
	)
);

	
$gf = new cs_globalFunctions;
$gf->debugPrintOpt = 1;

$gf->debug_print("Here is the main invoice data::: ". $gf->debug_print($invoiceData,0));

$blockRows = $page->rip_all_block_rows();

if(!count($blockRows)) {
	$gf->debug_print("Here's what I could find for block rows (should be more than just an empty array)::: ". $gf->debug_print($page->get_block_row_defs('content'),0));
	throw new exception("You forgot to give me valid block rows for parsing invoice items!");
}
else {
	$gf->debug_print("Here's the items that will be parsed into the rows::: ". $gf->debug_print($invoiceItemData,0));
	foreach($invoiceData as $index=>$value) {
		$page->add_template_var($index, $value);
	}
	
	//I have no idea how this will work when I don't know what the name of the block row is.
	$tmplRow = array_pop($blockRows);
	$allParsedRows = "";
	
	foreach($invoiceItemData as $itemId=>$subData) {
		$allParsedRows .= $page->gfObj->mini_parser($tmplRow, $subData, '{', '}');
	}
}

?>

<?php
if( !(isset($_POST['action']) && $_POST['action'] == 'save') )
	die("Invalid action");
if( !isset($_POST['rid']) )
	die("No configuration ID set. Cannot proceed.");
$_id = trim($_POST['rid']);
if( empty($_id) ) die("Empty configuration ID. Cannot proceed.");

$opts = get_option('ecapi_options');
$dbms = new couchdb(
	$opts['ecapi_config_db_url'], $opts['ecapi_config_db_name'], 
	$opts['ecapi_config_db_username'],$opts['ecapi_config_db_password']
);
$json = $dbms->getDoc($_id);
$is_change = empty($json['data']->error);
if( $is_change ) {
	print "Changed existing";
	$json = $json['data'];
	$_rev = $json->_rev;
} else {
	print "Created new";
	$json = new stdClass();
	$_rev = NULL;
}

// No rev -> we assume it's an attempt to create new
// if( !isset($_POST['_rev']) )
//    die("Document revision ID not set in request. Cannot update.");
	
if( $_rev !== $_POST['_rev'] )
	die("Revision number mismatch! Expected '<span style=\"color:#f00\">{$_POST['_rev']}</span>', but latest is '<span style=\"color:#f00\">$_rev</span>'");

$json->type = "provider-spec";
if( !isset($_POST ['debug']) ) unset($json->debug);
foreach( $_POST as $a=>$v)
	switch( $a ) {
		case 'cache':
			$json->{"mks:cache-lifetime"} = intval($v);
			break;
		case 'debug':
			$json->debug = TRUE;
			break;
		case 'dataset-uuid':
			if( empty($v) ) unset($json->{'catalogue-uuid'});
			else $json->{'catalogue-uuid'} = $v;
			break;
		case 'endpoint':
			if( empty($v) ) unset($json->{'http://rdfs.org/ns/void#sparqlEndpoint'});
			else $json->{'http://rdfs.org/ns/void#sparqlEndpoint'} = $v;
			break;
		case 'graph':
			if( empty($v) ) unset($json->{'mks:graph'});
			else $json->{'mks:graph'} = $v;
			break;
	}
$response = $dbms->saveDoc( $_id, $json );
if( $response['data']->ok == 1 ) {
	$redi = home_url() . '/wp-admin?page=ecapi-config' . ( $is_change ? '&action=view&id=' . urlencode($_id) : '' );
	wp_redirect( $redi ); exit;
} else {
	die("FAIL:<pre>". print_r( $response,TRUE ). "</pre>");
}
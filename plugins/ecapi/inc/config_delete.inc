<h1></h1>
<?php
if( !(isset($_POST['action']) && $_POST['action'] == 'delete') )
	die("Unexpected action");
	
$_id = $_POST['id'];
$opts = get_option('ecapi_options');
$dbms = new couchdb(
	$opts['ecapi_config_db_url'], $opts['ecapi_config_db_name'], 
	$opts['ecapi_config_db_username'], $opts['ecapi_config_db_password']
);

if( !isset($_POST['_rev']) )
	die("Document revision ID not set in request. Cannot delete.");

$response = $dbms->deleteDoc( $_id, $_POST['_rev'] );
if( $response['data']->ok == 1 ) {
	wp_redirect (  home_url() . '/wp-admin/admin.php?page=ecapi-config&action=list' );
} else {
	die("FAIL:<pre>". print_r( $response,TRUE ). "</pre>");
}

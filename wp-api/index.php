<?php
require '../wp-config.php';
require 'api-class.php';

$api = new Api( $wpdb, $table_prefix );

$key = "b652e0e4492bba8fc30cb5eeed6f88f2";

$error_string = "[]";

$type = ( isset( $_POST['type'] ) && !empty( $_POST['type'] ) ) ? @$_POST['type'] : NULL;
$key_post = ( isset( $_POST['key'] ) && !empty( $_POST['key'] ) ) ? @$_POST['key'] : NULL;

if( is_null( $key_post ) || $key_post != $key  ){
	echo $error_string;
	die();
}


switch($type){
	case "feed":
		echo $api->getFeed();
	break;
	default:
		echo $error_string;
}
?>
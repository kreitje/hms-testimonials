<?php

require_once '../../../wp-load.php';
require_once './admin.php';

$token = ( isset($_GET['token'] ) ) ? $_GET['token'] : '';
$key = ( isset($_GET['key'] ) ) ? $_GET['key'] : '';
$id = ( isset($_GET['id'] ) ) ? $_GET['id'] : '';

$blog_id = 1;
if (isset($_GET['blog_id']) && is_numeric($_GET['blog_id']))
	$blog_id = (int)$_GET['blog_id'];

$message = 'We could not find an unapproved testimonial you are looking for.';


$find_testimonial = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM `".$wpdb->prefix."hms_testimonials` 
		WHERE 
			`blog_id` = %d AND 
			`id` = %d AND 
			`autoapprove_token` = %s AND 
			`display` = 0
		LIMIT 1", $blog_id, $id, $token ), ARRAY_A );

if ( count($find_testimonial) > 0) {

	$find_key = md5($find_testimonial['name'] . '/' . $find_testimonial['created_at']);

	if ( $find_key == $key ) {

		$wpdb->update(
			$wpdb->prefix."hms_testimonials",
			array( 'display' => 1 ),
			array( 'id' => $find_testimonial['id'] )
		);

		$message = 'This testimonial has been approved.';
		
	} else {
		$message = 'The URL you entered is invalid.';
	}


}
$url = get_bloginfo('wpurl');
?>
<html>
	<head>
		<title>Auto Approve Testimonials</title>
		<meta http-equiv="refresh" content="10;<?php echo $url; ?>">
	</head>
	<body>
		<h1 align="center"><?php echo $message; ?></h1>
		<div align="center">You will be redirected back to your site momentarily.</div>
	</body>
</html>
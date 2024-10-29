<?php
if (!defined('ABSPATH'))
    exit;
// Your PHP logic here

// Send a JSON response
$response = array('message' => 'AJAX request to connect.php was successful!');
header('Content-Type: application/json');
echo wp_json_encode($response);

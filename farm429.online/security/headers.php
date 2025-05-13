<?php
header("X-Content-Type-Options: nosniff");
if (strpos($_SERVER['SCRIPT_NAME'], '/api/') !== false) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
} 
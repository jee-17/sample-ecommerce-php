<?php
// session_boot.php
// Put this at the top of every PHP script BEFORE any output.
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
// If you’re on HTTPS, uncomment the next line
// ini_set('session.cookie_secure', '1');
ini_set('session.cookie_samesite', 'Lax'); // works well for same-origin fetch

session_name('PHPSESSID');
session_start();

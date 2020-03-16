<?php
require_once 'SameSiteSessionStarter.php';

//start samesite none php session
SameSiteSessionStarter::session_start();

// set session variable as usual
$_SESSION['test'] = '12345';

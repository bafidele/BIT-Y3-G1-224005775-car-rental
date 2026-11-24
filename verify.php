<?php
require 'config/db.php';
$_SESSION['auth_notice'] = 'Email verification is not required. Customer accounts are free and ready to use after registration.';
header('Location: login.php');
exit;

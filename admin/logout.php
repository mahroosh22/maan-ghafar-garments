<?php

session_start();

/* Clear all session variables */
session_unset();

/* Destroy the current session */
session_destroy();

/* Redirect to admin login */
header("Location: login.php");
exit;

?>
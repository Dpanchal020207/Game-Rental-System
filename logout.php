<?php
require 'db.php';
session_unset();
session_destroy();
redirect('login.php');
?>

<?php
session_start();
session_destroy();
header('Location: /new_hotelly_cool/index.php');
exit;
?>

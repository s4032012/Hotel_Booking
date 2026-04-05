<?php
session_start();
session_unset();
session_destroy();
header("Location: index.php");
exit();
?>
<?php
session_start();
session_unset();
session_destroy();
// Chuyển ngay về trang chủ
header("Location: index.php");
exit();
?>
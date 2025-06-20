<?php
require_once 'phpqrcode/qrlib.php';

header('Content-Type: image/png');
QRcode::png('Hello World', false, QR_ECLEVEL_L, 6, 2);
?>
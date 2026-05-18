<?php
$img192 = imagecreatetruecolor(192, 192);
$bg = imagecolorallocate($img192, 5, 12, 24);
imagefill($img192, 0, 0, $bg);
$text = imagecolorallocate($img192, 255, 255, 255);
imagestring($img192, 5, 60, 88, 'PIC', $text);
imagepng($img192, __DIR__ . '/icon-192.png');
imagedestroy($img192);

$img512 = imagecreatetruecolor(512, 512);
$bg = imagecolorallocate($img512, 5, 12, 24);
imagefill($img512, 0, 0, $bg);
$text = imagecolorallocate($img512, 255, 255, 255);
imagestring($img512, 5, 220, 248, 'PIC', $text);
imagepng($img512, __DIR__ . '/icon-512.png');
imagedestroy($img512);

echo "Icons created successfully\n";

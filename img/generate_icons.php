<?php
$source = __DIR__ . '/pic.png';

function resizeIcon($srcPath, $size, $destPath) {
    $src = imagecreatefrompng($srcPath);
    if (!$src) {
        echo "Error: No se pudo cargar $srcPath\n";
        return false;
    }
    
    $srcW = imagesx($src);
    $srcH = imagesy($src);
    
    $dst = imagecreatetruecolor($size, $size);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $size, $size, $srcW, $srcH);
    imagepng($dst, $destPath);
    
    echo "Icono {$size}x{$size} creado: $destPath\n";
    return true;
}

resizeIcon($source, 192, __DIR__ . '/icon-192.png');
resizeIcon($source, 512, __DIR__ . '/icon-512.png');

echo "Iconos generados correctamente desde pic.png\n";

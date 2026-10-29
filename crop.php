<?php
$img_path = 'uploads/qr_code.jpg';
if (!file_exists($img_path)) {
    die("File not found");
}
$img = imagecreatefromjpeg($img_path);
$width = imagesx($img);
$height = imagesy($img);
echo "Original: {$width}x{$height}\n";

// The image uploaded seems to have text at the top and bottom.
// We want the square QR code in the middle.
// Usually, it's roughly a square in the center. 
// Let's crop a square from the center.
// Since it's a GPay QR, the QR code is a rounded square, but let's just grab the center square width x width.
$crop_size = $width * 0.8; // Let's guess the QR code is about 80% of the width
$x = ($width - $crop_size) / 2;
// The QR code is usually slightly shifted up from exact center due to bottom text being longer sometimes.
// But exact center square crop might just work. Let's try 70% of the height centered.
$crop_size = min($width, $height) * 0.75;
$x = ($width - $crop_size) / 2;
$y = ($height - $crop_size) / 2;

$cropped = imagecrop($img, ['x' => $x, 'y' => $y, 'width' => $crop_size, 'height' => $crop_size]);

if ($cropped !== FALSE) {
    imagejpeg($cropped, 'uploads/qr_code_cropped.jpg', 90);
    imagedestroy($cropped);
    echo "Cropped successfully.\n";
} else {
    echo "Crop failed.\n";
}
imagedestroy($img);
?>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Simple LSB-based steganography (for demonstration purposes)

function encodeSteganography($imagePath, $outputPath, $hiddenText) {
    $image = imagecreatefrompng($imagePath);
    $binaryText = '';

    // Add a delimiter to know where it ends
    $hiddenText .= '|END|';

    // Convert text to binary
    for ($i = 0; $i < strlen($hiddenText); $i++) {
        $binaryText .= str_pad(decbin(ord($hiddenText[$i])), 8, '0', STR_PAD_LEFT);
    }

    $width = imagesx($image);
    $height = imagesy($image);
    $charIndex = 0;

    for ($y = 0; $y < $height && $charIndex < strlen($binaryText); $y++) {
        for ($x = 0; $x < $width && $charIndex < strlen($binaryText); $x++) {
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            // Change LSB of blue channel
            $b = ($b & 0xFE) | $binaryText[$charIndex];
            $charIndex++;

            $newColor = imagecolorallocate($image, $r, $g, $b);
            imagesetpixel($image, $x, $y, $newColor);
        }
    }

    imagepng($image, $outputPath);
    imagedestroy($image);
    return true;
}

function decodeSteganography($imagePath) {
    $image = imagecreatefrompng($imagePath);
    $width = imagesx($image);
    $height = imagesy($image);
    $binaryText = '';
    $decodedText = '';

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $rgb = imagecolorat($image, $x, $y);
            $b = $rgb & 0xFF;
            $binaryText .= ($b & 1);

            if (strlen($binaryText) % 8 == 0) {
                $char = chr(bindec(substr($binaryText, -8)));
                $decodedText .= $char;
                if (strpos($decodedText, '|END|') !== false) {
                    $decodedText = str_replace('|END|', '', $decodedText);
                    return $decodedText;
                }
            }
        }
    }

    imagedestroy($image);
    return $decodedText;
}
?>

<?php
/**
 * Script de diagnostic pour l'extension GD
 * Exécuter: php test_gd.php
 */

echo "========================================\n";
echo "  DIAGNOSTIC DE L'EXTENSION GD\n";
echo "========================================\n\n";

// Test 1: Vérifier si GD est chargé
echo "1. Extension GD chargée: ";
if (extension_loaded('gd')) {
    echo "✅ OUI\n";
} else {
    echo "❌ NON - GD n'est pas activé!\n";
    echo "   → Activez GD dans php.ini et redémarrez Apache\n";
    exit(1);
}

// Test 2: Informations sur GD
echo "\n2. Informations GD:\n";
$gdInfo = gd_info();
foreach ($gdInfo as $key => $value) {
    $status = $value ? '✅' : '❌';
    echo "   $status $key: " . ($value === true ? 'Oui' : ($value === false ? 'Non' : $value)) . "\n";
}

// Test 3: Formats d'image supportés
echo "\n3. Formats d'image supportés:\n";
echo "   PNG: " . (function_exists('imagepng') ? '✅ Oui' : '❌ Non') . "\n";
echo "   JPEG: " . (function_exists('imagejpeg') ? '✅ Oui' : '❌ Non') . "\n";
echo "   GIF: " . (function_exists('imagegif') ? '✅ Oui' : '❌ Non') . "\n";
echo "   WebP: " . (function_exists('imagewebp') ? '✅ Oui' : '❌ Non') . "\n";

// Test 4: Créer une image simple
echo "\n4. Test de création d'image PNG:\n";
try {
    $image = imagecreatetruecolor(100, 100);
    if ($image === false) {
        throw new Exception("Impossible de créer l'image");
    }
    
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $white);
    
    ob_start();
    imagepng($image);
    $imageData = ob_get_clean();
    imagedestroy($image);
    
    echo "   ✅ Image PNG créée avec succès (" . strlen($imageData) . " bytes)\n";
} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

// Test 5: Tester endroid/qr-code si disponible
echo "\n5. Test de la bibliothèque endroid/qr-code:\n";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    try {
        $builder = \Endroid\QrCode\Builder\Builder::create()
            ->writer(new \Endroid\QrCode\Writer\PngWriter())
            ->data('Test QR Code')
            ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
            ->errorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->build();
        
        $qrData = $builder->getString();
        echo "   ✅ QR Code généré avec succès (" . strlen($qrData) . " bytes)\n";
        
        // Sauvegarder le QR code pour test
        $testFile = __DIR__ . '/test_qrcode.png';
        file_put_contents($testFile, $qrData);
        echo "   ✅ QR Code sauvegardé: $testFile\n";
        
    } catch (Exception $e) {
        echo "   ❌ Erreur: " . $e->getMessage() . "\n";
        echo "   Stack trace:\n";
        echo "   " . str_replace("\n", "\n   ", $e->getTraceAsString()) . "\n";
    }
} else {
    echo "   ⚠️  Composer autoload non trouvé\n";
    echo "   → Exécutez: composer install\n";
}

// Test 6: Configuration PHP
echo "\n6. Configuration PHP:\n";
echo "   Version PHP: " . PHP_VERSION . "\n";
echo "   Fichier php.ini: " . php_ini_loaded_file() . "\n";
echo "   Extension dir: " . ini_get('extension_dir') . "\n";

// Test 7: Vérifier le fichier DLL
echo "\n7. Vérification du fichier php_gd.dll:\n";
$extDir = ini_get('extension_dir');
$gdDll = $extDir . DIRECTORY_SEPARATOR . 'php_gd.dll';
if (file_exists($gdDll)) {
    echo "   ✅ Fichier trouvé: $gdDll\n";
    echo "   Taille: " . filesize($gdDll) . " bytes\n";
} else {
    echo "   ❌ Fichier non trouvé: $gdDll\n";
}

echo "\n========================================\n";
echo "  RÉSUMÉ\n";
echo "========================================\n";

if (extension_loaded('gd') && function_exists('imagepng')) {
    echo "✅ GD est correctement configuré!\n";
    echo "   Vous pouvez générer des QR codes.\n";
} else {
    echo "❌ GD n'est pas correctement configuré\n";
    echo "\n📋 ACTIONS À FAIRE:\n";
    echo "1. Ouvrir: " . php_ini_loaded_file() . "\n";
    echo "2. Chercher: ;extension=gd\n";
    echo "3. Modifier en: extension=gd\n";
    echo "4. Sauvegarder le fichier\n";
    echo "5. Redémarrer Apache\n";
    echo "6. Relancer ce script: php test_gd.php\n";
}

echo "\n";

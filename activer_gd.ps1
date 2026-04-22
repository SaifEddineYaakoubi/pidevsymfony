# Script PowerShell pour activer l'extension GD automatiquement
# Exécuter en tant qu'administrateur

$phpIniPath = "C:\xampp\php\php.ini"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Activation de l'extension GD pour PHP" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Vérifier si le fichier existe
if (-Not (Test-Path $phpIniPath)) {
    Write-Host "❌ ERREUR: Le fichier php.ini n'existe pas à: $phpIniPath" -ForegroundColor Red
    Write-Host "Vérifiez que XAMPP est installé correctement." -ForegroundColor Yellow
    pause
    exit 1
}

Write-Host "✓ Fichier php.ini trouvé: $phpIniPath" -ForegroundColor Green

# Faire une sauvegarde
$backupPath = "$phpIniPath.backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
Copy-Item $phpIniPath $backupPath
Write-Host "✓ Sauvegarde créée: $backupPath" -ForegroundColor Green

# Lire le contenu
$content = Get-Content $phpIniPath

# Remplacer ;extension=gd par extension=gd
$modified = $false
$newContent = $content | ForEach-Object {
    if ($_ -match '^\s*;extension=gd\s*$') {
        Write-Host "✓ Ligne trouvée: $_" -ForegroundColor Yellow
        $modified = $true
        "extension=gd"
    } else {
        $_
    }
}

if ($modified) {
    # Sauvegarder les modifications
    $newContent | Set-Content $phpIniPath -Encoding UTF8
    Write-Host "✓ Extension GD activée avec succès!" -ForegroundColor Green
    Write-Host ""
    Write-Host "⚠️  IMPORTANT: Vous devez maintenant:" -ForegroundColor Yellow
    Write-Host "   1. Ouvrir le Panneau de Contrôle XAMPP" -ForegroundColor White
    Write-Host "   2. Cliquer sur 'Stop' pour Apache" -ForegroundColor White
    Write-Host "   3. Cliquer sur 'Start' pour Apache" -ForegroundColor White
    Write-Host ""
} else {
    Write-Host "⚠️  La ligne ';extension=gd' n'a pas été trouvée." -ForegroundColor Yellow
    Write-Host "   Peut-être que GD est déjà activé?" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Vérification de l'état actuel de GD..." -ForegroundColor Cyan
$gdStatus = php -m | Select-String "gd"

if ($gdStatus) {
    Write-Host "✅ GD est ACTIVÉ!" -ForegroundColor Green
} else {
    Write-Host "❌ GD n'est PAS activé" -ForegroundColor Red
    Write-Host "   → Redémarrez Apache dans XAMPP" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Appuyez sur une touche pour fermer..." -ForegroundColor Gray
pause

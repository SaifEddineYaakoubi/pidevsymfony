# Script PowerShell pour activer l'extension Sodium automatiquement
# Exécutez ce script en tant qu'administrateur

Write-Host "=== Activation de l'extension Sodium pour PHP ===" -ForegroundColor Cyan
Write-Host ""

# Trouver le chemin du php.ini
$phpIniPath = php --ini | Select-String "Loaded Configuration File" | ForEach-Object { $_.ToString().Split(":")[1].Trim() }

if (-not $phpIniPath) {
    Write-Host "Erreur : Impossible de trouver le fichier php.ini" -ForegroundColor Red
    exit 1
}

Write-Host "Fichier php.ini trouvé : $phpIniPath" -ForegroundColor Green

# Vérifier si le fichier existe
if (-not (Test-Path $phpIniPath)) {
    Write-Host "Erreur : Le fichier php.ini n'existe pas à cet emplacement" -ForegroundColor Red
    exit 1
}

# Lire le contenu du fichier
$content = Get-Content $phpIniPath

# Vérifier si sodium est déjà activé
$sodiumEnabled = $content | Select-String -Pattern "^extension=sodium" -Quiet

if ($sodiumEnabled) {
    Write-Host "L'extension Sodium est déjà activée !" -ForegroundColor Green
    Write-Host ""
    Write-Host "Vérification..." -ForegroundColor Yellow
    php -r "echo extension_loaded('sodium') ? 'Sodium fonctionne correctement!' : 'Sodium ne fonctionne pas';"
    Write-Host ""
    exit 0
}

# Chercher la ligne commentée
$sodiumCommented = $content | Select-String -Pattern "^;extension=sodium" -Quiet

if ($sodiumCommented) {
    Write-Host "Ligne commentée trouvée. Décommentation en cours..." -ForegroundColor Yellow
    
    # Créer une sauvegarde
    $backupPath = "$phpIniPath.backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
    Copy-Item $phpIniPath $backupPath
    Write-Host "Sauvegarde créée : $backupPath" -ForegroundColor Green
    
    # Décommenter la ligne
    $newContent = $content -replace "^;extension=sodium", "extension=sodium"
    Set-Content -Path $phpIniPath -Value $newContent
    
    Write-Host "Extension Sodium activée avec succès !" -ForegroundColor Green
} else {
    Write-Host "Ligne 'extension=sodium' non trouvée. Ajout en cours..." -ForegroundColor Yellow
    
    # Créer une sauvegarde
    $backupPath = "$phpIniPath.backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
    Copy-Item $phpIniPath $backupPath
    Write-Host "Sauvegarde créée : $backupPath" -ForegroundColor Green
    
    # Trouver la section des extensions et ajouter sodium
    $extensionLineIndex = -1
    for ($i = 0; $i -lt $content.Count; $i++) {
        if ($content[$i] -match "^extension=") {
            $extensionLineIndex = $i
            break
        }
    }
    
    if ($extensionLineIndex -ge 0) {
        # Insérer après la première extension trouvée
        $newContent = $content[0..$extensionLineIndex] + "extension=sodium" + $content[($extensionLineIndex + 1)..($content.Count - 1)]
        Set-Content -Path $phpIniPath -Value $newContent
        Write-Host "Extension Sodium ajoutée avec succès !" -ForegroundColor Green
    } else {
        # Ajouter à la fin du fichier
        Add-Content -Path $phpIniPath -Value "`nextension=sodium"
        Write-Host "Extension Sodium ajoutée à la fin du fichier !" -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "=== Vérification ===" -ForegroundColor Cyan
Write-Host "Veuillez redémarrer Apache (si vous utilisez XAMPP) puis exécutez :" -ForegroundColor Yellow
Write-Host "  php -m | findstr sodium" -ForegroundColor White
Write-Host ""
Write-Host "Ou testez directement :" -ForegroundColor Yellow
Write-Host "  php -r `"echo extension_loaded('sodium') ? 'OK' : 'KO';`"" -ForegroundColor White
Write-Host ""
Write-Host "Appuyez sur une touche pour tester maintenant..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

Write-Host ""
Write-Host "Test en cours..." -ForegroundColor Yellow
$result = php -r "echo extension_loaded('sodium') ? 'OK' : 'KO';"

if ($result -eq "OK") {
    Write-Host "✓ Sodium fonctionne correctement !" -ForegroundColor Green
} else {
    Write-Host "✗ Sodium ne fonctionne pas encore. Redémarrez Apache et réessayez." -ForegroundColor Red
}

Write-Host ""
Write-Host "Terminé !" -ForegroundColor Cyan

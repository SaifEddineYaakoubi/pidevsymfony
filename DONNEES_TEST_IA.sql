-- Script SQL pour ajouter des données de test pour l'analyse IA
-- Exécutez ce script dans votre base de données pour tester l'outil

-- Mettre à jour les utilisateurs existants avec des données aléatoires
UPDATE utilisateur SET 
    date_naissance = DATE_SUB(CURDATE(), INTERVAL FLOOR(20 + RAND() * 40) YEAR),
    sexe = CASE 
        WHEN RAND() < 0.5 THEN 'homme'
        ELSE 'femme'
    END
WHERE date_naissance IS NULL;

-- Exemples de mise à jour manuelle (optionnel)
-- UPDATE utilisateur SET date_naissance = '1990-05-15', sexe = 'homme' WHERE id_user = 1;
-- UPDATE utilisateur SET date_naissance = '1985-08-22', sexe = 'femme' WHERE id_user = 2;
-- UPDATE utilisateur SET date_naissance = '1995-03-10', sexe = 'homme' WHERE id_user = 3;

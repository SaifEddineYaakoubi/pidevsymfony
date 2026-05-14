# Tableau de Performance – Avant / Après

## Table 5 – Tableau de performance Avant/Après optimisation

| Indicateur de performance | Avant | Après | Preuves |
|---|---|---|---|
| **Doctrine Doctor – Total Issues** | **60** | **0** | RAPPORT_CORRECTIONS_DOCTRINE.md |
| **Problèmes Critiques 🔴** | **6** | **0** | RAPPORT_CORRECTIONS_DOCTRINE.md |
| **Warnings 🟠** | **39** | **0** | RAPPORT_CORRECTIONS_DOCTRINE.md |
| **Info 🔵** | **15** | **0** | RAPPORT_CORRECTIONS_DOCTRINE.md |
| **Performance Issues** | **5** | **0** | TABLEAU_PERFORMANCE_ISSUES.md |
| **N+1 Query** | **5 requêtes** | **0** | TABLEAU_PERFORMANCE_ISSUES.md |
| **Slow Query – table `message`** | **Full table scan** | **< 100 ms** | TABLEAU_PERFORMANCE_ISSUES.md |
| **Peak memory – Profiler (tests)** | **0 MB** (aucun test) | **78.00 MB** | `php bin/phpunit tests/` |
| **Mémoire – Tests entités seuls** | **0 MB** | **24.00 MB** | `php bin/phpunit tests/CultureTest.php` |
| **Mémoire – Tests services seuls** | **0 MB** | **32.00 MB** | `php bin/phpunit tests/Service/` |
| **Temps total – Suite complète** | **0 s** (aucun test) | **8.74 s** | `php bin/phpunit tests/` |
| **Temps – Tests services** | **0 s** | **4.17 s** | `php bin/phpunit tests/Service/` |
| **Temps – Tests entités** | **0 s** | **0.31 s** | `php bin/phpunit tests/CultureTest.php` |
| **Tests exécutés** | **0** | **176 / 176 (100%)** | `php bin/phpunit tests/` |
| **Assertions** | **0** | **281 / 281 (100%)** | `php bin/phpunit tests/` |
| **Erreurs** | **—** | **0** | `php bin/phpunit tests/` |
| **Services métier créés** | **0** | **6 services / 52 méthodes** | `src/Service/` |
| **Entités corrigées** | **0** | **49 corrections / 14 entités** | RAPPORT_CORRECTIONS_DOCTRINE.md |

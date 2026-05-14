# Tableau – Bilan Global des Optimisations (Doctrine Doctor + Performance)

| Indicateur | Avant | Après |
|---|---|---|
| **Total Issues détectés** | **60** | **0** |
| **Problèmes Critiques 🔴** | **6** | **0** |
| **Warnings 🟠** | **39** | **0** |
| **Info 🔵** | **15** | **0** |
| **Performance Issues** | **5** | **0** |
| **N+1 Query** | **5 requêtes** (`find()` dans boucle — `MessageController`) | **0** |
| **Slow Query** | **Full table scan** sur table `message` (pas d'index) | **< 100 ms** (3 index ajoutés) |
| **Profiler Overhead** | **Élevé** (5 requêtes répétées — `RendementRepository`) | **Réduit** (`useResultCache()` ajouté) |

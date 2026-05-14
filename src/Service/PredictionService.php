<?php

namespace App\Service;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Psr\Log\LoggerInterface;

class PredictionService
{
    private LoggerInterface $logger;
    private string $projectDir;
    private string $dbHost;
    private string $dbName;
    private string $dbUser;
    private string $dbPassword;

    public function __construct(
        LoggerInterface $logger,
        string $projectDir,
        string $databaseUrl
    ) {
        $this->logger = $logger;
        $this->projectDir = $projectDir;
        
        // Parser l'URL de la base de données
        $this->parseDatabaseUrl($databaseUrl);
    }

    /**
     * Parse l'URL de la base de données pour extraire les informations de connexion
     */
    private function parseDatabaseUrl(string $databaseUrl): void
    {
        // Format: mysql://user:password@host:port/dbname
        $pattern = '/mysql:\/\/([^:]+):([^@]*)@([^:]+):(\d+)\/([^?]+)/';
        
        if (preg_match($pattern, $databaseUrl, $matches)) {
            $this->dbUser = $matches[1];
            $this->dbPassword = $matches[2];
            $this->dbHost = $matches[3];
            $this->dbName = $matches[5];
        } else {
            // Valeurs par défaut si le parsing échoue
            $this->dbUser = 'root';
            $this->dbPassword = '';
            $this->dbHost = '127.0.0.1';
            $this->dbName = 'smartfarm';
        }
    }

    /**
     * Prédit le chiffre d'affaires du mois prochain en utilisant l'IA
     * 
     * @return array ['success' => bool, 'prediction' => float|null, 'statistics' => array|null, 'error' => string|null]
     */
    /** @return array<string, mixed> */
    public function predictNextMonthSales(): array
    {
        // Si Python n'est pas disponible ou que le script échoue → fallback PHP direct
        $scriptPath = $this->projectDir . '/scripts/predict_sales.py';
        $pythonPath = null;

        if (file_exists($scriptPath)) {
            $pythonPath = $this->detectPythonPath();
        }

        // Tenter Python si disponible
        if ($pythonPath) {
            try {
                $cmd = $pythonPath === 'py -3'
                    ? ['py', '-3', $scriptPath, $this->dbHost, $this->dbName, $this->dbUser, $this->dbPassword]
                    : [$pythonPath, $scriptPath, $this->dbHost, $this->dbName, $this->dbUser, $this->dbPassword];

                $process = new Process($cmd);
                $process->setTimeout(30);
                $process->run();

                if ($process->isSuccessful()) {
                    $result = json_decode($process->getOutput(), true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($result['success'])) {
                        return $result;
                    }
                }

                // Python a échoué (dépendances manquantes, etc.) → fallback PHP
                $this->logger->warning('Script Python échoué, bascule sur fallback PHP', [
                    'exit_code' => $process->getExitCode(),
                    'error'     => $process->getErrorOutput(),
                ]);

            } catch (\Exception $e) {
                $this->logger->warning('Exception Python, bascule sur fallback PHP', [
                    'exception' => $e->getMessage()
                ]);
            }
        }

        // Fallback PHP (régression linéaire native)
        return $this->predictWithPhpFallback();
    }

    /**
     * Détecte le chemin de l'exécutable Python sur le système
     */
    private function detectPythonPath(): ?string
    {
        // Liste des chemins possibles pour Python sur Windows
        $possiblePaths = [
            'python',
            'python3',
            'py',
            'C:\\Python312\\python.exe',
            'C:\\Python311\\python.exe',
            'C:\\Python310\\python.exe',
            'C:\\Python39\\python.exe',
            'C:\\Python38\\python.exe',
            'C:\\Python37\\python.exe',
            'C:\\Users\\' . getenv('USERNAME') . '\\AppData\\Local\\Programs\\Python\\Python312\\python.exe',
            'C:\\Users\\' . getenv('USERNAME') . '\\AppData\\Local\\Programs\\Python\\Python311\\python.exe',
            'C:\\Users\\' . getenv('USERNAME') . '\\AppData\\Local\\Programs\\Python\\Python310\\python.exe',
            'C:\\Users\\' . getenv('USERNAME') . '\\AppData\\Local\\Programs\\Python\\Python39\\python.exe',
            'C:\\Users\\' . getenv('USERNAME') . '\\AppData\\Local\\Programs\\Python\\Python38\\python.exe',
            'C:\\Program Files\\Python312\\python.exe',
            'C:\\Program Files\\Python311\\python.exe',
            'C:\\Program Files\\Python310\\python.exe',
            'C:\\Program Files\\Python39\\python.exe',
            'C:\\Program Files (x86)\\Python312\\python.exe',
            'C:\\Program Files (x86)\\Python311\\python.exe',
            'C:\\Program Files (x86)\\Python310\\python.exe',
            '/usr/bin/python3',
            '/usr/bin/python',
            '/usr/local/bin/python3',
            '/usr/local/bin/python',
        ];

        foreach ($possiblePaths as $path) {
            try {
                $process = new Process([$path, '--version']);
                $process->setTimeout(3);
                $process->run();

                if ($process->isSuccessful()) {
                    $this->logger->info('Python trouvé', [
                        'path' => $path,
                        'version' => trim($process->getOutput())
                    ]);
                    return $path;
                }
            } catch (\Exception $e) {
                // Continuer avec le prochain chemin
                continue;
            }
        }

        // Essayer de trouver Python via 'where' sur Windows
        try {
            $process = new Process(['where', 'python']);
            $process->setTimeout(3);
            $process->run();
            
            if ($process->isSuccessful()) {
                $output = trim($process->getOutput());
                $paths = explode("\n", $output);
                if (!empty($paths[0])) {
                    $pythonPath = trim($paths[0]);
                    $this->logger->info('Python trouvé via where', ['path' => $pythonPath]);
                    return $pythonPath;
                }
            }
        } catch (\Exception $e) {
            // Ignorer
        }

        // Essayer 'py -3' (Python Launcher sur Windows)
        try {
            $process = new Process(['py', '-3', '--version']);
            $process->setTimeout(3);
            $process->run();
            
            if ($process->isSuccessful()) {
                $this->logger->info('Python trouvé via py launcher', [
                    'version' => trim($process->getOutput())
                ]);
                return 'py -3';
            }
        } catch (\Exception $e) {
            // Ignorer
        }

        return null;
    }

    /**
     * Fallback PHP: régression linéaire simple sans Python
     * Utilisé quand Python n'est pas disponible
     */
    /** @return array<string, mixed> */
    private function predictWithPhpFallback(): array
    {
        try {
            // Connexion PDO directe
            $dsn = "mysql:host={$this->dbHost};dbname={$this->dbName};charset=utf8mb4";
            $pdo = new \PDO($dsn, $this->dbUser, $this->dbPassword, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 5,
            ]);

            // Récupérer les ventes groupées par mois
            $stmt = $pdo->query("
                SELECT 
                    DATE_FORMAT(date_vente, '%Y-%m') as mois,
                    SUM(montant_total) as total_ventes,
                    COUNT(*) as nombre_ventes
                FROM vente
                WHERE date_vente IS NOT NULL
                GROUP BY DATE_FORMAT(date_vente, '%Y-%m')
                ORDER BY mois ASC
            ");

            if ($stmt === false) {
                throw new \RuntimeException('Échec de la requête SQL');
            }

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (count($rows) < 2) {
                // Avec un seul mois, on retourne une estimation basée sur la moyenne
                if (count($rows) === 1) {
                    $seulMois = (float)$rows[0]['total_ventes'];
                    $lastMonth = $rows[0]['mois'];
                    $lastDate = new \DateTime($lastMonth . '-01');
                    $lastDate->modify('+1 month');
                    $predictedMonth = $lastDate->format('Y-m');

                    return [
                        'success'         => true,
                        'prediction'      => round($seulMois, 2),
                        'predicted_month' => $predictedMonth,
                        'statistics'      => [
                            'moyenne_mensuelle'      => round($seulMois, 2),
                            'dernier_mois'           => $seulMois,
                            'mois_precedent'         => null,
                            'tendance'               => 'stable',
                            'variation_pourcent'     => 0,
                            'nombre_mois_historique' => 1,
                            'total_historique'       => round($seulMois, 2),
                        ],
                        'engine' => 'php_fallback_single',
                        'error'  => null,
                    ];
                }

                return [
                    'success'    => false,
                    'prediction' => null,
                    'statistics' => null,
                    'error'      => 'Pas assez de données historiques (minimum 2 mois requis)',
                    'engine'     => 'php_fallback'
                ];
            }

            // Régression linéaire simple en PHP
            $n = count($rows);
            $x = range(0, $n - 1);
            $y = array_map(fn($r) => (float)$r['total_ventes'], $rows);

            // Calcul de la moyenne
            $meanX = array_sum($x) / $n;
            $meanY = array_sum($y) / $n;

            // Calcul de la pente (slope) et intercept
            $numerator = 0;
            $denominator = 0;
            for ($i = 0; $i < $n; $i++) {
                $numerator   += ($x[$i] - $meanX) * ($y[$i] - $meanY);
                $denominator += ($x[$i] - $meanX) ** 2;
            }

            $slope     = $denominator != 0 ? $numerator / $denominator : 0;
            $intercept = $meanY - $slope * $meanX;

            // Prédiction pour le mois suivant
            $prediction = max(0, $slope * $n + $intercept);

            // Calculer le mois prédit
            $lastMonth   = $rows[$n - 1]['mois'];
            $lastDate    = new \DateTime($lastMonth . '-01');
            $lastDate->modify('+1 month');
            $predictedMonth = $lastDate->format('Y-m');

            // Statistiques
            $dernierMois    = (float)$rows[$n - 1]['total_ventes'];
            $tendance       = $prediction > $dernierMois ? 'hausse' : 'baisse';
            $variationPct   = $dernierMois > 0
                ? round((($prediction - $dernierMois) / $dernierMois) * 100, 2)
                : 0;

            return [
                'success'         => true,
                'prediction'      => round($prediction, 2),
                'predicted_month' => $predictedMonth,
                'statistics'      => [
                    'moyenne_mensuelle'       => round(array_sum($y) / $n, 2),
                    'dernier_mois'            => $dernierMois,
                    'mois_precedent'          => $n >= 2 ? (float)$rows[$n - 2]['total_ventes'] : null,
                    'tendance'                => $tendance,
                    'variation_pourcent'      => $variationPct,
                    'nombre_mois_historique'  => $n,
                    'total_historique'        => round(array_sum($y), 2),
                ],
                'engine' => 'php_fallback',
                'error'  => null,
            ];

        } catch (\Exception $e) {
            $this->logger->error('Erreur dans le fallback PHP', [
                'exception' => $e->getMessage()
            ]);

            return [
                'success'    => false,
                'prediction' => null,
                'statistics' => null,
                'error'      => 'Erreur lors de la prédiction: ' . $e->getMessage(),
                'engine'     => 'php_fallback'
            ];
        }
    }

    /**
     * Vérifie si les dépendances Python sont installées
     */
    /** @return array<string, mixed> */
    public function checkPythonDependencies(): array
    {
        $pythonPath = $this->detectPythonPath();
        
        if (!$pythonPath) {
            return [
                'success' => false,
                'python_installed' => false,
                'dependencies' => [],
                'error' => 'Python non installé'
            ];
        }

        $requiredPackages = ['mysql-connector-python', 'pandas', 'numpy', 'scikit-learn'];
        $installedPackages = [];
        $missingPackages = [];

        foreach ($requiredPackages as $package) {
            $process = new Process([$pythonPath, '-m', 'pip', 'show', $package]);
            $process->run();

            if ($process->isSuccessful()) {
                $installedPackages[] = $package;
            } else {
                $missingPackages[] = $package;
            }
        }

        return [
            'success' => empty($missingPackages),
            'python_installed' => true,
            'python_path' => $pythonPath,
            'installed_packages' => $installedPackages,
            'missing_packages' => $missingPackages,
            'error' => empty($missingPackages) ? null : 'Dépendances Python manquantes: ' . implode(', ', $missingPackages)
        ];
    }
}

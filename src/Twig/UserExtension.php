<?php
// src/Twig/UserExtension.php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UserExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getUserColor', [$this, 'getUserColor']),
        ];
    }

    public function getUserColor(int $id): string
    {
        $colors = ['#667eea', '#f093fb', '#4facfe', '#43e97b', '#fa709a', '#fee140', '#30cfd0', '#a8edea'];
        return $colors[$id % count($colors)];
    }
}
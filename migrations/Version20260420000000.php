<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Mark as completed since columns already exist
 */
final class Version20260420000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Columns quantite and id_produit already exist in vente table - no migration needed';
    }

    public function up(Schema $schema): void
    {
        // Columns already exist, no action needed
    }

    public function down(Schema $schema): void
    {
        // No action needed
    }
}


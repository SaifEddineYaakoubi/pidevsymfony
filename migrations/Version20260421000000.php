<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add missing foreign key constraint for id_produit in vente table
 */
final class Version20260421000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing foreign key constraint for id_produit in vente table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4CF7384557 FOREIGN KEY (id_produit) REFERENCES produit (id_produit) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4CF7384557');
    }
}


<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add latitude/longitude columns to parcelle.';
    }

    public function up(Schema $schema): void
    {
        // This migration is auto-safe for MySQL.
        $this->addSql('ALTER TABLE parcelle ADD latitude DOUBLE PRECISION DEFAULT NULL, ADD longitude DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE parcelle DROP latitude, DROP longitude');
    }
}


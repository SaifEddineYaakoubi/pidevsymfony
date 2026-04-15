<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fix parcelle primary key identity generation for existing databases.
 *
 * Some legacy schemas have parcelle.id_parcelle without AUTO_INCREMENT, which
 * causes Doctrine to fail with "No identity value was generated..." when
 * inserting new Parcelle rows.
 */
final class Version20260415150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure parcelle.id_parcelle is an AUTO_INCREMENT primary key';
    }

    public function up(Schema $schema): void
    {
        // MariaDB/MySQL: make sure id_parcelle is an auto-increment integer.
        // This is safe to run even if it is already AUTO_INCREMENT.
        $this->addSql('ALTER TABLE parcelle MODIFY id_parcelle INT NOT NULL AUTO_INCREMENT');

        // Add PK only if none exists
        $this->addSql(<<<'SQL'
SET @pk := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_TYPE = 'PRIMARY KEY'
    AND TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'parcelle'
);
SET @sql := IF(@pk = 0, 'ALTER TABLE parcelle ADD PRIMARY KEY (id_parcelle)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
SQL);
    }

    public function down(Schema $schema): void
    {
        // Keeping this empty on purpose: downgrading identity columns safely is
        // not supported on legacy schemas.
    }
}


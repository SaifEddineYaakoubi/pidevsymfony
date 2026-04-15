<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fix culture primary key identity generation for existing databases.
 */
final class Version20260415153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure culture.id_culture is an AUTO_INCREMENT primary key';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE culture MODIFY id_culture INT NOT NULL AUTO_INCREMENT');

        $this->addSql(<<<'SQL'
SET @pk := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_TYPE = 'PRIMARY KEY'
    AND TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'culture'
);
SET @sql := IF(@pk = 0, 'ALTER TABLE culture ADD PRIMARY KEY (id_culture)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
SQL);
    }

    public function down(Schema $schema): void
    {
        // Not reversible safely on legacy schemas.
    }
}


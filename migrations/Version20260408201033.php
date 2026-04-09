<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260408201033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recolte CHANGE date_recolte date_recolte DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE rendement CHANGE id_recolte id_recolte INT DEFAULT NULL');
        $this->addSql('ALTER TABLE rendement ADD CONSTRAINT FK_922F06F2EAAED84C FOREIGN KEY (id_recolte) REFERENCES recolte (id_recolte)');
        $this->addSql('CREATE INDEX IDX_922F06F2EAAED84C ON rendement (id_recolte)');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE recolte CHANGE date_recolte date_recolte DATE DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE rendement DROP FOREIGN KEY FK_922F06F2EAAED84C');
        $this->addSql('DROP INDEX IDX_922F06F2EAAED84C ON rendement');
        $this->addSql('ALTER TABLE rendement CHANGE id_recolte id_recolte INT NOT NULL');
    }
}

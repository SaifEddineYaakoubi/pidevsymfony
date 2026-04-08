<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406205550 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE alerte DROP FOREIGN KEY alerte_ibfk_1');
        $this->addSql('ALTER TABLE historique_action DROP FOREIGN KEY historique_action_ibfk_1');
        $this->addSql('DROP TABLE alerte');
        $this->addSql('DROP TABLE historique_action');
        $this->addSql('DROP TABLE ligne_vente');
        $this->addSql('ALTER TABLE badge CHANGE id id INT NOT NULL, CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE description description VARCHAR(255) NOT NULL, CHANGE niveau niveau VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE client CHANGE id_client id_client INT NOT NULL, CHANGE contact contact VARCHAR(100) NOT NULL, CHANGE adresse adresse VARCHAR(150) NOT NULL, CHANGE id_user id_user INT NOT NULL');
        $this->addSql('ALTER TABLE culture DROP FOREIGN KEY culture_ibfk_1');
        $this->addSql('ALTER TABLE culture CHANGE id_culture id_culture INT NOT NULL, CHANGE date_plantation date_plantation DATE NOT NULL, CHANGE date_recolte_prevue date_recolte_prevue DATE NOT NULL, CHANGE etat_croissance etat_croissance VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE culture ADD CONSTRAINT FK_B6A99CEB95B5C063 FOREIGN KEY (id_parcelle) REFERENCES parcelle (id_parcelle) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE culture RENAME INDEX id_parcelle TO IDX_B6A99CEB95B5C063');
        $this->addSql('DROP INDEX user_id ON face_images');
        $this->addSql('ALTER TABLE face_images CHANGE id id INT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE parcelle CHANGE id_parcelle id_parcelle INT NOT NULL, CHANGE localisation localisation VARCHAR(150) NOT NULL, CHANGE etat etat VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE parcelle ADD CONSTRAINT FK_C56E2CF66B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE parcelle RENAME INDEX id_user TO IDX_C56E2CF66B3CA4B');
        $this->addSql('ALTER TABLE produit CHANGE id_produit id_produit INT NOT NULL, CHANGE type type VARCHAR(50) NOT NULL, CHANGE unite unite VARCHAR(20) NOT NULL, CHANGE prix_unitaire prix_unitaire DOUBLE PRECISION NOT NULL, CHANGE id_user id_user INT NOT NULL');
        $this->addSql('ALTER TABLE recolte CHANGE date_recolte date_recolte DATE NOT NULL, CHANGE qualite qualite VARCHAR(50) NOT NULL, CHANGE type_culture type_culture VARCHAR(100) NOT NULL, CHANGE localisation localisation VARCHAR(150) NOT NULL, CHANGE id_user id_user INT NOT NULL');
        $this->addSql('ALTER TABLE recolte ADD CONSTRAINT FK_3433713C6834359B FOREIGN KEY (id_culture) REFERENCES culture (id_culture) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recolte RENAME INDEX id_culture TO IDX_3433713C6834359B');
        $this->addSql('DROP INDEX idx_type_culture ON recolte_archive');
        $this->addSql('DROP INDEX idx_cause_supression ON recolte_archive');
        $this->addSql('DROP INDEX idx_date_archivage ON recolte_archive');
        $this->addSql('DROP INDEX id_recolte_original ON recolte_archive');
        $this->addSql('ALTER TABLE recolte_archive CHANGE id_archive id_archive INT NOT NULL, CHANGE date_recolte date_recolte DATE NOT NULL, CHANGE qualite qualite VARCHAR(100) NOT NULL, CHANGE type_culture type_culture VARCHAR(100) NOT NULL, CHANGE localisation localisation VARCHAR(100) NOT NULL, CHANGE id_user id_user INT NOT NULL');
        $this->addSql('DROP INDEX id_recolte ON rendement');
        $this->addSql('ALTER TABLE soil_analysis CHANGE id id BIGINT NOT NULL, CHANGE latitude latitude DOUBLE PRECISION NOT NULL, CHANGE longitude longitude DOUBLE PRECISION NOT NULL, CHANGE ph ph DOUBLE PRECISION NOT NULL, CHANGE sand_percent sand_percent DOUBLE PRECISION NOT NULL, CHANGE silt_percent silt_percent DOUBLE PRECISION NOT NULL, CHANGE clay_percent clay_percent DOUBLE PRECISION NOT NULL, CHANGE nitrogen nitrogen DOUBLE PRECISION NOT NULL, CHANGE phosphorus phosphorus DOUBLE PRECISION NOT NULL, CHANGE potassium potassium DOUBLE PRECISION NOT NULL, CHANGE organic_carbon organic_carbon DOUBLE PRECISION NOT NULL, CHANGE source source VARCHAR(128) NOT NULL, CHANGE collected_at collected_at DATETIME NOT NULL, CHANGE sand sand DOUBLE PRECISION NOT NULL, CHANGE clay clay DOUBLE PRECISION NOT NULL, CHANGE silt silt DOUBLE PRECISION NOT NULL, CHANGE organic_matter organic_matter DOUBLE PRECISION NOT NULL, CHANGE cation_exchange_capacity cation_exchange_capacity DOUBLE PRECISION NOT NULL, CHANGE soil_type soil_type VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE stock CHANGE id_stock id_stock INT NOT NULL, CHANGE date_entree date_entree DATE NOT NULL, CHANGE date_expiration date_expiration DATE NOT NULL, CHANGE id_user id_user INT NOT NULL');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B365660F7384557 FOREIGN KEY (id_produit) REFERENCES produit (id_produit) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stock RENAME INDEX id_produit TO IDX_4B365660F7384557');
        $this->addSql('DROP INDEX uk_user_badge ON user_badge');
        $this->addSql('ALTER TABLE user_badge CHANGE id id INT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX email ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur CHANGE id_user id_user INT NOT NULL, CHANGE statut statut TINYINT(1) NOT NULL, CHANGE date_creation date_creation DATE NOT NULL, CHANGE face_image_path face_image_path VARCHAR(255) NOT NULL, CHANGE id_agriculteur id_agriculteur INT NOT NULL');
        $this->addSql('DROP INDEX id_user ON utilisateur_badge');
        $this->addSql('DROP INDEX id_badge ON utilisateur_badge');
        $this->addSql('ALTER TABLE utilisateur_badge CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE vente CHANGE id_vente id_vente INT NOT NULL, CHANGE date_vente date_vente DATE NOT NULL, CHANGE montant_total montant_total DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4CE173B1B8 FOREIGN KEY (id_client) REFERENCES client (id_client) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4C6B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vente RENAME INDEX id_client TO IDX_888A2A4CE173B1B8');
        $this->addSql('ALTER TABLE vente RENAME INDEX id_user TO IDX_888A2A4C6B3CA4B');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE alerte (id_alerte INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, message TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, date_alerte DATE DEFAULT \'curdate()\', statut VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, id_user INT DEFAULT NULL, INDEX id_user (id_user), PRIMARY KEY(id_alerte)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE historique_action (id_action INT AUTO_INCREMENT NOT NULL, action VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, date_action DATE DEFAULT \'curdate()\', id_user INT DEFAULT NULL, INDEX id_user (id_user), PRIMARY KEY(id_action)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE ligne_vente (id_ligne INT AUTO_INCREMENT NOT NULL, quantite DOUBLE PRECISION NOT NULL, prix DOUBLE PRECISION NOT NULL, id_vente INT DEFAULT NULL, id_produit INT DEFAULT NULL, INDEX id_vente (id_vente), INDEX id_produit (id_produit), PRIMARY KEY(id_ligne)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE alerte ADD CONSTRAINT alerte_ibfk_1 FOREIGN KEY (id_user) REFERENCES utilisateur (id_user)');
        $this->addSql('ALTER TABLE historique_action ADD CONSTRAINT historique_action_ibfk_1 FOREIGN KEY (id_user) REFERENCES utilisateur (id_user)');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE badge CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE nom nom VARCHAR(100) DEFAULT \'NULL\', CHANGE description description VARCHAR(255) DEFAULT \'NULL\', CHANGE niveau niveau VARCHAR(50) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE client CHANGE id_client id_client INT AUTO_INCREMENT NOT NULL, CHANGE contact contact VARCHAR(100) DEFAULT \'NULL\', CHANGE adresse adresse VARCHAR(150) DEFAULT \'NULL\', CHANGE id_user id_user INT DEFAULT NULL');
        $this->addSql('ALTER TABLE culture DROP FOREIGN KEY FK_B6A99CEB95B5C063');
        $this->addSql('ALTER TABLE culture CHANGE id_culture id_culture INT AUTO_INCREMENT NOT NULL, CHANGE date_plantation date_plantation DATE DEFAULT \'NULL\', CHANGE date_recolte_prevue date_recolte_prevue DATE DEFAULT \'NULL\', CHANGE etat_croissance etat_croissance VARCHAR(50) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE culture ADD CONSTRAINT culture_ibfk_1 FOREIGN KEY (id_parcelle) REFERENCES parcelle (id_parcelle)');
        $this->addSql('ALTER TABLE culture RENAME INDEX idx_b6a99ceb95b5c063 TO id_parcelle');
        $this->addSql('ALTER TABLE face_images CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX user_id ON face_images (user_id)');
        $this->addSql('ALTER TABLE parcelle DROP FOREIGN KEY FK_C56E2CF66B3CA4B');
        $this->addSql('ALTER TABLE parcelle CHANGE id_parcelle id_parcelle INT AUTO_INCREMENT NOT NULL, CHANGE localisation localisation VARCHAR(150) DEFAULT \'NULL\', CHANGE etat etat VARCHAR(50) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE parcelle RENAME INDEX idx_c56e2cf66b3ca4b TO id_user');
        $this->addSql('ALTER TABLE produit CHANGE id_produit id_produit INT AUTO_INCREMENT NOT NULL, CHANGE type type VARCHAR(50) DEFAULT \'NULL\', CHANGE unite unite VARCHAR(20) DEFAULT \'NULL\', CHANGE prix_unitaire prix_unitaire DOUBLE PRECISION DEFAULT \'NULL\', CHANGE id_user id_user INT DEFAULT NULL');
        $this->addSql('ALTER TABLE recolte DROP FOREIGN KEY FK_3433713C6834359B');
        $this->addSql('ALTER TABLE recolte CHANGE date_recolte date_recolte DATE DEFAULT \'NULL\', CHANGE qualite qualite VARCHAR(50) DEFAULT \'NULL\', CHANGE type_culture type_culture VARCHAR(100) DEFAULT \'NULL\', CHANGE localisation localisation VARCHAR(150) DEFAULT \'NULL\', CHANGE id_user id_user INT DEFAULT NULL');
        $this->addSql('ALTER TABLE recolte RENAME INDEX idx_3433713c6834359b TO id_culture');
        $this->addSql('ALTER TABLE recolte_archive CHANGE id_archive id_archive INT AUTO_INCREMENT NOT NULL, CHANGE date_recolte date_recolte DATE DEFAULT \'NULL\', CHANGE qualite qualite VARCHAR(100) DEFAULT \'NULL\', CHANGE type_culture type_culture VARCHAR(100) DEFAULT \'NULL\', CHANGE localisation localisation VARCHAR(100) DEFAULT \'NULL\', CHANGE id_user id_user INT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_type_culture ON recolte_archive (type_culture)');
        $this->addSql('CREATE INDEX idx_cause_supression ON recolte_archive (cause_supression)');
        $this->addSql('CREATE INDEX idx_date_archivage ON recolte_archive (date_archivage)');
        $this->addSql('CREATE INDEX id_recolte_original ON recolte_archive (id_recolte_original)');
        $this->addSql('CREATE INDEX id_recolte ON rendement (id_recolte)');
        $this->addSql('ALTER TABLE soil_analysis CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE latitude latitude DOUBLE PRECISION DEFAULT \'NULL\', CHANGE longitude longitude DOUBLE PRECISION DEFAULT \'NULL\', CHANGE ph ph DOUBLE PRECISION DEFAULT \'NULL\', CHANGE sand_percent sand_percent DOUBLE PRECISION DEFAULT \'NULL\', CHANGE silt_percent silt_percent DOUBLE PRECISION DEFAULT \'NULL\', CHANGE clay_percent clay_percent DOUBLE PRECISION DEFAULT \'NULL\', CHANGE nitrogen nitrogen DOUBLE PRECISION DEFAULT \'NULL\', CHANGE phosphorus phosphorus DOUBLE PRECISION DEFAULT \'NULL\', CHANGE potassium potassium DOUBLE PRECISION DEFAULT \'NULL\', CHANGE organic_carbon organic_carbon DOUBLE PRECISION DEFAULT \'NULL\', CHANGE source source VARCHAR(128) DEFAULT \'NULL\', CHANGE collected_at collected_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE sand sand DOUBLE PRECISION DEFAULT \'NULL\', CHANGE clay clay DOUBLE PRECISION DEFAULT \'NULL\', CHANGE silt silt DOUBLE PRECISION DEFAULT \'NULL\', CHANGE organic_matter organic_matter DOUBLE PRECISION DEFAULT \'NULL\', CHANGE cation_exchange_capacity cation_exchange_capacity DOUBLE PRECISION DEFAULT \'NULL\', CHANGE soil_type soil_type VARCHAR(100) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B365660F7384557');
        $this->addSql('ALTER TABLE stock CHANGE id_stock id_stock INT AUTO_INCREMENT NOT NULL, CHANGE date_entree date_entree DATE DEFAULT \'NULL\', CHANGE date_expiration date_expiration DATE DEFAULT \'NULL\', CHANGE id_user id_user INT DEFAULT NULL');
        $this->addSql('ALTER TABLE stock RENAME INDEX idx_4b365660f7384557 TO id_produit');
        $this->addSql('ALTER TABLE user_badge CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uk_user_badge ON user_badge (user_id, badge_id)');
        $this->addSql('ALTER TABLE utilisateur CHANGE id_user id_user INT AUTO_INCREMENT NOT NULL, CHANGE statut statut TINYINT(1) DEFAULT 1, CHANGE date_creation date_creation DATE DEFAULT \'curdate()\', CHANGE face_image_path face_image_path VARCHAR(255) DEFAULT \'NULL\', CHANGE id_agriculteur id_agriculteur INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX email ON utilisateur (email)');
        $this->addSql('ALTER TABLE utilisateur_badge CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('CREATE INDEX id_user ON utilisateur_badge (id_user)');
        $this->addSql('CREATE INDEX id_badge ON utilisateur_badge (id_badge)');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4CE173B1B8');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4C6B3CA4B');
        $this->addSql('ALTER TABLE vente CHANGE id_vente id_vente INT AUTO_INCREMENT NOT NULL, CHANGE date_vente date_vente DATE DEFAULT \'curdate()\', CHANGE montant_total montant_total DOUBLE PRECISION DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE vente RENAME INDEX idx_888a2a4ce173b1b8 TO id_client');
        $this->addSql('ALTER TABLE vente RENAME INDEX idx_888a2a4c6b3ca4b TO id_user');
    }
}

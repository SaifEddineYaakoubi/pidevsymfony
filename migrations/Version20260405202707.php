<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260405202707 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Some environments already have this table (manual creation, previous bundle install, etc.).
        // Make the migration idempotent to avoid blocking other schema changes.
        $this->addSql('CREATE TABLE IF NOT EXISTS messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE badge CHANGE id id INT NOT NULL, CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE description description VARCHAR(255) NOT NULL, CHANGE niveau niveau VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE client CHANGE id_client id_client INT NOT NULL, CHANGE contact contact VARCHAR(100) NOT NULL, CHANGE adresse adresse VARCHAR(150) NOT NULL, CHANGE id_user id_user INT NOT NULL');
        $this->addSql('ALTER TABLE culture CHANGE date_plantation date_plantation DATE NOT NULL, CHANGE date_recolte_prevue date_recolte_prevue DATE NOT NULL, CHANGE etat_croissance etat_croissance VARCHAR(50) NOT NULL, CHANGE id_parcelle id_parcelle INT NOT NULL');
        // Keep existing index on culture(id_parcelle).
        // Dropping it may fail if it is required by an existing foreign key constraint.
        $this->addSql('DROP INDEX IF EXISTS user_id ON face_images');
        $this->addSql('ALTER TABLE face_images CHANGE id id INT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE parcelle CHANGE localisation localisation VARCHAR(150) NOT NULL, CHANGE etat etat VARCHAR(50) NOT NULL, CHANGE id_user id_user INT NOT NULL');
        // Do NOT add the parcelle(id_user) foreign key here.
        // Existing databases may contain orphan rows (id_user not present in utilisateur),
        // which would make this migration fail. The FK can be added later after data cleanup.
        // MariaDB: recreate the index instead of renaming
        $this->addSql('DROP INDEX IF EXISTS id_user ON parcelle');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_C56E2CF66B3CA4B ON parcelle (id_user)');
        $this->addSql('ALTER TABLE produit CHANGE id_produit id_produit INT NOT NULL, CHANGE type type VARCHAR(50) NOT NULL, CHANGE unite unite VARCHAR(20) NOT NULL, CHANGE prix_unitaire prix_unitaire DOUBLE PRECISION NOT NULL, CHANGE id_user id_user INT NOT NULL');
        $this->addSql('ALTER TABLE recolte CHANGE id_recolte id_recolte INT NOT NULL, CHANGE date_recolte date_recolte DATE NOT NULL, CHANGE qualite qualite VARCHAR(50) NOT NULL, CHANGE type_culture type_culture VARCHAR(100) NOT NULL, CHANGE localisation localisation VARCHAR(150) NOT NULL, CHANGE id_user id_user INT NOT NULL');
        // Skip adding this foreign key in this legacy migration.
        // On existing databases it may already exist (or a similar FK exists), causing errno 121.
        // Keep existing index on recolte(id_culture).
        // Dropping it may fail if it is required by an existing foreign key constraint.
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_3433713C6834359B ON recolte (id_culture)');
        $this->addSql('DROP INDEX IF EXISTS idx_type_culture ON recolte_archive');
        $this->addSql('DROP INDEX IF EXISTS idx_cause_supression ON recolte_archive');
        $this->addSql('DROP INDEX IF EXISTS idx_date_archivage ON recolte_archive');
        $this->addSql('DROP INDEX IF EXISTS id_recolte_original ON recolte_archive');
        $this->addSql('ALTER TABLE recolte_archive CHANGE id_archive id_archive INT NOT NULL, CHANGE date_recolte date_recolte DATE NOT NULL, CHANGE qualite qualite VARCHAR(100) NOT NULL, CHANGE type_culture type_culture VARCHAR(100) NOT NULL, CHANGE localisation localisation VARCHAR(100) NOT NULL, CHANGE id_user id_user INT NOT NULL');
        $this->addSql('DROP INDEX IF EXISTS id_recolte ON rendement');
        $this->addSql('ALTER TABLE rendement CHANGE id_rendement id_rendement INT NOT NULL');
        $this->addSql('ALTER TABLE soil_analysis CHANGE id id BIGINT NOT NULL, CHANGE latitude latitude DOUBLE PRECISION NOT NULL, CHANGE longitude longitude DOUBLE PRECISION NOT NULL, CHANGE ph ph DOUBLE PRECISION NOT NULL, CHANGE sand_percent sand_percent DOUBLE PRECISION NOT NULL, CHANGE silt_percent silt_percent DOUBLE PRECISION NOT NULL, CHANGE clay_percent clay_percent DOUBLE PRECISION NOT NULL, CHANGE nitrogen nitrogen DOUBLE PRECISION NOT NULL, CHANGE phosphorus phosphorus DOUBLE PRECISION NOT NULL, CHANGE potassium potassium DOUBLE PRECISION NOT NULL, CHANGE organic_carbon organic_carbon DOUBLE PRECISION NOT NULL, CHANGE source source VARCHAR(128) NOT NULL, CHANGE collected_at collected_at DATETIME NOT NULL, CHANGE sand sand DOUBLE PRECISION NOT NULL, CHANGE clay clay DOUBLE PRECISION NOT NULL, CHANGE silt silt DOUBLE PRECISION NOT NULL, CHANGE organic_matter organic_matter DOUBLE PRECISION NOT NULL, CHANGE cation_exchange_capacity cation_exchange_capacity DOUBLE PRECISION NOT NULL, CHANGE soil_type soil_type VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE stock CHANGE id_stock id_stock INT NOT NULL, CHANGE date_entree date_entree DATE NOT NULL, CHANGE date_expiration date_expiration DATE NOT NULL, CHANGE id_user id_user INT NOT NULL');
        // Skip adding stock(id_produit) FK in this legacy migration.
        // On existing databases it may already exist (or a similar FK exists), causing errno 121.
        // Keep existing index on stock(id_produit).
        // Dropping it may fail if it is required by an existing foreign key constraint.
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_4B365660F7384557 ON stock (id_produit)');
        $this->addSql('DROP INDEX IF EXISTS uk_user_badge ON user_badge');
        $this->addSql('ALTER TABLE user_badge CHANGE id id INT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX IF EXISTS email ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur CHANGE id_user id_user INT NOT NULL, CHANGE statut statut TINYINT(1) NOT NULL, CHANGE date_creation date_creation DATE NOT NULL, CHANGE face_image_path face_image_path VARCHAR(255) NOT NULL, CHANGE id_agriculteur id_agriculteur INT NOT NULL');
        $this->addSql('DROP INDEX IF EXISTS id_user ON utilisateur_badge');
        $this->addSql('DROP INDEX IF EXISTS id_badge ON utilisateur_badge');
        $this->addSql('ALTER TABLE utilisateur_badge CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE vente CHANGE id_vente id_vente INT NOT NULL, CHANGE date_vente date_vente DATE NOT NULL, CHANGE montant_total montant_total DOUBLE PRECISION NOT NULL');
        // Skip adding vente(id_client) FK in this legacy migration.
        // On existing databases it may already exist (or a similar FK exists), causing errno 121.
        // Do NOT add vente(id_user) foreign key here.
        // Existing databases may contain orphan rows (id_user not present in utilisateur),
        // which would make this migration fail.
        // Keep existing index on vente(id_client).
        // Dropping it may fail if it is required by an existing foreign key constraint.
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_888A2A4CE173B1B8 ON vente (id_client)');
        $this->addSql('DROP INDEX IF EXISTS id_user ON vente');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_888A2A4C6B3CA4B ON vente (id_user)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE badge CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE nom nom VARCHAR(100) DEFAULT \'NULL\', CHANGE description description VARCHAR(255) DEFAULT \'NULL\', CHANGE niveau niveau VARCHAR(50) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE client CHANGE id_client id_client INT AUTO_INCREMENT NOT NULL, CHANGE contact contact VARCHAR(100) DEFAULT \'NULL\', CHANGE adresse adresse VARCHAR(150) DEFAULT \'NULL\', CHANGE id_user id_user INT DEFAULT NULL');
        $this->addSql('ALTER TABLE culture CHANGE date_plantation date_plantation DATE DEFAULT \'NULL\', CHANGE date_recolte_prevue date_recolte_prevue DATE DEFAULT \'NULL\', CHANGE etat_croissance etat_croissance VARCHAR(50) DEFAULT \'NULL\', CHANGE id_parcelle id_parcelle INT DEFAULT NULL');
        // Keep existing index on culture(id_parcelle) unchanged.
        $this->addSql('ALTER TABLE face_images CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX user_id ON face_images (user_id)');
        $this->addSql('ALTER TABLE parcelle CHANGE localisation localisation VARCHAR(150) DEFAULT \'NULL\', CHANGE etat etat VARCHAR(50) DEFAULT \'NULL\', CHANGE id_user id_user INT DEFAULT NULL');
        $this->addSql('DROP INDEX IDX_C56E2CF66B3CA4B ON parcelle');
        $this->addSql('CREATE INDEX id_user ON parcelle (id_user)');
        $this->addSql('ALTER TABLE produit CHANGE id_produit id_produit INT AUTO_INCREMENT NOT NULL, CHANGE type type VARCHAR(50) DEFAULT \'NULL\', CHANGE unite unite VARCHAR(20) DEFAULT \'NULL\', CHANGE prix_unitaire prix_unitaire DOUBLE PRECISION DEFAULT \'NULL\', CHANGE id_user id_user INT DEFAULT NULL');
        $this->addSql('ALTER TABLE recolte CHANGE id_recolte id_recolte INT AUTO_INCREMENT NOT NULL, CHANGE date_recolte date_recolte DATE DEFAULT \'NULL\', CHANGE qualite qualite VARCHAR(50) DEFAULT \'NULL\', CHANGE type_culture type_culture VARCHAR(100) DEFAULT \'NULL\', CHANGE localisation localisation VARCHAR(150) DEFAULT \'NULL\', CHANGE id_user id_user INT DEFAULT NULL');
        $this->addSql('DROP INDEX IDX_3433713C6834359B ON recolte');
        $this->addSql('CREATE INDEX id_culture ON recolte (id_culture)');
        $this->addSql('ALTER TABLE recolte_archive CHANGE id_archive id_archive INT AUTO_INCREMENT NOT NULL, CHANGE date_recolte date_recolte DATE DEFAULT \'NULL\', CHANGE qualite qualite VARCHAR(100) DEFAULT \'NULL\', CHANGE type_culture type_culture VARCHAR(100) DEFAULT \'NULL\', CHANGE localisation localisation VARCHAR(100) DEFAULT \'NULL\', CHANGE id_user id_user INT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_type_culture ON recolte_archive (type_culture)');
        $this->addSql('CREATE INDEX idx_cause_supression ON recolte_archive (cause_supression)');
        $this->addSql('CREATE INDEX idx_date_archivage ON recolte_archive (date_archivage)');
        $this->addSql('CREATE INDEX id_recolte_original ON recolte_archive (id_recolte_original)');
        $this->addSql('ALTER TABLE rendement CHANGE id_rendement id_rendement INT AUTO_INCREMENT NOT NULL');
        $this->addSql('CREATE INDEX id_recolte ON rendement (id_recolte)');
        $this->addSql('ALTER TABLE soil_analysis CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE latitude latitude DOUBLE PRECISION DEFAULT \'NULL\', CHANGE longitude longitude DOUBLE PRECISION DEFAULT \'NULL\', CHANGE ph ph DOUBLE PRECISION DEFAULT \'NULL\', CHANGE sand_percent sand_percent DOUBLE PRECISION DEFAULT \'NULL\', CHANGE silt_percent silt_percent DOUBLE PRECISION DEFAULT \'NULL\', CHANGE clay_percent clay_percent DOUBLE PRECISION DEFAULT \'NULL\', CHANGE nitrogen nitrogen DOUBLE PRECISION DEFAULT \'NULL\', CHANGE phosphorus phosphorus DOUBLE PRECISION DEFAULT \'NULL\', CHANGE potassium potassium DOUBLE PRECISION DEFAULT \'NULL\', CHANGE organic_carbon organic_carbon DOUBLE PRECISION DEFAULT \'NULL\', CHANGE source source VARCHAR(128) DEFAULT \'NULL\', CHANGE collected_at collected_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE sand sand DOUBLE PRECISION DEFAULT \'NULL\', CHANGE clay clay DOUBLE PRECISION DEFAULT \'NULL\', CHANGE silt silt DOUBLE PRECISION DEFAULT \'NULL\', CHANGE organic_matter organic_matter DOUBLE PRECISION DEFAULT \'NULL\', CHANGE cation_exchange_capacity cation_exchange_capacity DOUBLE PRECISION DEFAULT \'NULL\', CHANGE soil_type soil_type VARCHAR(100) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE stock CHANGE id_stock id_stock INT AUTO_INCREMENT NOT NULL, CHANGE date_entree date_entree DATE DEFAULT \'NULL\', CHANGE date_expiration date_expiration DATE DEFAULT \'NULL\', CHANGE id_user id_user INT DEFAULT NULL');
        $this->addSql('DROP INDEX IDX_4B365660F7384557 ON stock');
        $this->addSql('CREATE INDEX id_produit ON stock (id_produit)');
        $this->addSql('ALTER TABLE user_badge CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uk_user_badge ON user_badge (user_id, badge_id)');
        $this->addSql('ALTER TABLE utilisateur CHANGE id_user id_user INT AUTO_INCREMENT NOT NULL, CHANGE statut statut TINYINT(1) DEFAULT 1, CHANGE date_creation date_creation DATE DEFAULT \'curdate()\', CHANGE face_image_path face_image_path VARCHAR(255) DEFAULT \'NULL\', CHANGE id_agriculteur id_agriculteur INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX email ON utilisateur (email)');
        $this->addSql('ALTER TABLE utilisateur_badge CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('CREATE INDEX id_user ON utilisateur_badge (id_user)');
        $this->addSql('CREATE INDEX id_badge ON utilisateur_badge (id_badge)');
        $this->addSql('ALTER TABLE vente CHANGE id_vente id_vente INT AUTO_INCREMENT NOT NULL, CHANGE date_vente date_vente DATE DEFAULT \'curdate()\', CHANGE montant_total montant_total DOUBLE PRECISION DEFAULT \'NULL\'');
        $this->addSql('DROP INDEX IDX_888A2A4CE173B1B8 ON vente');
        $this->addSql('CREATE INDEX id_client ON vente (id_client)');
        $this->addSql('DROP INDEX IDX_888A2A4C6B3CA4B ON vente');
        $this->addSql('CREATE INDEX id_user ON vente (id_user)');
    }
}

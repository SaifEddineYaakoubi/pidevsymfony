<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour WebAuthn - Suppression des anciennes colonnes de reconnaissance faciale
 * et ajout des nouvelles colonnes pour WebAuthn
 */
final class Version_WebAuthn extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migration vers WebAuthn - Suppression des anciennes colonnes face_* et ajout de webauthn_user_handle';
    }

    public function up(Schema $schema): void
    {
        // Supprimer les anciennes colonnes de reconnaissance faciale
        $this->addSql('ALTER TABLE utilisateur DROP COLUMN IF EXISTS face_image');
        $this->addSql('ALTER TABLE utilisateur DROP COLUMN IF EXISTS face_enabled');
        $this->addSql('ALTER TABLE utilisateur DROP COLUMN IF EXISTS face_credential_id');
        $this->addSql('ALTER TABLE utilisateur DROP COLUMN IF EXISTS face_descriptors');
        
        // Ajouter la nouvelle colonne pour WebAuthn
        $this->addSql('ALTER TABLE utilisateur ADD COLUMN webauthn_user_handle VARCHAR(255) DEFAULT NULL UNIQUE');
        
        // Créer la table pour les Passkeys
        $this->addSql('CREATE TABLE IF NOT EXISTS passkey (
            id VARCHAR(255) NOT NULL PRIMARY KEY,
            user_handle VARCHAR(255) NOT NULL,
            public_key_credential_id BLOB NOT NULL,
            type VARCHAR(255) NOT NULL,
            transports TEXT,
            attestation_type VARCHAR(255) NOT NULL,
            trust_path TEXT,
            aaguid BLOB NOT NULL,
            credential_public_key BLOB NOT NULL,
            counter INT NOT NULL,
            INDEX idx_user_handle (user_handle)
        )');
    }

    public function down(Schema $schema): void
    {
        // Restaurer les anciennes colonnes
        $this->addSql('ALTER TABLE utilisateur ADD COLUMN face_image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD COLUMN face_enabled TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD COLUMN face_credential_id TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD COLUMN face_descriptors JSON DEFAULT NULL');
        
        // Supprimer la colonne WebAuthn
        $this->addSql('ALTER TABLE utilisateur DROP COLUMN webauthn_user_handle');
        
        // Supprimer la table passkey
        $this->addSql('DROP TABLE IF EXISTS passkey');
    }
}

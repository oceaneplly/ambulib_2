<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230724075827 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE profil (id SERIAL NOT NULL, designation VARCHAR(45) NOT NULL, role VARCHAR(45) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E6D6B29757698A6A ON profil (role)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E6D6B2978947610D ON profil (designation)');
        $this->addSql('CREATE TABLE utilisateur (id SERIAL NOT NULL, profil INT DEFAULT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, login VARCHAR(20) NOT NULL, password VARCHAR(50) DEFAULT NULL, mail_actif BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX profil_id_idx ON utilisateur (profil)');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3E6D6B297 FOREIGN KEY (profil) REFERENCES profil (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE utilisateur DROP CONSTRAINT FK_1D1C63B3E6D6B297');
        $this->addSql('DROP TABLE profil');
        $this->addSql('DROP TABLE utilisateur');
    }
}

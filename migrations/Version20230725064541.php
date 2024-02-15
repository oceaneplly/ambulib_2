<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230725064541 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE flux (id SERIAL NOT NULL, id_vetement INT DEFAULT NULL, id_personne INT DEFAULT NULL, date_entree TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, date_sortie TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, code VARCHAR(200) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX vetement_id_idx ON flux (id_vetement)');
        $this->addSql('CREATE INDEX personne_id_idx ON flux (id_personne)');
        $this->addSql('CREATE TABLE personne (id SERIAL NOT NULL, code VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE flux ADD CONSTRAINT FK_7252313A6C6FAA5A FOREIGN KEY (id_vetement) REFERENCES vetement (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE flux ADD CONSTRAINT FK_7252313A5F15257A FOREIGN KEY (id_personne) REFERENCES personne (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE flux DROP CONSTRAINT FK_7252313A6C6FAA5A');
        $this->addSql('ALTER TABLE flux DROP CONSTRAINT FK_7252313A5F15257A');
        $this->addSql('DROP TABLE flux');
        $this->addSql('DROP TABLE personne');
    }
}

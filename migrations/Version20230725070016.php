<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230725070016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE flux_id_seq1 CASCADE');
        $this->addSql('DROP SEQUENCE personne_id_seq1 CASCADE');
        $this->addSql('ALTER TABLE flux DROP CONSTRAINT fk_7252313a6c6faa5a');
        $this->addSql('ALTER TABLE flux DROP CONSTRAINT fk_7252313a5f15257a');
        $this->addSql('DROP INDEX personne_id_idx');
        $this->addSql('DROP INDEX vetement_id_idx');
        $this->addSql('ALTER TABLE flux ADD vetement INT DEFAULT NULL');
        $this->addSql('ALTER TABLE flux ADD personne INT DEFAULT NULL');
        $this->addSql('ALTER TABLE flux DROP id_vetement');
        $this->addSql('ALTER TABLE flux DROP id_personne');
        $this->addSql('ALTER TABLE flux ADD CONSTRAINT FK_7252313A3CB446CF FOREIGN KEY (vetement) REFERENCES vetement (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE flux ADD CONSTRAINT FK_7252313AFCEC9EF FOREIGN KEY (personne) REFERENCES personne (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX personne_id_idx ON flux (personne)');
        $this->addSql('CREATE INDEX vetement_id_idx ON flux (vetement)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE flux_id_seq1 INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE personne_id_seq1 INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE flux DROP CONSTRAINT FK_7252313A3CB446CF');
        $this->addSql('ALTER TABLE flux DROP CONSTRAINT FK_7252313AFCEC9EF');
        $this->addSql('DROP INDEX vetement_id_idx');
        $this->addSql('DROP INDEX personne_id_idx');
        $this->addSql('ALTER TABLE flux ADD id_vetement INT DEFAULT NULL');
        $this->addSql('ALTER TABLE flux ADD id_personne INT DEFAULT NULL');
        $this->addSql('ALTER TABLE flux DROP vetement');
        $this->addSql('ALTER TABLE flux DROP personne');
        $this->addSql('ALTER TABLE flux ADD CONSTRAINT fk_7252313a6c6faa5a FOREIGN KEY (id_vetement) REFERENCES vetement (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE flux ADD CONSTRAINT fk_7252313a5f15257a FOREIGN KEY (id_personne) REFERENCES personne (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX vetement_id_idx ON flux (id_vetement)');
        $this->addSql('CREATE INDEX personne_id_idx ON flux (id_personne)');
    }
}

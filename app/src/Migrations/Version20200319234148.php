<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200319234148 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE users ADD status VARCHAR(10) CHECK (status IN (\'active\', \'invited\')) NOT NULL');
        $this->addSql('ALTER TABLE users DROP is_active');
        $this->addSql('ALTER TABLE users ALTER roles SET NOT NULL');
        $this->addSql('ALTER TABLE users ALTER password DROP NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE users ADD is_active BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE users DROP status');
        $this->addSql('ALTER TABLE users ALTER roles DROP NOT NULL');
        $this->addSql('ALTER TABLE users ALTER password SET NOT NULL');
    }
}

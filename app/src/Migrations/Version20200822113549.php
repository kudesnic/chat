<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200822113549 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE chat DROP CONSTRAINT fk_659df2aaa37e1784');
        $this->addSql('DROP INDEX idx_659df2aaa37e1784');
        $this->addSql('ALTER TABLE chat DROP unread_messages_sender_id');
        $this->addSql('ALTER TABLE chat DROP unread_messages_count');
        $this->addSql('ALTER TABLE users ALTER roles TYPE json');
        $this->addSql('ALTER TABLE users ALTER roles DROP DEFAULT');
        $this->addSql('ALTER TABLE users ALTER status TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE participant ADD unread_messages_count SMALLINT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE participant DROP unread_messages_count');
        $this->addSql('ALTER TABLE users ALTER roles TYPE JSON');
        $this->addSql('ALTER TABLE users ALTER roles DROP DEFAULT');
        $this->addSql('ALTER TABLE users ALTER status TYPE VARCHAR(10)');
        $this->addSql('ALTER TABLE chat ADD unread_messages_sender_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE chat ADD unread_messages_count SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE chat ADD CONSTRAINT fk_659df2aaa37e1784 FOREIGN KEY (unread_messages_sender_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_659df2aaa37e1784 ON chat (unread_messages_sender_id)');
    }
}

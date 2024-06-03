<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240603184126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE telegram_status_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE telegram_status (id INT NOT NULL, chat INT NOT NULL, status SMALLINT NOT NULL, PRIMARY KEY(id))');

//        $this->addSql('CREATE SEQUENCE telegram_response_id_seq');
        $this->addSql('SELECT setval(\'telegram_response_id_seq\', (SELECT MAX(id) FROM telegram_response))');
        $this->addSql('ALTER TABLE telegram_response ALTER id SET DEFAULT nextval(\'telegram_response_id_seq\')');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE telegram_status_id_seq CASCADE');

        $this->addSql('DROP TABLE telegram_status');
        $this->addSql('ALTER TABLE telegram_response ALTER id DROP DEFAULT');
    }
}

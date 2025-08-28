<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250827103247 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE professor (id INT AUTO_INCREMENT NOT NULL, user_id_id INT NOT NULL, UNIQUE INDEX UNIQ_790DD7E39D86650F (user_id_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE professor ADD CONSTRAINT FK_790DD7E39D86650F FOREIGN KEY (user_id_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE professor DROP FOREIGN KEY FK_790DD7E39D86650F');
        $this->addSql('DROP TABLE professor');
    }
}

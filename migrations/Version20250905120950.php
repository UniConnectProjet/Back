<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250905120950 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE professor_category (professor_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_6A15A3437D2D84D5 (professor_id), INDEX IDX_6A15A34312469DE2 (category_id), PRIMARY KEY (professor_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE professor_category ADD CONSTRAINT FK_6A15A3437D2D84D5 FOREIGN KEY (professor_id) REFERENCES professor (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE professor_category ADD CONSTRAINT FK_6A15A34312469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE professor_category DROP FOREIGN KEY FK_6A15A3437D2D84D5');
        $this->addSql('ALTER TABLE professor_category DROP FOREIGN KEY FK_6A15A34312469DE2');
        $this->addSql('DROP TABLE professor_category');
    }
}

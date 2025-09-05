<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250905113745 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE professor_course (professor_id INT NOT NULL, course_id INT NOT NULL, INDEX IDX_3C7933807D2D84D5 (professor_id), INDEX IDX_3C793380591CC992 (course_id), PRIMARY KEY (professor_id, course_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE professor_course ADD CONSTRAINT FK_3C7933807D2D84D5 FOREIGN KEY (professor_id) REFERENCES professor (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE professor_course ADD CONSTRAINT FK_3C793380591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE professor_course DROP FOREIGN KEY FK_3C7933807D2D84D5');
        $this->addSql('ALTER TABLE professor_course DROP FOREIGN KEY FK_3C793380591CC992');
        $this->addSql('DROP TABLE professor_course');
    }
}

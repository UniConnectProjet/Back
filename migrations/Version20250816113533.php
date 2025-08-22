<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250816113533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE course_session (id INT AUTO_INCREMENT NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, room VARCHAR(255) NOT NULL, course_id INT NOT NULL, clesse_id INT NOT NULL, professor_id INT DEFAULT NULL, INDEX IDX_D887D038591CC992 (course_id), INDEX IDX_D887D0387B11811A (clesse_id), INDEX IDX_D887D0387D2D84D5 (professor_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE course_session ADD CONSTRAINT FK_D887D038591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE course_session ADD CONSTRAINT FK_D887D0387B11811A FOREIGN KEY (clesse_id) REFERENCES classe (id)');
        $this->addSql('ALTER TABLE course_session ADD CONSTRAINT FK_D887D0387D2D84D5 FOREIGN KEY (professor_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course_session DROP FOREIGN KEY FK_D887D038591CC992');
        $this->addSql('ALTER TABLE course_session DROP FOREIGN KEY FK_D887D0387B11811A');
        $this->addSql('ALTER TABLE course_session DROP FOREIGN KEY FK_D887D0387D2D84D5');
        $this->addSql('DROP TABLE course_session');
    }
}

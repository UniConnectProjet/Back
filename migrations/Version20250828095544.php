<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250828095544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course_session ADD professor_id INT NOT NULL');
        $this->addSql('ALTER TABLE course_session ADD CONSTRAINT FK_D887D0387D2D84D5 FOREIGN KEY (professor_id) REFERENCES professor (id)');
        $this->addSql('CREATE INDEX IDX_D887D0387D2D84D5 ON course_session (professor_id)');
        $this->addSql('ALTER TABLE professor ADD weekly_availability JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course_session DROP FOREIGN KEY FK_D887D0387D2D84D5');
        $this->addSql('DROP INDEX IDX_D887D0387D2D84D5 ON course_session');
        $this->addSql('ALTER TABLE course_session DROP professor_id');
        $this->addSql('ALTER TABLE professor DROP weekly_availability');
    }
}

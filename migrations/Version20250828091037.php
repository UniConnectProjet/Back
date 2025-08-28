<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250828091037 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course_session DROP FOREIGN KEY `FK_D887D0387D2D84D5`');
        $this->addSql('DROP INDEX IDX_D887D0387D2D84D5 ON course_session');
        $this->addSql('ALTER TABLE course_session DROP professor_id');
        $this->addSql('ALTER TABLE professor DROP primary_subject, DROP contrat_type, DROP weekly_availability');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course_session ADD professor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE course_session ADD CONSTRAINT `FK_D887D0387D2D84D5` FOREIGN KEY (professor_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_D887D0387D2D84D5 ON course_session (professor_id)');
        $this->addSql('ALTER TABLE professor ADD primary_subject VARCHAR(255) DEFAULT NULL, ADD contrat_type VARCHAR(255) NOT NULL, ADD weekly_availability JSON DEFAULT NULL');
    }
}

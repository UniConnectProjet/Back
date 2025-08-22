<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250816121617 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course_session DROP FOREIGN KEY `FK_D887D038591CC992`');
        $this->addSql('ALTER TABLE course_session DROP FOREIGN KEY `FK_D887D0387B11811A`');
        $this->addSql('DROP INDEX IDX_D887D0387B11811A ON course_session');
        $this->addSql('ALTER TABLE course_session CHANGE room room VARCHAR(64) DEFAULT NULL, CHANGE clesse_id classe_id INT NOT NULL');
        $this->addSql('ALTER TABLE course_session ADD CONSTRAINT FK_D887D038591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_session ADD CONSTRAINT FK_D887D0388F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_D887D0388F5EA509 ON course_session (classe_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE course_session DROP FOREIGN KEY FK_D887D038591CC992');
        $this->addSql('ALTER TABLE course_session DROP FOREIGN KEY FK_D887D0388F5EA509');
        $this->addSql('DROP INDEX IDX_D887D0388F5EA509 ON course_session');
        $this->addSql('ALTER TABLE course_session CHANGE room room VARCHAR(255) NOT NULL, CHANGE classe_id clesse_id INT NOT NULL');
        $this->addSql('ALTER TABLE course_session ADD CONSTRAINT `FK_D887D038591CC992` FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE course_session ADD CONSTRAINT `FK_D887D0387B11811A` FOREIGN KEY (clesse_id) REFERENCES classe (id)');
        $this->addSql('CREATE INDEX IDX_D887D0387B11811A ON course_session (clesse_id)');
    }
}

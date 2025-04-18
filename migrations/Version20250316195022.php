<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250316195022 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add semester system with related associations - MariaDB compatible';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE semester (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE TABLE semester_classe (
            semester_id INT NOT NULL,
            classe_id INT NOT NULL,
            PRIMARY KEY(semester_id, classe_id)
        )');

        $this->addSql('CREATE INDEX IDX_BA9937334A798B6F ON semester_classe (semester_id)');
        $this->addSql('CREATE INDEX IDX_BA9937338F5EA509 ON semester_classe (classe_id)');

        $this->addSql('CREATE TABLE student_semester (
            student_id INT NOT NULL,
            semester_id INT NOT NULL,
            PRIMARY KEY(student_id, semester_id)
        )');

        $this->addSql('CREATE INDEX IDX_44B3C276CB944F1A ON student_semester (student_id)');
        $this->addSql('CREATE INDEX IDX_44B3C2764A798B6F ON student_semester (semester_id)');

        $this->addSql('ALTER TABLE semester_classe ADD CONSTRAINT FK_BA9937334A798B6F FOREIGN KEY (semester_id) REFERENCES semester (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE semester_classe ADD CONSTRAINT FK_BA9937338F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE student_semester ADD CONSTRAINT FK_44B3C276CB944F1A FOREIGN KEY (student_id) REFERENCES student (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE student_semester ADD CONSTRAINT FK_44B3C2764A798B6F FOREIGN KEY (semester_id) REFERENCES semester (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE absence ADD semester_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_765AE0C94A798B6F ON absence (semester_id)');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C94A798B6F FOREIGN KEY (semester_id) REFERENCES semester (id)');

        $this->addSql('ALTER TABLE course_unit ADD semester_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_1419D1554A798B6F ON course_unit (semester_id)');
        $this->addSql('ALTER TABLE course_unit ADD CONSTRAINT FK_1419D1554A798B6F FOREIGN KEY (semester_id) REFERENCES semester (id)');

        $this->addSql('ALTER TABLE course_unit ADD average_score DOUBLE DEFAULT NULL');
        $this->addSql('ALTER TABLE course_unit DROP COLUMN semester');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C94A798B6F');
        $this->addSql('ALTER TABLE course_unit DROP FOREIGN KEY FK_1419D1554A798B6F');
        $this->addSql('ALTER TABLE semester_classe DROP FOREIGN KEY FK_BA9937334A798B6F');
        $this->addSql('ALTER TABLE semester_classe DROP FOREIGN KEY FK_BA9937338F5EA509');
        $this->addSql('ALTER TABLE student_semester DROP FOREIGN KEY FK_44B3C276CB944F1A');
        $this->addSql('ALTER TABLE student_semester DROP FOREIGN KEY FK_44B3C2764A798B6F');

        $this->addSql('DROP TABLE semester');
        $this->addSql('DROP TABLE semester_classe');
        $this->addSql('DROP TABLE student_semester');

        $this->addSql('DROP INDEX IDX_1419D1554A798B6F ON course_unit');
        $this->addSql('ALTER TABLE course_unit ADD semester INT NOT NULL');
        $this->addSql('ALTER TABLE course_unit DROP COLUMN semester_id');
        $this->addSql('ALTER TABLE course_unit DROP COLUMN average_score');

        $this->addSql('DROP INDEX IDX_765AE0C94A798B6F ON absence');
        $this->addSql('ALTER TABLE absence DROP COLUMN semester_id');
    }
}

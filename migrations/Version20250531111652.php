<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250531111652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE absence (id INT AUTO_INCREMENT NOT NULL, started_date DATETIME NOT NULL, ended_date DATETIME NOT NULL, justified TINYINT(1) NOT NULL, justification VARCHAR(255) DEFAULT NULL, student_id INT DEFAULT NULL, semester_id INT DEFAULT NULL, INDEX IDX_765AE0C9CB944F1A (student_id), INDEX IDX_765AE0C94A798B6F (semester_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE classe (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, level_id_id INT DEFAULT NULL, category_id INT DEFAULT NULL, INDEX IDX_8F87BF96159D9B5E (level_id_id), INDEX IDX_8F87BF9612469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE course (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, average DOUBLE PRECISION NOT NULL, course_unit_id INT DEFAULT NULL, INDEX IDX_169E6FB9F07E75E1 (course_unit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE course_classe (course_id INT NOT NULL, classe_id INT NOT NULL, INDEX IDX_21BF7EDB591CC992 (course_id), INDEX IDX_21BF7EDB8F5EA509 (classe_id), PRIMARY KEY(course_id, classe_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE course_unit (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, average DOUBLE PRECISION NOT NULL, average_score DOUBLE PRECISION DEFAULT NULL, semester_id INT DEFAULT NULL, category_id INT DEFAULT NULL, INDEX IDX_1419D1554A798B6F (semester_id), INDEX IDX_1419D15512469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE grade (id INT AUTO_INCREMENT NOT NULL, grade DOUBLE PRECISION NOT NULL, dividor DOUBLE PRECISION NOT NULL, title VARCHAR(255) NOT NULL, student_id INT DEFAULT NULL, course_id INT DEFAULT NULL, INDEX IDX_595AAE34CB944F1A (student_id), INDEX IDX_595AAE34591CC992 (course_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE level (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE refresh_tokens (refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, UNIQUE INDEX UNIQ_9BACE7E1C74F2195 (refresh_token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE semester (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE semester_student (semester_id INT NOT NULL, student_id INT NOT NULL, INDEX IDX_11AAE6DC4A798B6F (semester_id), INDEX IDX_11AAE6DCCB944F1A (student_id), PRIMARY KEY(semester_id, student_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE semester_classe (semester_id INT NOT NULL, classe_id INT NOT NULL, INDEX IDX_BA9937334A798B6F (semester_id), INDEX IDX_BA9937338F5EA509 (classe_id), PRIMARY KEY(semester_id, classe_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE student (id INT AUTO_INCREMENT NOT NULL, classe_id INT NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_B723AF338F5EA509 (classe_id), UNIQUE INDEX UNIQ_B723AF33A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE student_course (student_id INT NOT NULL, course_id INT NOT NULL, INDEX IDX_98A8B739CB944F1A (student_id), INDEX IDX_98A8B739591CC992 (course_id), PRIMARY KEY(student_id, course_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, birthday DATE NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE absence ADD CONSTRAINT FK_765AE0C9CB944F1A FOREIGN KEY (student_id) REFERENCES student (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE absence ADD CONSTRAINT FK_765AE0C94A798B6F FOREIGN KEY (semester_id) REFERENCES semester (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE classe ADD CONSTRAINT FK_8F87BF96159D9B5E FOREIGN KEY (level_id_id) REFERENCES level (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE classe ADD CONSTRAINT FK_8F87BF9612469DE2 FOREIGN KEY (category_id) REFERENCES category (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course ADD CONSTRAINT FK_169E6FB9F07E75E1 FOREIGN KEY (course_unit_id) REFERENCES course_unit (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_classe ADD CONSTRAINT FK_21BF7EDB591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_classe ADD CONSTRAINT FK_21BF7EDB8F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_unit ADD CONSTRAINT FK_1419D1554A798B6F FOREIGN KEY (semester_id) REFERENCES semester (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_unit ADD CONSTRAINT FK_1419D15512469DE2 FOREIGN KEY (category_id) REFERENCES category (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE grade ADD CONSTRAINT FK_595AAE34CB944F1A FOREIGN KEY (student_id) REFERENCES student (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE grade ADD CONSTRAINT FK_595AAE34591CC992 FOREIGN KEY (course_id) REFERENCES course (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE semester_student ADD CONSTRAINT FK_11AAE6DC4A798B6F FOREIGN KEY (semester_id) REFERENCES semester (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE semester_student ADD CONSTRAINT FK_11AAE6DCCB944F1A FOREIGN KEY (student_id) REFERENCES student (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE semester_classe ADD CONSTRAINT FK_BA9937334A798B6F FOREIGN KEY (semester_id) REFERENCES semester (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE semester_classe ADD CONSTRAINT FK_BA9937338F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE student ADD CONSTRAINT FK_B723AF338F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE student ADD CONSTRAINT FK_B723AF33A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE student_course ADD CONSTRAINT FK_98A8B739CB944F1A FOREIGN KEY (student_id) REFERENCES student (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE student_course ADD CONSTRAINT FK_98A8B739591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C9CB944F1A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C94A798B6F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE classe DROP FOREIGN KEY FK_8F87BF96159D9B5E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE classe DROP FOREIGN KEY FK_8F87BF9612469DE2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course DROP FOREIGN KEY FK_169E6FB9F07E75E1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_classe DROP FOREIGN KEY FK_21BF7EDB591CC992
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_classe DROP FOREIGN KEY FK_21BF7EDB8F5EA509
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_unit DROP FOREIGN KEY FK_1419D1554A798B6F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_unit DROP FOREIGN KEY FK_1419D15512469DE2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE grade DROP FOREIGN KEY FK_595AAE34CB944F1A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE grade DROP FOREIGN KEY FK_595AAE34591CC992
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE semester_student DROP FOREIGN KEY FK_11AAE6DC4A798B6F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE semester_student DROP FOREIGN KEY FK_11AAE6DCCB944F1A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE semester_classe DROP FOREIGN KEY FK_BA9937334A798B6F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE semester_classe DROP FOREIGN KEY FK_BA9937338F5EA509
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE student DROP FOREIGN KEY FK_B723AF338F5EA509
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE student DROP FOREIGN KEY FK_B723AF33A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE student_course DROP FOREIGN KEY FK_98A8B739CB944F1A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE student_course DROP FOREIGN KEY FK_98A8B739591CC992
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE absence
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE category
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE classe
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE course
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE course_classe
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE course_unit
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE grade
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE level
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE refresh_tokens
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE semester
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE semester_student
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE semester_classe
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE student
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE student_course
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE `user`
        SQL);
    }
}

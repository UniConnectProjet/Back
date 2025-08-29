<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250828125831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE absence (id INT AUTO_INCREMENT NOT NULL, started_date DATETIME NOT NULL, ended_date DATETIME NOT NULL, justified TINYINT(1) NOT NULL, justification VARCHAR(255) DEFAULT NULL, student_id INT DEFAULT NULL, semester_id INT DEFAULT NULL, INDEX IDX_765AE0C9CB944F1A (student_id), INDEX IDX_765AE0C94A798B6F (semester_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE category_level (category_id INT NOT NULL, level_id INT NOT NULL, INDEX IDX_72D9835A12469DE2 (category_id), INDEX IDX_72D9835A5FB14BA7 (level_id), PRIMARY KEY (category_id, level_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE classe (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, level_id_id INT DEFAULT NULL, category_id INT DEFAULT NULL, INDEX IDX_8F87BF96159D9B5E (level_id_id), INDEX IDX_8F87BF9612469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE course (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, average DOUBLE PRECISION NOT NULL, course_unit_id INT DEFAULT NULL, INDEX IDX_169E6FB9F07E75E1 (course_unit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE course_classe (course_id INT NOT NULL, classe_id INT NOT NULL, INDEX IDX_21BF7EDB591CC992 (course_id), INDEX IDX_21BF7EDB8F5EA509 (classe_id), PRIMARY KEY (course_id, classe_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE course_session (id INT AUTO_INCREMENT NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, room VARCHAR(64) DEFAULT NULL, course_id INT NOT NULL, classe_id INT NOT NULL, professor_id INT NOT NULL, INDEX IDX_D887D038591CC992 (course_id), INDEX IDX_D887D0388F5EA509 (classe_id), INDEX IDX_D887D0387D2D84D5 (professor_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE course_unit (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, average DOUBLE PRECISION NOT NULL, average_score DOUBLE PRECISION DEFAULT NULL, semester_id INT DEFAULT NULL, category_id INT DEFAULT NULL, levels_id INT DEFAULT NULL, INDEX IDX_1419D1554A798B6F (semester_id), INDEX IDX_1419D15512469DE2 (category_id), INDEX IDX_1419D155AF9C3A25 (levels_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE grade (id INT AUTO_INCREMENT NOT NULL, grade DOUBLE PRECISION NOT NULL, dividor DOUBLE PRECISION NOT NULL, title VARCHAR(255) NOT NULL, student_id INT DEFAULT NULL, course_id INT DEFAULT NULL, semester_id INT DEFAULT NULL, INDEX IDX_595AAE34CB944F1A (student_id), INDEX IDX_595AAE34591CC992 (course_id), INDEX IDX_595AAE344A798B6F (semester_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE level (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE professor (id INT AUTO_INCREMENT NOT NULL, weekly_availability JSON DEFAULT NULL, user_id_id INT NOT NULL, UNIQUE INDEX UNIQ_790DD7E39D86650F (user_id_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE refresh_tokens (refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, UNIQUE INDEX UNIQ_9BACE7E1C74F2195 (refresh_token), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE semester (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE semester_student (semester_id INT NOT NULL, student_id INT NOT NULL, INDEX IDX_11AAE6DC4A798B6F (semester_id), INDEX IDX_11AAE6DCCB944F1A (student_id), PRIMARY KEY (semester_id, student_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE semester_classe (semester_id INT NOT NULL, classe_id INT NOT NULL, INDEX IDX_BA9937334A798B6F (semester_id), INDEX IDX_BA9937338F5EA509 (classe_id), PRIMARY KEY (semester_id, classe_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE student (id INT AUTO_INCREMENT NOT NULL, classe_id INT NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_B723AF338F5EA509 (classe_id), UNIQUE INDEX UNIQ_B723AF33A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE student_course (student_id INT NOT NULL, course_id INT NOT NULL, INDEX IDX_98A8B739CB944F1A (student_id), INDEX IDX_98A8B739591CC992 (course_id), PRIMARY KEY (student_id, course_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, birthday DATE NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C9CB944F1A FOREIGN KEY (student_id) REFERENCES student (id)');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C94A798B6F FOREIGN KEY (semester_id) REFERENCES semester (id)');
        $this->addSql('ALTER TABLE category_level ADD CONSTRAINT FK_72D9835A12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category_level ADD CONSTRAINT FK_72D9835A5FB14BA7 FOREIGN KEY (level_id) REFERENCES level (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classe ADD CONSTRAINT FK_8F87BF96159D9B5E FOREIGN KEY (level_id_id) REFERENCES level (id)');
        $this->addSql('ALTER TABLE classe ADD CONSTRAINT FK_8F87BF9612469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE course ADD CONSTRAINT FK_169E6FB9F07E75E1 FOREIGN KEY (course_unit_id) REFERENCES course_unit (id)');
        $this->addSql('ALTER TABLE course_classe ADD CONSTRAINT FK_21BF7EDB591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_classe ADD CONSTRAINT FK_21BF7EDB8F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_session ADD CONSTRAINT FK_D887D038591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_session ADD CONSTRAINT FK_D887D0388F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_session ADD CONSTRAINT FK_D887D0387D2D84D5 FOREIGN KEY (professor_id) REFERENCES professor (id)');
        $this->addSql('ALTER TABLE course_unit ADD CONSTRAINT FK_1419D1554A798B6F FOREIGN KEY (semester_id) REFERENCES semester (id)');
        $this->addSql('ALTER TABLE course_unit ADD CONSTRAINT FK_1419D15512469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE course_unit ADD CONSTRAINT FK_1419D155AF9C3A25 FOREIGN KEY (levels_id) REFERENCES level (id)');
        $this->addSql('ALTER TABLE grade ADD CONSTRAINT FK_595AAE34CB944F1A FOREIGN KEY (student_id) REFERENCES student (id)');
        $this->addSql('ALTER TABLE grade ADD CONSTRAINT FK_595AAE34591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE grade ADD CONSTRAINT FK_595AAE344A798B6F FOREIGN KEY (semester_id) REFERENCES semester (id)');
        $this->addSql('ALTER TABLE professor ADD CONSTRAINT FK_790DD7E39D86650F FOREIGN KEY (user_id_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE semester_student ADD CONSTRAINT FK_11AAE6DC4A798B6F FOREIGN KEY (semester_id) REFERENCES semester (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE semester_student ADD CONSTRAINT FK_11AAE6DCCB944F1A FOREIGN KEY (student_id) REFERENCES student (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE semester_classe ADD CONSTRAINT FK_BA9937334A798B6F FOREIGN KEY (semester_id) REFERENCES semester (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE semester_classe ADD CONSTRAINT FK_BA9937338F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF338F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF33A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE student_course ADD CONSTRAINT FK_98A8B739CB944F1A FOREIGN KEY (student_id) REFERENCES student (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE student_course ADD CONSTRAINT FK_98A8B739591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C9CB944F1A');
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C94A798B6F');
        $this->addSql('ALTER TABLE category_level DROP FOREIGN KEY FK_72D9835A12469DE2');
        $this->addSql('ALTER TABLE category_level DROP FOREIGN KEY FK_72D9835A5FB14BA7');
        $this->addSql('ALTER TABLE classe DROP FOREIGN KEY FK_8F87BF96159D9B5E');
        $this->addSql('ALTER TABLE classe DROP FOREIGN KEY FK_8F87BF9612469DE2');
        $this->addSql('ALTER TABLE course DROP FOREIGN KEY FK_169E6FB9F07E75E1');
        $this->addSql('ALTER TABLE course_classe DROP FOREIGN KEY FK_21BF7EDB591CC992');
        $this->addSql('ALTER TABLE course_classe DROP FOREIGN KEY FK_21BF7EDB8F5EA509');
        $this->addSql('ALTER TABLE course_session DROP FOREIGN KEY FK_D887D038591CC992');
        $this->addSql('ALTER TABLE course_session DROP FOREIGN KEY FK_D887D0388F5EA509');
        $this->addSql('ALTER TABLE course_session DROP FOREIGN KEY FK_D887D0387D2D84D5');
        $this->addSql('ALTER TABLE course_unit DROP FOREIGN KEY FK_1419D1554A798B6F');
        $this->addSql('ALTER TABLE course_unit DROP FOREIGN KEY FK_1419D15512469DE2');
        $this->addSql('ALTER TABLE course_unit DROP FOREIGN KEY FK_1419D155AF9C3A25');
        $this->addSql('ALTER TABLE grade DROP FOREIGN KEY FK_595AAE34CB944F1A');
        $this->addSql('ALTER TABLE grade DROP FOREIGN KEY FK_595AAE34591CC992');
        $this->addSql('ALTER TABLE grade DROP FOREIGN KEY FK_595AAE344A798B6F');
        $this->addSql('ALTER TABLE professor DROP FOREIGN KEY FK_790DD7E39D86650F');
        $this->addSql('ALTER TABLE semester_student DROP FOREIGN KEY FK_11AAE6DC4A798B6F');
        $this->addSql('ALTER TABLE semester_student DROP FOREIGN KEY FK_11AAE6DCCB944F1A');
        $this->addSql('ALTER TABLE semester_classe DROP FOREIGN KEY FK_BA9937334A798B6F');
        $this->addSql('ALTER TABLE semester_classe DROP FOREIGN KEY FK_BA9937338F5EA509');
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF338F5EA509');
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF33A76ED395');
        $this->addSql('ALTER TABLE student_course DROP FOREIGN KEY FK_98A8B739CB944F1A');
        $this->addSql('ALTER TABLE student_course DROP FOREIGN KEY FK_98A8B739591CC992');
        $this->addSql('DROP TABLE absence');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE category_level');
        $this->addSql('DROP TABLE classe');
        $this->addSql('DROP TABLE course');
        $this->addSql('DROP TABLE course_classe');
        $this->addSql('DROP TABLE course_session');
        $this->addSql('DROP TABLE course_unit');
        $this->addSql('DROP TABLE grade');
        $this->addSql('DROP TABLE level');
        $this->addSql('DROP TABLE professor');
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE semester');
        $this->addSql('DROP TABLE semester_student');
        $this->addSql('DROP TABLE semester_classe');
        $this->addSql('DROP TABLE student');
        $this->addSql('DROP TABLE student_course');
        $this->addSql('DROP TABLE `user`');
    }
}

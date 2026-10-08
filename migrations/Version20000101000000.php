<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Baseline: the schema as it was before migrations were introduced (2026-10-08, before the start
 * location of #19). Databases created earlier with doctrine:schema:create, such as production,
 * already have it, so there the migration runs no SQL and is only recorded as executed.
 */
final class Version20000101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Baseline schema (no changes on databases that already have it)';
    }

    public function up(Schema $schema): void
    {
        // Not skipIf(): skipped migrations aren't recorded, so the baseline would stay pending forever
        if ($schema->hasTable('event')) {
            $this->write('The schema already exists, the baseline is only recorded as executed.');

            return;
        }

        $this->addSql('CREATE TABLE event_chronicle (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, summary VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, published_at DATETIME DEFAULT NULL, start_date DATETIME NOT NULL, end_date DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, photo_album_g VARCHAR(255) DEFAULT NULL, publish TINYINT NOT NULL, modified_at DATETIME DEFAULT NULL, created_by_id INT NOT NULL, author_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_5A04E5D1EB92D913 (photo_album_g), INDEX IDX_5A04E5D1B03A8386 (created_by_id), INDEX IDX_5A04E5D1BC6F00C9 (author_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE event_chronicle_sport_type (event_chronicle_id INT NOT NULL, sport_type_id INT NOT NULL, INDEX IDX_1E5AC6E1CE6E59DD (event_chronicle_id), INDEX IDX_1E5AC6E164F9C039 (sport_type_id), PRIMARY KEY (event_chronicle_id, sport_type_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE event_chronicle_event_route (event_chronicle_id INT NOT NULL, event_route_id INT NOT NULL, INDEX IDX_628E3E5FCE6E59DD (event_chronicle_id), INDEX IDX_628E3E5F72005D36 (event_route_id), PRIMARY KEY (event_chronicle_id, event_route_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE blog (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, summary VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, publish TINYINT NOT NULL, created_at DATETIME NOT NULL, published_at DATETIME DEFAULT NULL, modified_at DATETIME DEFAULT NULL, start_date DATETIME DEFAULT NULL, section_id INT NOT NULL, created_by_id INT NOT NULL, author_by_id INT DEFAULT NULL, INDEX IDX_C0155143D823E37A (section_id), INDEX IDX_C0155143B03A8386 (created_by_id), INDEX IDX_C0155143BC6F00C9 (author_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE blog_sport_type (blog_id INT NOT NULL, sport_type_id INT NOT NULL, INDEX IDX_1DC75B3CDAE07E97 (blog_id), INDEX IDX_1DC75B3C64F9C039 (sport_type_id), PRIMARY KEY (blog_id, sport_type_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE event (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME DEFAULT NULL, publish TINYINT NOT NULL, created_at DATETIME NOT NULL, show_date TINYINT NOT NULL, modified_at DATETIME DEFAULT NULL, content LONGTEXT DEFAULT NULL, published_at DATETIME DEFAULT NULL, event_invitation_id INT DEFAULT NULL, event_chronicle_id INT DEFAULT NULL, blog_id INT DEFAULT NULL, created_by_id INT NOT NULL, author_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_3BAE0AA78704CA5C (event_invitation_id), UNIQUE INDEX UNIQ_3BAE0AA7CE6E59DD (event_chronicle_id), UNIQUE INDEX UNIQ_3BAE0AA7DAE07E97 (blog_id), INDEX IDX_3BAE0AA7B03A8386 (created_by_id), INDEX IDX_3BAE0AA7BC6F00C9 (author_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE event_sport_type (event_id INT NOT NULL, sport_type_id INT NOT NULL, INDEX IDX_A3EFA0FA71F7E88B (event_id), INDEX IDX_A3EFA0FA64F9C039 (sport_type_id), PRIMARY KEY (event_id, sport_type_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE blog_section (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_C185C76C989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, nick_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, display_name VARCHAR(190) NOT NULL, UNIQUE INDEX UNIQ_8D93D649A045A5E9 (nick_name), UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sport_type (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, shortcut VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE event_invitation (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, summary VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, published_at DATETIME DEFAULT NULL, start_date DATETIME NOT NULL, end_date DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, publish TINYINT NOT NULL, modified_at DATETIME DEFAULT NULL, created_by_id INT NOT NULL, author_by_id INT DEFAULT NULL, INDEX IDX_A9F3B88DB03A8386 (created_by_id), INDEX IDX_A9F3B88DBC6F00C9 (author_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE event_invitation_sport_type (event_invitation_id INT NOT NULL, sport_type_id INT NOT NULL, INDEX IDX_A36739008704CA5C (event_invitation_id), INDEX IDX_A367390064F9C039 (sport_type_id), PRIMARY KEY (event_invitation_id, sport_type_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE event_invitation_event_route (event_invitation_id INT NOT NULL, event_route_id INT NOT NULL, INDEX IDX_B53ED14E8704CA5C (event_invitation_id), INDEX IDX_B53ED14E72005D36 (event_route_id), PRIMARY KEY (event_invitation_id, event_route_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE event_route (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, length INT NOT NULL, created_at DATETIME NOT NULL, gpx_slug VARCHAR(255) DEFAULT NULL, event_date DATETIME DEFAULT NULL, elevation INT DEFAULT NULL, gpx LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cache_items (item_id VARBINARY(255) NOT NULL, item_data MEDIUMBLOB NOT NULL, item_lifetime INT UNSIGNED DEFAULT NULL, item_time INT UNSIGNED NOT NULL, PRIMARY KEY (item_id))');
        $this->addSql('ALTER TABLE event_chronicle ADD CONSTRAINT FK_5A04E5D1B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE event_chronicle ADD CONSTRAINT FK_5A04E5D1BC6F00C9 FOREIGN KEY (author_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE event_chronicle_sport_type ADD CONSTRAINT FK_1E5AC6E1CE6E59DD FOREIGN KEY (event_chronicle_id) REFERENCES event_chronicle (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_chronicle_sport_type ADD CONSTRAINT FK_1E5AC6E164F9C039 FOREIGN KEY (sport_type_id) REFERENCES sport_type (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_chronicle_event_route ADD CONSTRAINT FK_628E3E5FCE6E59DD FOREIGN KEY (event_chronicle_id) REFERENCES event_chronicle (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_chronicle_event_route ADD CONSTRAINT FK_628E3E5F72005D36 FOREIGN KEY (event_route_id) REFERENCES event_route (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE blog ADD CONSTRAINT FK_C0155143D823E37A FOREIGN KEY (section_id) REFERENCES blog_section (id)');
        $this->addSql('ALTER TABLE blog ADD CONSTRAINT FK_C0155143B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE blog ADD CONSTRAINT FK_C0155143BC6F00C9 FOREIGN KEY (author_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE blog_sport_type ADD CONSTRAINT FK_1DC75B3CDAE07E97 FOREIGN KEY (blog_id) REFERENCES blog (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE blog_sport_type ADD CONSTRAINT FK_1DC75B3C64F9C039 FOREIGN KEY (sport_type_id) REFERENCES sport_type (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA78704CA5C FOREIGN KEY (event_invitation_id) REFERENCES event_invitation (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7CE6E59DD FOREIGN KEY (event_chronicle_id) REFERENCES event_chronicle (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7DAE07E97 FOREIGN KEY (blog_id) REFERENCES blog (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7BC6F00C9 FOREIGN KEY (author_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE event_sport_type ADD CONSTRAINT FK_A3EFA0FA71F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_sport_type ADD CONSTRAINT FK_A3EFA0FA64F9C039 FOREIGN KEY (sport_type_id) REFERENCES sport_type (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_invitation ADD CONSTRAINT FK_A9F3B88DB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE event_invitation ADD CONSTRAINT FK_A9F3B88DBC6F00C9 FOREIGN KEY (author_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE event_invitation_sport_type ADD CONSTRAINT FK_A36739008704CA5C FOREIGN KEY (event_invitation_id) REFERENCES event_invitation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_invitation_sport_type ADD CONSTRAINT FK_A367390064F9C039 FOREIGN KEY (sport_type_id) REFERENCES sport_type (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_invitation_event_route ADD CONSTRAINT FK_B53ED14E8704CA5C FOREIGN KEY (event_invitation_id) REFERENCES event_invitation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_invitation_event_route ADD CONSTRAINT FK_B53ED14E72005D36 FOREIGN KEY (event_route_id) REFERENCES event_route (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The baseline creates the whole schema; drop the database instead.');
    }
}

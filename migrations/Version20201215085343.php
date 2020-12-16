<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201215085343 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE blog CHANGE author_by_id author_by_id INT DEFAULT NULL, CHANGE published_at published_at DATETIME DEFAULT NULL, CHANGE modified_at modified_at DATETIME DEFAULT NULL, CHANGE start_date start_date DATE DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C185C76C989D9B62 ON blog_section (slug)');
        $this->addSql('ALTER TABLE event CHANGE event_invitation_id event_invitation_id INT DEFAULT NULL, CHANGE event_chronicle_id event_chronicle_id INT DEFAULT NULL, CHANGE blog_id blog_id INT DEFAULT NULL, CHANGE author_by_id author_by_id INT DEFAULT NULL, CHANGE end_date end_date DATE DEFAULT NULL, CHANGE modified_at modified_at DATETIME DEFAULT NULL, CHANGE published_at published_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE event_chronicle CHANGE author_by_id author_by_id INT DEFAULT NULL, CHANGE published_at published_at DATETIME DEFAULT NULL, CHANGE end_date end_date DATETIME DEFAULT NULL, CHANGE photo_album_g photo_album_g VARCHAR(190) DEFAULT NULL, CHANGE modified_at modified_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE event_invitation CHANGE author_by_id author_by_id INT DEFAULT NULL, CHANGE published_at published_at DATETIME DEFAULT NULL, CHANGE end_date end_date DATETIME DEFAULT NULL, CHANGE modified_at modified_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE event_route CHANGE gpx_slug gpx_slug VARCHAR(190) DEFAULT NULL, CHANGE event_date event_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE sport_type CHANGE image image VARCHAR(190) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE blog CHANGE author_by_id author_by_id INT DEFAULT NULL, CHANGE published_at published_at DATETIME DEFAULT \'NULL\', CHANGE modified_at modified_at DATETIME DEFAULT \'NULL\', CHANGE start_date start_date DATE DEFAULT \'NULL\'');
        $this->addSql('DROP INDEX UNIQ_C185C76C989D9B62 ON blog_section');
        $this->addSql('ALTER TABLE event CHANGE event_invitation_id event_invitation_id INT DEFAULT NULL, CHANGE event_chronicle_id event_chronicle_id INT DEFAULT NULL, CHANGE blog_id blog_id INT DEFAULT NULL, CHANGE author_by_id author_by_id INT DEFAULT NULL, CHANGE end_date end_date DATE DEFAULT \'NULL\', CHANGE modified_at modified_at DATETIME DEFAULT \'NULL\', CHANGE published_at published_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE event_chronicle CHANGE author_by_id author_by_id INT DEFAULT NULL, CHANGE published_at published_at DATETIME DEFAULT \'NULL\', CHANGE end_date end_date DATETIME DEFAULT \'NULL\', CHANGE photo_album_g photo_album_g VARCHAR(190) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE modified_at modified_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE event_invitation CHANGE author_by_id author_by_id INT DEFAULT NULL, CHANGE published_at published_at DATETIME DEFAULT \'NULL\', CHANGE end_date end_date DATETIME DEFAULT \'NULL\', CHANGE modified_at modified_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE event_route CHANGE gpx_slug gpx_slug VARCHAR(190) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE event_date event_date DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE sport_type CHANGE image image VARCHAR(190) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
    }
}

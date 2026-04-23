<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260416131716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE costumer_tags (costumer_id INT NOT NULL, tags_id INT NOT NULL, INDEX IDX_E4ECFD2360B71152 (costumer_id), INDEX IDX_E4ECFD238D7B4FB4 (tags_id), PRIMARY KEY (costumer_id, tags_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE Tags (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE costumer_tags ADD CONSTRAINT FK_E4ECFD2360B71152 FOREIGN KEY (costumer_id) REFERENCES Costumer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE costumer_tags ADD CONSTRAINT FK_E4ECFD238D7B4FB4 FOREIGN KEY (tags_id) REFERENCES Tags (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE TimeEntry DROP FOREIGN KEY `FK_988949E1A76ED395`');
        $this->addSql('ALTER TABLE TimeEntry ADD CONSTRAINT FK_988949E1A76ED395 FOREIGN KEY (user_id) REFERENCES Costumer (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE costumer_tags DROP FOREIGN KEY FK_E4ECFD2360B71152');
        $this->addSql('ALTER TABLE costumer_tags DROP FOREIGN KEY FK_E4ECFD238D7B4FB4');
        $this->addSql('DROP TABLE costumer_tags');
        $this->addSql('DROP TABLE Tags');
        $this->addSql('ALTER TABLE TimeEntry DROP FOREIGN KEY FK_988949E1A76ED395');
        $this->addSql('ALTER TABLE TimeEntry ADD CONSTRAINT `FK_988949E1A76ED395` FOREIGN KEY (user_id) REFERENCES Costumer (id)');
    }
}

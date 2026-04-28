<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260326082729 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // $this->addSql('DROP INDEX UNIQ_84459824D17F50A6 ON Costumer');
        // $this->addSql('ALTER TABLE Costumer DROP uuid');
        $rows = $this->connection->fetchAllAssociative('SELECT id,roles FROM user__user');

        /** @var array{'id': scalar, 'roles': string}[] $rows */
        foreach ($rows as $row) {
            $id = $row['id'];
            $roles = json_encode(unserialize($row['roles']));
            $this->connection->executeQuery('UPDATE user__user SET roles = :roles WHERE id = :id', ['roles' => $roles, 'id' => $id]);
        }
        $this->addSql('ALTER TABLE `order` CHANGE order_dateTime order_dateTime DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE user__user CHANGE roles roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        // $this->addSql('ALTER TABLE Costumer ADD uuid BINARY(16) DEFAULT \'(cast(uuid_v4() as char(16) charset binary))\' NOT NULL COMMENT \'(DC2Type:uuid)\'');
        // $this->addSql('CREATE UNIQUE INDEX UNIQ_84459824D17F50A6 ON Costumer (uuid)');
        $this->addSql('ALTER TABLE `order` CHANGE order_dateTime order_dateTime DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user__user CHANGE roles roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\'');
    }
}

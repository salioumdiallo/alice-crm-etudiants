<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260916120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename User business role column to status';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" RENAME COLUMN role TO status');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" RENAME COLUMN status TO role');
    }
}

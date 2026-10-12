<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute l'identifiant de session Stripe sur les commandes, avec une contrainte
 * d'unicité : une session de paiement ne peut créer qu'une seule commande.
 */
final class Version20261011120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute commande.stripe_session_id (unique) pour vérifier les paiements Stripe';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commande ADD stripe_session_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6EEAA67D1A314A57 ON commande (stripe_session_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_6EEAA67D1A314A57 ON commande');
        $this->addSql('ALTER TABLE commande DROP stripe_session_id');
    }
}

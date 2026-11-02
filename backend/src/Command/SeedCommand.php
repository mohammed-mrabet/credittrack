<?php

namespace App\Command;

use App\Entity\Client;
use App\Entity\Invoice;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Jeu de données de démonstration : php bin/console app:seed [--if-empty]
 * Les dates sont calculées par rapport à aujourd'hui pour avoir toujours
 * des factures payées, à venir et en retard.
 */
#[AsCommand(name: 'app:seed', description: 'Charge des clients et des factures de démonstration')]
class SeedCommand extends Command
{
    private const CLIENTS = [
        ['Atlas Distribution SARL', 'compta@atlas-distribution.ma', '0536 12 34 56'],
        ['Oriental Matériaux', 'finance@oriental-materiaux.ma', '0536 45 67 89'],
        ['Berkane Agrumes Export', 'contact@berkane-agrumes.ma', '0536 61 22 33'],
        ['Saïdia Hôtels & Loisirs', 'factures@saidia-hotels.ma', '0536 62 44 10'],
        ['Nador Logistique', 'comptabilite@nador-logistique.ma', '0536 33 90 12'],
    ];

    /** [index client, montant, émise il y a N jours, délai en jours, payée après N jours (null = non payée), description] */
    private const INVOICES = [
        [0, '18500.00', 95, 30, 25, 'Fourniture de matériel informatique'],
        [0, '7200.00', 40, 30, null, 'Maintenance annuelle du parc'],
        [0, '12900.00', 10, 30, null, 'Licences logicielles'],
        [1, '45200.00', 120, 60, null, 'Livraison de ciment - lot 3'],
        [1, '9800.00', 70, 30, 28, 'Transport de marchandises'],
        [1, '15600.00', 15, 45, null, 'Location de matériel'],
        [2, '32400.00', 85, 30, null, 'Emballages export saison 2026'],
        [2, '6100.00', 50, 30, 20, 'Contrôle qualité'],
        [2, '21000.00', 5, 30, null, 'Prestation logistique frigorifique'],
        [3, '11750.00', 65, 15, 14, 'Séminaire - hébergement'],
        [3, '8400.00', 35, 15, null, 'Restauration événementielle'],
        [3, '5300.00', 3, 30, null, 'Location de salle'],
        [4, '27800.00', 100, 45, 40, 'Transit portuaire'],
        [4, '13300.00', 55, 30, null, 'Stockage entrepôt - septembre'],
        [4, '9950.00', 8, 30, null, 'Stockage entrepôt - octobre'],
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ClientRepository $clients,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('if-empty', null, InputOption::VALUE_NONE, 'Ne fait rien si des clients existent déjà');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('if-empty') && $this->clients->count([]) > 0) {
            $io->note('Des données existent déjà : rien à charger.');

            return Command::SUCCESS;
        }

        $this->em->createQuery('DELETE FROM App\Entity\Invoice')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Client')->execute();

        $clients = [];
        foreach (self::CLIENTS as [$name, $email, $phone]) {
            $clients[] = $client = new Client($name, $email, $phone);
            $this->em->persist($client);
        }

        $today = new \DateTimeImmutable('today');
        $counters = [];
        // Numérotation chronologique : on crée d'abord les factures les plus anciennes.
        $rows = self::INVOICES;
        usort($rows, static fn (array $a, array $b) => $b[2] <=> $a[2]);
        foreach ($rows as [$clientIndex, $amount, $issuedDaysAgo, $termDays, $paidAfter, $description]) {
            $issueDate = $today->modify("-$issuedDaysAgo days");
            $year = $issueDate->format('Y');
            $counters[$year] = ($counters[$year] ?? 0) + 1;
            $number = sprintf('FAC-%s-%04d', $year, $counters[$year]);

            $invoice = new Invoice(
                $number,
                $clients[$clientIndex],
                $amount,
                $issueDate,
                $issueDate->modify("+$termDays days"),
                $description,
            );
            if (null !== $paidAfter) {
                $invoice->markAsPaid($issueDate->modify("+$paidAfter days"));
            }
            $this->em->persist($invoice);
        }

        $this->em->flush();
        $io->success(sprintf('%d clients et %d factures chargés.', count(self::CLIENTS), count(self::INVOICES)));

        return Command::SUCCESS;
    }
}

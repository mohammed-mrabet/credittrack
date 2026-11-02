<?php

namespace App\Repository;

use App\Entity\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * Recherche filtrée. Le filtre par statut est traduit en SQL à partir des dates,
     * avec la même règle que Invoice::getStatus().
     *
     * @return Invoice[]
     */
    public function search(?string $status = null, ?int $clientId = null, ?string $query = null): array
    {
        $qb = $this->createQueryBuilder('i')
            ->join('i.client', 'c')
            ->addSelect('c')
            ->orderBy('i.dueDate', 'ASC');

        if (null !== $status) {
            $this->applyStatus($qb, $status);
        }

        if (null !== $clientId) {
            $qb->andWhere('c.id = :clientId')->setParameter('clientId', $clientId);
        }

        if (null !== $query && '' !== trim($query)) {
            $qb->andWhere('LOWER(i.number) LIKE :q OR LOWER(c.name) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower(trim($query)).'%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Somme et nombre de factures par statut.
     *
     * @return array{total: float, count: int}
     */
    public function totalsForStatus(string $status): array
    {
        $qb = $this->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.amount), 0) AS total, COUNT(i.id) AS nb');
        $this->applyStatus($qb, $status);
        $row = $qb->getQuery()->getSingleResult();

        return ['total' => round((float) $row['total'], 2), 'count' => (int) $row['nb']];
    }

    /**
     * Encours (factures non payées) par client, du plus gros au plus petit.
     *
     * @return array<int, array{clientId: int, clientName: string, outstanding: float, overdue: float}>
     */
    public function outstandingByClient(int $limit = 5): array
    {
        $today = new \DateTimeImmutable('today');

        $rows = $this->createQueryBuilder('i')
            ->join('i.client', 'c')
            ->select('c.id AS clientId, c.name AS clientName')
            ->addSelect('SUM(i.amount) AS outstanding')
            ->addSelect('SUM(CASE WHEN i.dueDate < :today THEN i.amount ELSE 0 END) AS overdue')
            ->where('i.paidAt IS NULL')
            ->groupBy('c.id, c.name')
            ->orderBy('outstanding', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('today', $today, Types::DATE_IMMUTABLE)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $r) => [
            'clientId' => (int) $r['clientId'],
            'clientName' => $r['clientName'],
            'outstanding' => round((float) $r['outstanding'], 2),
            'overdue' => round((float) $r['overdue'], 2),
        ], $rows);
    }

    /** Prochain numéro de facture de l'année : FAC-2026-0001, FAC-2026-0002, ... */
    public function nextNumber(?\DateTimeImmutable $date = null): string
    {
        $year = ($date ?? new \DateTimeImmutable())->format('Y');
        $prefix = sprintf('FAC-%s-', $year);

        $last = $this->createQueryBuilder('i')
            ->select('i.number')
            ->where('i.number LIKE :prefix')
            ->setParameter('prefix', $prefix.'%')
            ->orderBy('i.number', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $next = null === $last ? 1 : ((int) substr($last['number'], strlen($prefix))) + 1;

        return $prefix.str_pad((string) $next, 4, '0', \STR_PAD_LEFT);
    }

    private function applyStatus(QueryBuilder $qb, string $status): void
    {
        $alias = $qb->getRootAliases()[0];

        switch ($status) {
            case Invoice::STATUS_PAID:
                $qb->andWhere("$alias.paidAt IS NOT NULL");
                break;
            case Invoice::STATUS_OVERDUE:
                $qb->andWhere("$alias.paidAt IS NULL AND $alias.dueDate < :today")
                    ->setParameter('today', new \DateTimeImmutable('today'), Types::DATE_IMMUTABLE);
                break;
            case Invoice::STATUS_UNPAID:
                $qb->andWhere("$alias.paidAt IS NULL AND $alias.dueDate >= :today")
                    ->setParameter('today', new \DateTimeImmutable('today'), Types::DATE_IMMUTABLE);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Statut inconnu : "%s".', $status));
        }
    }
}

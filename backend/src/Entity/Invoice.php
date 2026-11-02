<?php

namespace App\Entity;

use App\Repository\InvoiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name: 'invoice')]
#[ORM\Index(name: 'idx_invoice_status', columns: ['paid_at', 'due_date'])]
class Invoice
{
    public const STATUS_PAID = 'paid';
    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_OVERDUE = 'overdue';

    public const STATUSES = [self::STATUS_PAID, self::STATUS_UNPAID, self::STATUS_OVERDUE];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    private string $number;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Client $client;

    /** Montant stocké en NUMERIC(12,2) : Doctrine le renvoie sous forme de chaîne pour éviter les erreurs d'arrondi. */
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $amount;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $issueDate;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $dueDate;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $number,
        Client $client,
        string $amount,
        \DateTimeImmutable $issueDate,
        \DateTimeImmutable $dueDate,
        ?string $description = null,
    ) {
        if ($dueDate < $issueDate) {
            throw new \InvalidArgumentException("La date d'échéance doit être postérieure à la date d'émission.");
        }
        if (!is_numeric($amount) || (float) $amount <= 0) {
            throw new \InvalidArgumentException('Le montant doit être un nombre positif.');
        }

        $this->number = $number;
        $this->client = $client;
        $this->amount = number_format((float) $amount, 2, '.', '');
        $this->issueDate = $issueDate;
        $this->dueDate = $dueDate;
        $this->description = $description;
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Statut calculé à partir des dates, jamais stocké : il reste donc toujours cohérent.
     * - paid    : un paiement a été enregistré
     * - overdue : non payée et date d'échéance dépassée
     * - unpaid  : non payée, encore dans les délais
     */
    public function getStatus(?\DateTimeImmutable $today = null): string
    {
        if (null !== $this->paidAt) {
            return self::STATUS_PAID;
        }

        $today = ($today ?? new \DateTimeImmutable())->setTime(0, 0);

        return $this->dueDate < $today ? self::STATUS_OVERDUE : self::STATUS_UNPAID;
    }

    /** Nombre de jours de retard (0 si la facture n'est pas en retard). */
    public function getDaysOverdue(?\DateTimeImmutable $today = null): int
    {
        if (self::STATUS_OVERDUE !== $this->getStatus($today)) {
            return 0;
        }

        $today = ($today ?? new \DateTimeImmutable())->setTime(0, 0);

        return (int) $this->dueDate->diff($today)->days;
    }

    public function markAsPaid(?\DateTimeImmutable $paidAt = null): void
    {
        if (null !== $this->paidAt) {
            throw new \LogicException('Cette facture est déjà payée.');
        }

        $this->paidAt = $paidAt ?? new \DateTimeImmutable('today');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getIssueDate(): \DateTimeImmutable
    {
        return $this->issueDate;
    }

    public function getDueDate(): \DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'client' => ['id' => $this->client->getId(), 'name' => $this->client->getName()],
            'amount' => (float) $this->amount,
            'description' => $this->description,
            'issueDate' => $this->issueDate->format('Y-m-d'),
            'dueDate' => $this->dueDate->format('Y-m-d'),
            'paidAt' => $this->paidAt?->format('Y-m-d'),
            'status' => $this->getStatus(),
            'daysOverdue' => $this->getDaysOverdue(),
        ];
    }
}

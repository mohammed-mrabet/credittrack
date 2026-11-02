<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Repository\InvoiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    public function __construct(private readonly InvoiceRepository $invoices)
    {
    }

    /** Indicateurs du tableau de bord des créances. */
    #[Route('/api/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        $paid = $this->invoices->totalsForStatus(Invoice::STATUS_PAID);
        $unpaid = $this->invoices->totalsForStatus(Invoice::STATUS_UNPAID);
        $overdue = $this->invoices->totalsForStatus(Invoice::STATUS_OVERDUE);

        $billed = $paid['total'] + $unpaid['total'] + $overdue['total'];

        $latestOverdue = array_slice($this->invoices->search(Invoice::STATUS_OVERDUE), 0, 5);

        return new JsonResponse([
            'paid' => $paid,
            'unpaid' => $unpaid,
            'overdue' => $overdue,
            'outstanding' => round($unpaid['total'] + $overdue['total'], 2),
            // Part du chiffre facturé déjà encaissée, en %.
            'collectionRate' => $billed > 0 ? round($paid['total'] / $billed * 100, 1) : 0.0,
            'topClients' => $this->invoices->outstandingByClient(),
            'overdueInvoices' => array_map(static fn (Invoice $i) => $i->toArray(), $latestOverdue),
        ]);
    }

    #[Route('/api/health', name: 'health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }
}

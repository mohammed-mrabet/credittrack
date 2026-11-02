<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Repository\ClientRepository;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/invoices')]
class InvoiceController extends AbstractController
{
    use JsonPayloadTrait;

    public function __construct(
        private readonly InvoiceRepository $invoices,
        private readonly ClientRepository $clients,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** GET /api/invoices?status=overdue&clientId=3&q=FAC-2026 */
    #[Route('', name: 'invoice_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $status = $request->query->get('status') ?: null;
        if (null !== $status && !in_array($status, Invoice::STATUSES, true)) {
            return $this->validationError(['status' => 'Statut attendu : '.implode(', ', Invoice::STATUSES).'.']);
        }

        $clientId = $request->query->get('clientId');
        $clientId = is_numeric($clientId) ? (int) $clientId : null;

        $result = $this->invoices->search($status, $clientId, $request->query->get('q'));

        return new JsonResponse(array_map(static fn (Invoice $i) => $i->toArray(), $result));
    }

    #[Route('/{id<\d+>}', name: 'invoice_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $invoice = $this->invoices->find($id);

        return $invoice ? new JsonResponse($invoice->toArray()) : $this->notFound('Facture introuvable.');
    }

    #[Route('', name: 'invoice_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->payload($request);
        $errors = [];

        $client = isset($data['clientId']) && is_numeric($data['clientId'])
            ? $this->clients->find((int) $data['clientId'])
            : null;
        if (null === $client) {
            $errors['clientId'] = 'Client introuvable.';
        }

        $amount = $data['amount'] ?? null;
        if (!is_numeric($amount) || (float) $amount <= 0) {
            $errors['amount'] = 'Le montant doit être un nombre strictement positif.';
        }

        $issueDate = $this->parseDate($data['issueDate'] ?? null);
        $dueDate = $this->parseDate($data['dueDate'] ?? null);
        if (null === $issueDate) {
            $errors['issueDate'] = "Date d'émission invalide (format AAAA-MM-JJ).";
        }
        if (null === $dueDate) {
            $errors['dueDate'] = "Date d'échéance invalide (format AAAA-MM-JJ).";
        }
        if ($issueDate && $dueDate && $dueDate < $issueDate) {
            $errors['dueDate'] = "L'échéance doit être postérieure à la date d'émission.";
        }

        $description = isset($data['description']) ? mb_substr(trim((string) $data['description']), 0, 255) : null;

        if ($errors) {
            return $this->validationError($errors);
        }

        $invoice = new Invoice(
            $this->invoices->nextNumber($issueDate),
            $client,
            (string) $amount,
            $issueDate,
            $dueDate,
            $description ?: null,
        );
        $this->em->persist($invoice);
        $this->em->flush();

        return new JsonResponse($invoice->toArray(), JsonResponse::HTTP_CREATED);
    }

    /** Enregistre le paiement d'une facture. Corps optionnel : {"paidAt": "2026-11-02"}. */
    #[Route('/{id<\d+>}/pay', name: 'invoice_pay', methods: ['POST'])]
    public function pay(int $id, Request $request): JsonResponse
    {
        $invoice = $this->invoices->find($id);
        if (null === $invoice) {
            return $this->notFound('Facture introuvable.');
        }

        $data = $this->payload($request);
        $paidAt = isset($data['paidAt']) ? $this->parseDate($data['paidAt']) : null;
        if (isset($data['paidAt']) && null === $paidAt) {
            return $this->validationError(['paidAt' => 'Date de paiement invalide (format AAAA-MM-JJ).']);
        }

        try {
            $invoice->markAsPaid($paidAt);
        } catch (\LogicException $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_CONFLICT);
        }
        $this->em->flush();

        return new JsonResponse($invoice->toArray());
    }

    #[Route('/{id<\d+>}', name: 'invoice_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $invoice = $this->invoices->find($id);
        if (null === $invoice) {
            return $this->notFound('Facture introuvable.');
        }

        $this->em->remove($invoice);
        $this->em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}

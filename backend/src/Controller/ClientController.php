<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/clients')]
class ClientController extends AbstractController
{
    use JsonPayloadTrait;

    public function __construct(
        private readonly ClientRepository $clients,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'client_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return new JsonResponse(array_map(
            static fn (Client $c) => $c->toArray(),
            $this->clients->findAllOrdered(),
        ));
    }

    #[Route('', name: 'client_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->payload($request);
        $errors = [];

        $name = trim((string) ($data['name'] ?? ''));
        $email = isset($data['email']) && '' !== trim((string) $data['email']) ? trim((string) $data['email']) : null;
        $phone = isset($data['phone']) && '' !== trim((string) $data['phone']) ? trim((string) $data['phone']) : null;

        if ('' === $name) {
            $errors['name'] = 'Le nom du client est obligatoire.';
        } elseif (mb_strlen($name) > 150) {
            $errors['name'] = 'Le nom ne doit pas dépasser 150 caractères.';
        }
        if (null !== $email && false === filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "L'adresse e-mail n'est pas valide.";
        }
        if ($errors) {
            return $this->validationError($errors);
        }

        $client = new Client($name, $email, $phone);
        $this->em->persist($client);
        $this->em->flush();

        return new JsonResponse($client->toArray(), JsonResponse::HTTP_CREATED);
    }
}

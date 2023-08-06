<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Utils\Pagination;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[OA\Tag(name: 'Customer')]
#[Route('/api/customer')]
class CustomerController extends AbstractController
{

    #[OA\Get(
        path: '/api/customer/users',
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                example: 5
            )
        ]
    )]
    #[Route('/users', name: 'customer_users', methods: ['GET'])]
    public function showListUsers(
        Request $request,
        UserRepository $userRepository,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse
    {

        $countUsers = $userRepository->count([
            'customer' => $this->getUser(),
        ]);

        $valueParamsToPagination = Pagination::getValueParamsToPagination($request, $countUsers, $urlGenerator);

        $users = $userRepository->findBy([
            'customer' => $this->getUser()
        ], limit: $valueParamsToPagination["limit"], offset: $valueParamsToPagination["offset"]);
        $context = SerializationContext::create()
            ->setGroups(['getUsersByCustomer'])
            ->setSerializeNull(true);

        $jsonProductsList = $serializer->serialize([
            "total_items_page" => count($users),
            ...array_filter(
                $valueParamsToPagination,
                function ($item, $key) {
                    return ($key !== 'limit' && $key !== 'offset') ? [$key => $item] : [];
                },
                ARRAY_FILTER_USE_BOTH
            ),
            'data' => $users,
        ], 'json', $context);

        return new JsonResponse(
            $jsonProductsList,
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[OA\Get(
        path: '/api/customer/users/{id}',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                schema: new OA\Schema(type: 'string'),
                example: "1ee33165-b02c-6080-b323-a7cf585beb7d"
            )
        ]
    )]
    #[Route('/users/{id}', name: 'customer_details_user', methods: ['GET'])]
    public function showDetailsUser(
        ?User $user,
        SerializerInterface $serializer
    ): JsonResponse
    {
        if (!$user || $user->getCustomer() !== $this->getUser()) {
            throw $this->createNotFoundException("Page not found");
        }

        $context = SerializationContext::create()
            ->setGroups(['getUsersByCustomer']);

        $jsonUser = $serializer->serialize($user, 'json', $context);

        return new JsonResponse(
            $jsonUser,
            Response::HTTP_OK,
            [],
            true
        );
    }
}

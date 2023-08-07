<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Utils\Pagination;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        $this->denyAccessUnlessGranted('USERS_VIEW');

        $countUsers = $userRepository->count([
            'customer' => $this->getUser(),
        ]);

        $valueParamsToPagination = Pagination::getValueParamsToPagination($request, $countUsers, $urlGenerator);

        $users = $userRepository->findBy([
            'customer' => $this->getUser()
        ], limit: $valueParamsToPagination['limit'], offset: $valueParamsToPagination['offset']);
        $context = SerializationContext::create()
            ->setGroups(['getUsersByCustomer'])
            ->setSerializeNull(true);

        $jsonProductsList = $serializer->serialize([
            'total_items_page' => count($users),
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
        if (!$user) {
            throw $this->createNotFoundException('Page not found');
        }

        $this->denyAccessUnlessGranted('USER_VIEW', $user);

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

    #[OA\Post(
        path: '/api/customer/users',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: [
                    "firstname",
                    "lastname",
                    "email",
                    "password"
                ],
                properties: [
                    new OA\Property(
                        property: "firstname",
                        type: "string",
                        example: "Jean"
                    ),
                    new OA\Property(
                        property: "lastname",
                        type: "string",
                        example: "Dupont"
                    ),
                    new OA\Property(
                        property: "email",
                        type: "string",
                        example: "jean.dupont@oc-p7.fr"
                    ),
                    new OA\Property(
                        property: "password",
                        type: "string",
                        format: "password",
                        maxLength: 20,
                        minLength: 8,
                        example: "Mot2p@ass3"
                    ),
                    new OA\Property(
                        property: "phone",
                        type: "string",
                        example: "0680124121"
                    ),
                    new OA\Property(
                        property: "address",
                        type: "string",
                        example: "10 rue de la paix, 75001 Paris"
                    )
                ],
                type: "object"
            )
        ),
    )]
    #[Route('/users', name: 'customer_add_user', methods: ['POST'])]
    public function addUser(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $this->denyAccessUnlessGranted('USER_CREATE');

        $content = $serializer->serialize(
            [
                'class_name' => 'User',
                ...$request->toArray()
            ], 'json'
        );

        /**
         * @var User $user
         */
        $user = $serializer->deserialize(
            $content,
            User::class,
            'json'
        );

        $errors = $validator->validate($user);

        if ($errors->count()) {
            $errorsArray = [];

            foreach ($errors as $key => $error) {
                $errorsArray[$key] = $error->getMessage();
            }

            return new JsonResponse(
                $errorsArray,
                Response::HTTP_BAD_REQUEST
            );
        }

        $user
            ->setCustomer($this->getUser())
            ->setPassword($passwordHasher->hashPassword(
                $user,
                $user->getPassword()
            ));

        $em->persist($user);
        $em->flush();

        $context = SerializationContext::create()
            ->setGroups('getUsersByCustomer')
            ->setSerializeNull(true);

        $jsonUser = $serializer->serialize($user, 'json', $context);

        $location = $this->generateUrl(
            'customer_details_user',
            [
                'id' => $user->getId()
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse(
            $jsonUser,
            Response::HTTP_CREATED,
            ['Location' => $location],
            json: true
        );
    }

    #[OA\Delete(
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
    #[Route('/users/{id}', name: 'customer_delete_user', methods: ['DELETE'])]
    public function deleteUser(
        ?User $user,
        EntityManagerInterface $em
    ): JsonResponse
    {
        if (!$user) {
            throw $this->createNotFoundException('Page not found');
        }

        $this->denyAccessUnlessGranted('USER_DELETE', $user);

        $em->remove($user);
        $em->flush();

        return new JsonResponse(
            null,
            Response::HTTP_NO_CONTENT
        );
    }
}

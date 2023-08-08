<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Utils\Pagination;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[OA\Tag(name: 'Customer')]
#[Route('/api/customer')]
class CustomerController extends AbstractController
{

    /**
     * @throws InvalidArgumentException
     */
    #[OA\Get(
        path: '/api/customer/users',
        description: '<b><u>Récupération de la liste des utilisateurs</u>:</b><br><br>
            Une pagination est mise en place. À l\'aide de query params, il est possible de saisir le numéro 
            d\'une page et la limite de résultats par page.<br>
            Cette liste retournera :
            <ul>
                <li>Nombre total d\'utilisateurs dans la collection du demandeur</li>
                <li>Nombre total d\'utilisateurs dans la page</li>
                <li>Nombre total de pages</li>
                <li>La page précédente (si elle existe)</li>
                <li>La page suivante (si elle existe)</li>
                <li>La liste des utilisateurs avec leur détail</li>
            </ul><br>
            <b>NOTE: Seuls les utilisateurs présents dans la collection du demandeur (customer) seront retournés.</b>',
        summary: 'Récupération de la liste des utilisateurs présents dans la collection du demandeur (customer).',
        parameters: [
            new OA\Parameter(
                name: 'page',
                description: 'Paramètre concernant le numéro de page (optionnel)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Paramètre concernant le nombre d\'utilisateur par page (optionnel)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                example: 5
            )
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Retourne le nombre d\'utilisateurs dans la page, le nombre d\'utilisateurs dans la base de données, 
                le nombre de page, la page précédente, la page suivante et la liste des utilisateurs liées au demandeur.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'total_items_page',
                            type: 'integer',
                            example: 5
                        ),
                        new OA\Property(
                            property: 'total_items_collection',
                            type: 'integer',
                            example: 15
                        ),
                        new OA\Property(
                            property: 'total_pages',
                            type: 'integer',
                            example: 3
                        ),
                        new OA\Property(
                            property: 'previous_page',
                            type: 'string',
                            example: "https://localhost:3000/api/products?limit=5&page=1"
                        ),
                        new OA\Property(
                            property: 'next_page',
                            type: 'string',
                            example: "https://localhost:3000/api/products?limit=5&page=3"
                        ),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                ref: new Model(
                                    type: User::class,
                                    groups: ['getUsersByCustomer']
                                )
                            )
                        )
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: 'Une exception est levée, l\'utilisateur doit être connecté pour accéder aux informations demandées.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'status',
                            type: 'int',
                            example: 401
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'JWT Token not found'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_FORBIDDEN,
                description: 'Une exception est levée, l\'utilisateur n\'a pas les droits pour accéder aux informations demandées.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'status',
                            type: 'int',
                            example: 403
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Access Denied.'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Une exception est levée, la page n\'existe pas.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'status',
                            type: 'int',
                            example: 404
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Page not found.'
                        )
                    ]
                )
            ),
        ]
    )]
    #[Route('/users', name: 'customer_users', methods: ['GET'])]
    public function showListUsers(
        Request $request,
        UserRepository $userRepository,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        $this->denyAccessUnlessGranted('USERS_VIEW');

        $countUsers = $userRepository->count([
            'customer' => $this->getUser(),
        ]);

        $valueParamsToPagination = Pagination::getValueParamsToPagination($request, $countUsers, $urlGenerator);

        $idCache = 'getUsers-' .
            ($valueParamsToPagination['offset'] + $valueParamsToPagination['limit']) / $valueParamsToPagination['limit'] .
            '-' .
            $valueParamsToPagination['limit'];

        $jsonUsersList = $cachePool->get(
            $idCache,
            function (ItemInterface $item) use ($userRepository, $valueParamsToPagination, $serializer) {
                $item->tag('usersCache');

                $users = $userRepository->findBy(
                    [
                    'customer' => $this->getUser()
                    ],
                    limit: $valueParamsToPagination['limit'],
                    offset: $valueParamsToPagination['offset']
                );

                $context = SerializationContext::create()
                    ->setGroups(['getUsersByCustomer'])
                    ->setSerializeNull(true);

                return $serializer->serialize([
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
            }
        );

        return new JsonResponse(
            $jsonUsersList,
            Response::HTTP_OK,
            [],
            true
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    #[OA\Get(
        path: '/api/customer/users/{id}',
        description: '<b><u>Récupération du détail d\'un utilisateur</u>:</b><br><br>
            Cela aura pour effet de retourner un objet JSON contenant la clé et valeur de ses propriétés.<br><br>
            <b>NOTE: Le détail est retourné seulement si l\'utilisateur est dans la collection du demandeur (customer).</b>',
        summary: 'Récupération du détail d\'un utilisateur à l\'aide de son identifiant, présent dans la collection
         du demandeur (customer).',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Identifiant Uuid de l\'utilisateur que l\'on souhaite obtenir le détail des informations.',
                in: 'path',
                schema: new OA\Schema(type: 'string'),
                example: '1ee33165-b02c-6080-b323-a7cf585beb7d'
            )
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Retourne le détail de l\'utilisateur sous forme d\'objet JSON.',
                content: new OA\JsonContent(
                    ref: new Model(
                        type: User::class,
                        groups: ['getUsersByCustomer']
                    ),
                    type: 'object'
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: 'Une exception est levée, l\'utilisateur doit être connecté pour accéder aux informations demandées.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'status',
                            type: 'int',
                            example: 401
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'JWT Token not found'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_FORBIDDEN,
                description: 'Une exception est levée, l\'utilisateur n\'a pas les droits pour accéder aux informations demandées.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'status',
                            type: 'int',
                            example: 403
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Access Denied.'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Une exception est levée, la page n\'existe pas.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'status',
                            type: 'int',
                            example: 404
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Page not found.'
                        )
                    ]
                )
            ),
        ]
    )]
    #[Route('/users/{id}', name: 'customer_details_user', methods: ['GET'])]
    public function showDetailsUser(
        ?User $user,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        if (!$user) {
            throw $this->createNotFoundException('Page not found');
        }

        $this->denyAccessUnlessGranted('USER_VIEW', $user);

        $idCache = 'getUser-' . $user->getId();

        $jsonUser = $cachePool->get(
            $idCache,
            function (ItemInterface $item) use ($user, $serializer) {
                $item->tag('user-details-' . $user->getId() . '-cache');

                $context = SerializationContext::create()
                    ->setGroups(['getUsersByCustomer'])
                    ->setSerializeNull(true);

                return $serializer->serialize($user, 'json', $context);

            }
        );

        return new JsonResponse(
            $jsonUser,
            Response::HTTP_OK,
            [],
            true
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    #[OA\Post(
        path: '/api/customer/users',
        description: '<b><u>Ajout d\'un nouvel utilisateur</u>:</b><br><br>
            Cela aura pour effet d\'ajouter un nouvel utilisateur dans la collection du demandeur (customer).<br>
            L\'adresse email doit être unique, une exception sera levée le cas échéant. Le numéro de téléphone et l\'adresse
            ne sont pas requis.<br><br>
            <b>NOTE: Il n\'est pas demandé de préciser l\'identifiant du demandeur (customer), celui-ci sera
            récupéré automatique via les propriétés de la requête.</b>',
        summary: 'Ajout d\'un nouvel utilisateur dans la collection du demandeur (customer).',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: [
                    'firstname',
                    'lastname',
                    'email',
                    'password'
                ],
                properties: [
                    new OA\Property(
                        property: 'firstname',
                        type: 'string',
                        example: 'Jean'
                    ),
                    new OA\Property(
                        property: 'lastname',
                        type: 'string',
                        example: 'Dupont'
                    ),
                    new OA\Property(
                        property: 'email',
                        type: 'string',
                        example: 'jean.dupont@oc-p7.fr'
                    ),
                    new OA\Property(
                        property: 'password',
                        type: 'string',
                        format: 'password',
                        maxLength: 20,
                        minLength: 8,
                        example: 'Mot2p@ass3'
                    ),
                    new OA\Property(
                        property: 'phone',
                        type: 'string',
                        example: '0680124121'
                    ),
                    new OA\Property(
                        property: 'address',
                        type: 'string',
                        example: '10 rue de la paix, 75001 Paris'
                    )
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Retourne le détail du nouvel utilisateur.',
                headers: [
                    new OA\Header(
                        header: 'Location',
                        description: 'L\URL pointant vers le détails du nouvel utilisateur.',
                        schema: new OA\Schema(
                            type: 'string',
                            example: 'https://localhost:3000/api/customer/users/1ee35342-a565-6ad0-8c98-29ef8289788e'
                        )
                    )
                ],
                content: new OA\JsonContent(
                    ref: new Model(
                        type: User::class,
                        groups: ['getUsersByCustomer']
                    ),
                    type: 'object'
                )
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: 'Une exception est levée, une ou plusieurs erreurs de validation sur l\'envoi 
                des données a été détecté.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'email',
                            type: 'string',
                            example: 'L\'adresse email existe déjà, veuillez en saisir une nouvelle.'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: 'Une exception est levée, l\'utilisateur doit être connecté pour accéder aux informations demandées.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'status',
                            type: 'int',
                            example: 401
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'JWT Token not found'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_FORBIDDEN,
                description: 'Une exception est levée, l\'utilisateur n\'a pas les droits pour accéder aux informations demandées.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'status',
                            type: 'int',
                            example: 403
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Access Denied.'
                        )
                    ]
                )
            )
        ]
    )]
    #[Route('/users', name: 'customer_add_user', methods: ['POST'])]
    public function addUser(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cachePool
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

        $cachePool->invalidateTags(['usersCache']);

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

    /**
     * @throws InvalidArgumentException
     */
    #[OA\Delete(
        path: '/api/customer/users/{id}',
        description: '<b><u>Suppression d\'un utilisateur</u>:</b><br><br>
            Cela aura pour effet de supprimer un utilisateur de la collection du demandeur (customer).<br><br>
            <b>NOTE: Seuls les utilisateurs présents dans la collection du demandeur (customer) peuvent être supprimés.</b>',
        summary: 'Suppression d\'un utilisateur de la collection du demandeur (customer).',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Identifiant Uuid de l\'utilisateur que l\'on souhaite supprimer définitivement.',
                in: 'path',
                schema: new OA\Schema(type: 'string'),
                example: '1ee33165-b02c-6080-b323-a7cf585beb7d'
            )
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Suppression de l\'utilisateur avec succès. Renvoie uniquement un status code 204 
                sans contenu.',
                content: null
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: 'Une exception est levée, l\'utilisateur doit être connecté pour accéder aux informations demandées.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'status',
                            type: 'int',
                            example: 401
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'JWT Token not found'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_FORBIDDEN,
                description: 'Une exception est levée, l\'utilisateur n\'a pas les droits pour accéder aux informations demandées.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'status',
                            type: 'int',
                            example: 403
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Access Denied.'
                        )
                    ]
                )
            )
        ]
    )]
    #[Route('/users/{id}', name: 'customer_delete_user', methods: ['DELETE'])]
    public function deleteUser(
        ?User $user,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        if (!$user) {
            throw $this->createNotFoundException('Page not found');
        }

        $this->denyAccessUnlessGranted('USER_DELETE', $user);

        $em->remove($user);
        $em->flush();

        $cachePool->invalidateTags(['usersCache']);
        $cachePool->invalidateTags(['getUser-' . $user->getId()]);

        return new JsonResponse(
            null,
            Response::HTTP_NO_CONTENT
        );
    }
}

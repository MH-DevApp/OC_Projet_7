<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Utils\Pagination;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[OA\Tag(name: 'Products')]
#[Route('/api/products')]
class ProductController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[OA\Get(
        path: '/api/products',
        description: '<b><u>Récupération de la liste des produits</u>:</b><br><br>
            Une pagination est mise en place. À l\'aide de query params, il est possible de saisir le numéro 
            d\'une page et la limite de résultats par page.<br>
            Cette liste retournera :
            <ul>
                <li>Nombre total de produits dans la base de données</li>
                <li>Nombre total de produits dans la page</li>
                <li>Nombre total de pages</li>
                <li>La page précédente (si elle existe)</li>
                <li>La page suivante (si elle existe)</li>
                <li>La liste des produits avec leur détail</li>
            </ul><br>',
        summary: 'Récupération de la liste des produits.',
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
                description: 'Paramètre concernant le nombre de produits par page (optionnel)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                example: 5
            )
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Retourne le nombre de produits dans la page, le nombre de produits dans la base de données, 
                le nombre de page, la page précédente, la page suivante et la liste des produits.',
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
                                    type: Product::class
                                ),
                                type: 'object'
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
    #[Route('', name: 'products', methods: ['GET'])]
    public function getProductsList(
        Request $request,
        ProductRepository $productRepository,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        $countProducts = $productRepository->count([]);

        $valueParamsToPagination = Pagination::getValueParamsToPagination($request, $countProducts, $urlGenerator);

        $idCache = 'getProducts-' .
            ($valueParamsToPagination['offset'] + $valueParamsToPagination['limit']) / $valueParamsToPagination['limit'] .
            '-' .
            $valueParamsToPagination['limit'];

        $jsonProductsList = $cachePool->get(
            $idCache,
            function (ItemInterface $item) use ($productRepository, $valueParamsToPagination, $serializer) {
                $item->tag('productsCache');

                $products = $productRepository->findBy(
                    [],
                    limit: $valueParamsToPagination['limit'],
                    offset: $valueParamsToPagination['offset']
                );

                $context = SerializationContext::create()
                    ->setSerializeNull(true);

                return $serializer->serialize([
                    "total_items_page" => count($products),
                    ...array_filter(
                        $valueParamsToPagination,
                        function ($item, $key) {
                            return ($key !== 'limit' && $key !== 'offset') ? [$key => $item] : [];
                        },
                        ARRAY_FILTER_USE_BOTH
                    ),
                    'data' => $products,
                ],
                    'json',
                    $context
                );
            }
        );

        return new JsonResponse(
            $jsonProductsList,
            Response::HTTP_OK,
            [],
            true
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    #[OA\Get(
        path: '/api/products/{id}',
        description: '<b><u>Récupération du détail d\'un produit</u>:</b><br><br>
            Cela aura pour effet de retourner un objet JSON contenant la clé et valeur de ses propriétés.<br><br>',
        summary: 'Récupération du détail d\'un produit à l\'aide de son identifiant.',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Identifiant Uuid du produit que l\'on souhaite obtenir le détail des informations.',
                in: 'path',
                schema: new OA\Schema(type: 'string'),
                example: '1ee33165-a193-6d0c-be5b-a7cf585beb7d'
            )
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Retourne le détail du produit sous forme d\'objet JSON.',
                content: new OA\JsonContent(
                    ref: new Model(
                        type: Product::class
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
    #[Route('/{id}', name: 'products_details', methods: ['GET'])]
    public function getProductsDetails(
        ?Product $product,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        if (!$product) {
            throw $this->createNotFoundException('Page not found');
        }

        $idCache = 'getProduct-'.$product->getId();

        $jsonProduct = $cachePool->get(
            $idCache,
            function (ItemInterface $item) use ($product, $serializer) {
                $item->tag('product-details-' . $product->getId() . '-cache');

                $context = SerializationContext::create()
                    ->setSerializeNull(true);

                return $serializer->serialize($product, 'json', $context);

            }
        );

        return new JsonResponse(
            $jsonProduct,
            Response::HTTP_OK,
            [],
            true
        );
    }
}

<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
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

#[OA\Tag(name: 'Products')]
#[Route('/api/products')]
class ProductController extends AbstractController
{
    #[OA\Get(
        path: '/api/products',
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
    #[Route('', name: 'products', methods: ['GET'])]
    public function getProductsList(
        Request $request,
        ProductRepository $productRepository,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse
    {
        $countProducts = $productRepository->count([]);

        $valueParamsToPagination = Pagination::getValueParamsToPagination($request, $countProducts, $urlGenerator);

        $products = $productRepository->findBy(
            [],
            limit: $valueParamsToPagination["limit"],
            offset: $valueParamsToPagination["offset"]
        );

        $context = SerializationContext::create()
            ->setSerializeNull(true);

        $jsonProductsList = $serializer->serialize([
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

        return new JsonResponse(
            $jsonProductsList,
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[OA\Get(
        path: '/api/products/{id}',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                schema: new OA\Schema(type: 'string'),
                example: "1ee33165-a193-6d0c-be5b-a7cf585beb7d"
            )
        ]
    )]
    #[Route('/{id}', name: 'products_details', methods: ['GET'])]
    public function getProductsDetails(
        ?Product $product,
        SerializerInterface $serializer
    ): JsonResponse
    {
        if (!$product) {
            throw $this->createNotFoundException();
        }

        $jsonProduct = $serializer->serialize($product, 'json');

        return new JsonResponse(
            $jsonProduct,
            Response::HTTP_OK,
            [],
            true
        );
    }
}

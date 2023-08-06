<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use JMS\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
        SerializerInterface $serializer
    ): JsonResponse
    {
        $countProducts = $productRepository->count([]);
        $page = $request->query->getInt('page');
        $limit = $request->query->getInt('limit');

        if (!$page) {
            $page = 1;
            if (!$limit) {
                $limit = $countProducts;
            }
        } else if (!$limit) {
            $limit = 5;
        }

        $offset = ($page - 1) * $limit;

        $products = $productRepository->findBy([], limit: $limit, offset: $offset);
        $jsonProductsList = $serializer->serialize($products, 'json');

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

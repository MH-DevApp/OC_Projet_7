<?php

namespace App\Controller;

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
}

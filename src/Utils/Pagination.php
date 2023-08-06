<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class Pagination {

    /**
     * Get value params to pagination
     *
     * @param Request $request
     * @param int $countItems
     * @param UrlGeneratorInterface $urlGenerator
     *
     * @return array<string, int|string|null>
     */
    public static function getValueParamsToPagination(
        Request $request,
        int $countItems,
        UrlGeneratorInterface $urlGenerator
    ): array
    {
        $urlPrevious = null;
        $urlNext = null;
        $page = $request->query->getInt('page');
        $limit = $request->query->getInt('limit');

        if ($page < 0 || $limit < 0) {
            $param = $page < 0 && $limit < 0 ?
                'Page and limit' :
                ($page < 0 ?
                    'Page' :
                    'Limit');

            throw new BadRequestException(
                sprintf(
                    "%s query parameters must be a positive integer"
                    , $param
                )
            );
        }

        $paramsToUrlGenerate = [
            'route' => $request->attributes->get('_route'),
            'query' => [],
        ];

        if (!$page) {
            $page = 1;
            if (!$limit) {
                $limit = $countItems;
            } else {
                $paramsToUrlGenerate['query']['limit'] = $limit;
            }
        } else if (!$limit) {
            $limit = 5;
        } else {
            $paramsToUrlGenerate['query']['limit'] = $limit;
        }

        $offset = (int) (($page - 1) * $limit);
        $totalPages = (int) ceil($countItems / $limit);

        if ($page > $totalPages) {
            throw new NotFoundHttpException("Page not found");
        }

        if ($countItems > $page * $limit) {
            $paramsToUrlGenerate['query']['page'] = $page + 1;

            $urlNext = $urlGenerator->generate(
                $paramsToUrlGenerate["route"],
                [
                    ...$paramsToUrlGenerate["query"]
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        if (($page * $limit) > $limit) {
            $paramsToUrlGenerate['query']['page'] = $page - 1;
            $urlPrevious = $urlGenerator->generate(
                $paramsToUrlGenerate["route"],
                [
                    ...$paramsToUrlGenerate["query"]
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        return [
            'total_items_collection' => $countItems,
            'total_pages' => $totalPages,
            'previous_page' => $urlPrevious,
            'next_page' => $urlNext,
            'limit' => $limit,
            'offset' => $offset
        ];
    }
}

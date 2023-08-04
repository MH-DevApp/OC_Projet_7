<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $dataProducts = json_decode(
            file_get_contents(__DIR__ . '/data/products.json'),
            true
        );

        foreach ($dataProducts as $dataProduct) {
            $product = new Product();

            $product
                ->setBrand($dataProduct["brand"])
                ->setModel($dataProduct["model"])
                ->setDescription($dataProduct["description"])
                ->setPrice($dataProduct["price"])
            ;

            $manager->persist($product);
        }

        $manager->flush();
    }
}

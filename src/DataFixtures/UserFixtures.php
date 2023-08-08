<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $dataUsers = json_decode(
            file_get_contents(__DIR__ . '/data/users.json'),
            true
        );

        $customers = $manager->getRepository(Customer::class)->findAll();

        foreach ($dataUsers as $dataUser) {
            $user = new User();

            $password = $this->passwordHasher->hashPassword(
                $user,
                "123456"
            );

            $user
                ->setFirstname($dataUser["firstname"])
                ->setLastname($dataUser["name"])
                ->setEmail($dataUser["email"])
                ->setPassword($password)
                ->setPhone($dataUser["phone"])
                ->setAddress($dataUser["address"])
                ->setCustomer($customers[array_rand($customers)])
            ;

            $manager->persist($user);

        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CustomerFixtures::class
        ];
    }
}

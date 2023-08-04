<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CustomerFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $dataCustomers = json_decode(
            file_get_contents(__DIR__ . '/data/customers.json'),
            true
        );

        foreach ($dataCustomers as $dataCustomer) {
            $customer = new Customer();
            $password = $this->passwordHasher->hashPassword(
                $customer,
                "123456"
            );
            $customer
                ->setFirstname($dataCustomer["firstname"])
                ->setLastname($dataCustomer["name"])
                ->setEmail($dataCustomer["email"])
                ->setPassword($password)
                ->setPhone($dataCustomer["phone"])
                ->setAddress($dataCustomer["address"])
                ->setCompanyName($dataCustomer["companyName"])
                ->setCompanySiret($dataCustomer["companySiret"])
            ;

            $manager->persist($customer);
        }


        $manager->flush();
    }
}

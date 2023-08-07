<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\UuidV6;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Hateoas\Relation(
 *  "details",
 *  href = @Hateoas\Route(
 *      "customer_details_user",
 *      parameters = {"id" = "expr(object.getId())"},
 *      absolute = true
 *  ),
 *  exclusion = @Hateoas\Exclusion(
 *      groups = {"getUsersByCustomer"},
 *      excludeIf = "expr(object.getCustomer() === null || not is_granted('USERS_VIEW', object))"
 *  )
 * )
 *
 * @Hateoas\Relation(
 *  "delete",
 *  href = @Hateoas\Route(
 *      "customer_delete_user",
 *      parameters = {"id" = "expr(object.getId())"},
 *      absolute = true
 *  ),
 *  exclusion = @Hateoas\Exclusion(
 *      groups = {"getUsersByCustomer"},
 *      excludeIf = "expr(object.getCustomer() === null || not is_granted('USERS_VIEW', object))"
 *  )
 * )
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'class_name', type: 'string')]
#[ORM\DiscriminatorMap(['User' => User::class, 'Customer' => Customer::class])]
#[UniqueEntity('email', message: 'Cette adresse email existe déjà, veuillez en saisir une nouvelle.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['getUsersByCustomer'])]
    private ?UuidV6 $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['getUsersByCustomer'])]
    #[Assert\NotBlank(message: 'Une adresse email est obligatoire.')]
    #[Assert\Email(message: 'L\'adresse email n\'est pas valide.')]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire.')]
    #[Assert\Length(
        min: 8,
        max: 20,
        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le mot de passe doit contenir au plus {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z])(?=.*[@#\/\+\-\*\.])[a-zA-Z0-9@#\/\+\-\*]+$/',
        message: 'Le mot de passe doit contenir au moins une minuscule, une majuscule, un chiffre et un caractère spécial (@#/*-+).'
    )]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getUsersByCustomer'])]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le prénom ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getUsersByCustomer'])]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le nom ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $lastname = null;

    #[ORM\Column(length: 30, nullable: true)]
    #[Groups(['getUsersByCustomer'])]
    #[Assert\Length(
        max: 30,
        maxMessage: 'Le numéro de téléphone ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['getUsersByCustomer'])]
    #[Assert\Length(
        max: 255,
        maxMessage: 'L\'adresse ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $address = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[Groups(['getUsersByCustomer'])]
    private ?Customer $customer = null;

    public function getId(): ?UuidV6
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {

    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }
}

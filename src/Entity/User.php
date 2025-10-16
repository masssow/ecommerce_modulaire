<?php

namespace App\Entity;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\State\UserRegistrationProcessor;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use Doctrine\Common\Collections\ArrayCollection;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;



#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ApiResource(
    operations: [
        // GET /api/users/{id}
        new Get(
            security: "is_granted('ROLE_USER') and object == user",
            normalizationContext: ['groups' => ['user:read']]),
            // GET /api/users (admin uniquement)
        new GetCollection(
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['admin:user:list']]
        ),

        // POST /api/users (inscription)
        new Post(
            processor: UserRegistrationProcessor::class,
            denormalizationContext: ['groups' => ['user:register']],
            normalizationContext:   ['groups' => ['user:read']],
            security: "is_anonymous()"
        ),

        // PATCH /api/users/{id} (modification profil)
        new Patch(
            security: "is_granted('ROLE_USER') and object == user",
            denormalizationContext: ['groups' => ['user:update']],
            normalizationContext:   ['groups' => ['user:read']]
        ),
    ],
    filters: ['user.search', 'user.order']
)]
#[ApiFilter(
    SearchFilter::class,
    properties: ['email' => 'exact', 'firstName' => 'partial', 'lastName' => 'partial']
)]
#[ApiFilter(OrderFilter::class, properties: ['createdAt'])]

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'admin:user:list'])]
    private ?int $id = null;

    /**
     * @Assert\NotBlank(message="L'adresse mail ne peut pas être vide")
     */
    #[ORM\Column(length: 180)]
    #[Groups(['user:read', 'user:register', 'user:update', 'admin:user:list'])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];
    

    /**
     * @var string The hashed password
     */
    /**
     * @Assert\NotBlank(message="Le mot de passe ne peut pas être vide")
     */
    #[ORM\Column]
    #[Groups(['user:register'])]
    private ?string $password = null;

    /**
     * @Assert\NotBlank(message="Le nom ne peut pas être vide")
     */
    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:register', 'user:update'])]
    private ?string $firstname = null;

    /**
     * @Assert\NotBlank(message="Le prénom ne peut pas être vide")
     */
    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:register', 'user:update'])]
    private ?string $lastname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    /**
     * @var Collection<int, Adresse>
     */
    #[ORM\OneToMany(targetEntity: Adresse::class, mappedBy: 'user')]
    private Collection $adresses;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Customer $customer = null;

    /**
     * @var Collection<int, UserPaymentMethod>
     */
    #[ORM\OneToMany(targetEntity: UserPaymentMethod::class, mappedBy: 'user')]
    private Collection $userPaymentMethods;

    /**
     * @var Collection<int, Favorite>
     */
    #[ORM\OneToMany(targetEntity: Favorite::class, mappedBy: 'user')]
    private Collection $favorites;

    public function __construct()
    {
        $this->adresses = new ArrayCollection();
        $this->userPaymentMethods = new ArrayCollection();
        $this->favorites = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): ?string
    {
        $first = trim((string) $this->firstname);
        $last  = trim((string) $this->lastname);

        $full = trim($first . ' ' . $last);
        return $full !== '' ? $full : null;
    }

    public function setFullName(?string $fullName): self
    {
        $fullName = trim((string) $fullName);

        if ($fullName === '') {
            $this->firstname = null;
            $this->lastname  = null;
            return $this;
        }

        $parts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $last  = array_pop($parts) ?? '';
        $first = implode(' ', $parts);

        $this->firstname = $first !== '' ? $first : null;
        $this->lastname  = $last  !== '' ? $last  : null;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
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
        return (string) $this->email;
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

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    /**
     * @return Collection<int, Adresse>
     */
    public function getAdresses(): Collection
    {
        return $this->adresses;
    }

    public function addAdress(Adresse $adress): static
    {
        if (!$this->adresses->contains($adress)) {
            $this->adresses->add($adress);
            $adress->setUser($this);
        }

        return $this;
    }

    public function removeAdress(Adresse $adress): static
    {
        if ($this->adresses->removeElement($adress)) {
            // set the owning side to null (unless already changed)
            if ($adress->getUser() === $this) {
                $adress->setUser(null);
            }
        }

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        // unset the owning side of the relation if necessary
        if ($customer === null && $this->customer !== null) {
            $this->customer->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($customer !== null && $customer->getUser() !== $this) {
            $customer->setUser($this);
        }

        $this->customer = $customer;

        return $this;
    }

    /**
     * @return Collection<int, UserPaymentMethod>
     */
    public function getUserPaymentMethods(): Collection
    {
        return $this->userPaymentMethods;
    }

    public function addUserPaymentMethod(UserPaymentMethod $userPaymentMethod): static
    {
        if (!$this->userPaymentMethods->contains($userPaymentMethod)) {
            $this->userPaymentMethods->add($userPaymentMethod);
            $userPaymentMethod->setUser($this);
        }

        return $this;
    }

    public function removeUserPaymentMethod(UserPaymentMethod $userPaymentMethod): static
    {
        if ($this->userPaymentMethods->removeElement($userPaymentMethod)) {
            // set the owning side to null (unless already changed)
            if ($userPaymentMethod->getUser() === $this) {
                $userPaymentMethod->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Favorite>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Favorite $favorite): static
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
            $favorite->setUser($this);
        }

        return $this;
    }

    public function removeFavorite(Favorite $favorite): static
    {
        if ($this->favorites->removeElement($favorite)) {
            // set the owning side to null (unless already changed)
            if ($favorite->getUser() === $this) {
                $favorite->setUser(null);
            }
        }

        return $this;
    }
}

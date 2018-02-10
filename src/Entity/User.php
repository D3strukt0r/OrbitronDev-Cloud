<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User // implements \Serializable, UserInterface
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var int
     * @ORM\Column(type="integer", unique=true)
     */
    protected $remote_id;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $username;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $email;

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    protected $token_data;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getRemoteId(): int
    {
        return $this->remote_id;
    }

    /**
     * @param int $remote_id
     *
     * @return $this
     */
    public function setRemoteId(int $remote_id): self
    {
        $this->remote_id = $remote_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return $this
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return $this
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getTokenData(): string
    {
        return $this->token_data;
    }

    /**
     * @param string $token_data
     *
     * @return $this
     */
    public function setTokenData(string $token_data): self
    {
        $this->token_data = $token_data;

        return $this;
    }

    public function getPassword()
    {
    }

    /**
     * @return string|null
     */
    public function getSalt(): ?string
    {
        // you *may* need a real salt depending on your encoder
        // see section on salt below
        return null;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials()
    {
    }

    /**
     * @see \Serializable::serialize()
     *
     * @return string
     */
    public function serialize(): string
    {
        return serialize([
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt,
        ]);
    }

    /**
     * @see \Serializable::unserialize()
     *
     * @param $serialized
     */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt
            ) = unserialize($serialized);
    }
}

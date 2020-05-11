<?php

namespace App\Document;

use Symfony\Component\Security\Core\User\UserInterface;

class Autor implements UserInterface
{
    private $googleid;

    private $roles;

    private $oauth;

    private $token;

    public function getGoogleid(): ?string
    {
        return $this->googleid;
    }

    public function setGoogleid(string $googleid): self
    {
        $this->googleid = $googleid;

        return $this;
    }

    public function getRoles()
    {
        $roles = ['ROLE_USER'];
        foreach ($this->roles as $role) {
            $roles[] = $role;
        }

        return $roles;
    }

    public function addRole(string $role)
    {
        $this->roles[] = $role;
    }

    public function getUsername()
    {
        return $this->googleid;    
    }

    public function getPassword()
    {
        return null;
    }

    public function getSalt()
    {
        return null;
    }

    public function eraseCredentials()
    {
        return null;
    }

    public function getOauth(): bool
    {
        return $this->oauth;
    }

    public function setOauth(bool $oauth): self
    {
        $this->oauth = $oauth;
        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }
}

<?php
/*
php app/console doctrine:generate:entity --entity="MTIUserBackOfficeBundle:Profil" --fields="firstname:string(255) lastname:string(255) email:string(255) apikey:string(255)"
*/

namespace MTI\UserBackOfficeBundle\Entity;
use Symfony\Component\Security\Core\User\UserInterface;

class Profile implements UserInterface
{
	protected $id;

    protected $salt;

    protected $password;

    protected $username;

    protected $lastname;

    protected $email;

    protected $publicapikey;

    protected $secretapikey;

    protected $subscribe;

    protected $roles= array();

    public function eraseCredentials()
    {
    }

    public function getSubscribe()
    {
        return $this->subscribe;
    }

    public function setSubscribe($subscribe)
    {
        $this->subscribe = $subscribe;

        return $this;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function setRoles($roles)
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

 

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     * @return Profile
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string 
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Profile
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set publicapikey
     *
     * @param string $publicapikey
     * @return Profile
     */
    public function setPublicapikey($publicapikey)
    {
        $this->publicapikey = $publicapikey;

        return $this;
    }

    /**
     * Get publicapikey
     *
     * @return string 
     */
    public function getPublicapikey()
    {
        return $this->publicapikey;
    }

    /**
     * Set secretapikey
     *
     * @param string $secretapikey
     * @return Profile
     */
    public function setSecretapikey($secretapikey)
    {
        $this->secretapikey = $secretapikey;

        return $this;
    }

    /**
     * Get secretapikey
     *
     * @return string 
     */
    public function getSecretapikey()
    {
        return $this->secretapikey;
    }
}

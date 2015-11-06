<?php

namespace MTI\UserBackOfficeBundle\Entity;

class Cache
{
    protected $id;

    protected $request;

    protected $response;

    protected $created;
    /**
     * @var integer
     */


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }


    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set request
     *
     * @param string $request
     * @return Cache
     */
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get Request
     *
     * @return string 
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set response
     *
     * @param string $response
     * @return Cache
     */
    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Get Response
     *
     * @return string 
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedValue()
    {
         $this->created = new \DateTime();
    }
}

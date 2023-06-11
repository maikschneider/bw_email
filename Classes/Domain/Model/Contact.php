<?php

namespace Blueways\BwEmail\Domain\Model;

class Contact
{
    public $name;

    public $prename;

    public $lastname;

    public $email;

    /**
     * Contact constructor.
     *
     * @param $email
     */
    public function __construct($email)
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getPrename()
    {
        return $this->prename;
    }

    /**
     * @param mixed $prename
     */
    public function setPrename($prename): void
    {
        $this->prename = $prename;
    }

    /**
     * @return mixed
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @param mixed $lastname
     */
    public function setLastname($lastname): void
    {
        $this->lastname = $lastname;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return get_object_vars($this);
    }

    /**
     * Returns array in form array(email => 'Full Name')
     *
     * @return array
     */
    public function getRecipientArray()
    {
        if ($this->getFullName()) {
            return [$this->getEmail() => $this->getFullName()];
        }

        return [$this->getEmail()];
    }

    /**
     * @return bool|string
     */
    public function getFullName()
    {
        if ($this->name) {
            return $this->name;
        }

        if ($this->prename && $this->lastname) {
            return $this->prename . ' ' . $this->lastname;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }
}

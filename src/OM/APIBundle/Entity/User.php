<?php

namespace OM\APIBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * OM\APIBundle\Entity\User
 *
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="OM\APIBundle\Entity\UserRepository")
 */
class User implements UserInterface, \Serializable
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=25, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $password;
    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $salt = null;

    /**
     * @ORM\Column(type="string", length=60, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;

    /**
     * @ORM\Column(name="last_login", type="integer", nullable=true)
     */
    private $lastLogin = null;

    /**
     * @var Collection
     * @ORM\OneToMany(targetEntity="Message", mappedBy="from_id")
     */
    private $myMessages;

    /**
     * @var Collection
     * @ORM\OneToMany(targetEntity="Message", mappedBy="to_id")
     */
    private $messagesToMe;

    public function __construct()
    {
        $this->isActive = true;
        $this->myMessages= new ArrayCollection();
        $this->messagesToMe= new ArrayCollection();
        $this->salt = "";//md5(uniqid(null, true));
    }

    /**
     * @inheritDoc
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @inheritDoc
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @inheritDoc
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @inheritDoc
     */
    public function getRoles()
    {
        return array('ROLE_USER');
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials()
    {
    }

    /**
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        return serialize($this->getValues());
    }

    /**
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        $this->setValues(unserialize($serialized));
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
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
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
     * Set isActive
     *
     * @param boolean $isActive
     * @return User
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set lastLogin
     *
     * @param int $lastLogin
     * @return User
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * Get lastLogin
     *
     * @return int
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * Set salt
     *
     * @param string $salt
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    public function setValues($values)
    {
        foreach ($values as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
        return $this;
    }

    public function getValues()
    {
        return array(
            'id' => $this->getId(),
            'username' => $this->getUsername(),
            'email' => $this->getEmail(),
            'password' => $this->getPassword(),
            'salt' => $this->getSalt(),
            'lastLogin' => $this->getLastLogin(),
            'isActive' => $this->getisActive()
        );
    }

    /**
     * Add myMessages
     *
     * @param \OM\APIBundle\Entity\Message $myMessages
     * @return User
     */
    public function addMyMessage(\OM\APIBundle\Entity\Message $myMessages)
    {
        $this->myMessages[] = $myMessages;

        return $this;
    }

    /**
     * Remove myMessages
     *
     * @param \OM\APIBundle\Entity\Message $myMessages
     */
    public function removeMyMessage(\OM\APIBundle\Entity\Message $myMessages)
    {
        $this->myMessages->removeElement($myMessages);
    }

    /**
     * Get myMessages
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMyMessages()
    {
        return $this->myMessages;
    }

    /**
     * Add messagesToMe
     *
     * @param \OM\APIBundle\Entity\Message $messagesToMe
     * @return User
     */
    public function addMessagesToMe(\OM\APIBundle\Entity\Message $messagesToMe)
    {
        $this->messagesToMe[] = $messagesToMe;

        return $this;
    }

    /**
     * Remove messagesToMe
     *
     * @param \OM\APIBundle\Entity\Message $messagesToMe
     */
    public function removeMessagesToMe(\OM\APIBundle\Entity\Message $messagesToMe)
    {
        $this->messagesToMe->removeElement($messagesToMe);
    }

    /**
     * Get messagesToMe
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMessagesToMe()
    {
        return $this->messagesToMe;
    }
}

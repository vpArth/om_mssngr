<?php

namespace OM\APIBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OM\APIBundle\Entity\User
 *
 * @ORM\Table(name="messages",indexes={@ORM\Index(name="from_idx", columns={"from_id"}),@ORM\Index(name="to_idx", columns={"to_id"})})
 * @ORM\Entity(repositoryClass="OM\APIBundle\Entity\MessageRepository")
 */
class Message implements \Serializable
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $text = "";

    /**
     * @ORM\ManyToOne(targetEntity="User", cascade={"all"}, fetch="EAGER")
     * @ORM\JoinColumn(name="from_id", referencedColumnName="id")
     */
    private $fromUser;

    /**
     * @ORM\ManyToOne(targetEntity="User", cascade={"all"}, fetch="EAGER")
     * @ORM\JoinColumn(name="to_id", referencedColumnName="id")
     */
    private $toUser;

    /**
     * @ORM\Column(type="integer")
     */
    private $from_id = "";

    /**
     * @ORM\Column(type="integer")
     */
    private $to_id = "";

    /**
     * @ORM\Column(name="created", type="integer")
     */
    private $created;

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
     * Set text
     *
     * @param string $text
     * @return Message
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text
     *
     * @return string 
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return Message
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return integer 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set fromUser
     *
     * @param \OM\APIBundle\Entity\User $fromUser
     * @return Message
     */
    public function setFromUser(\OM\APIBundle\Entity\User $fromUser = null)
    {
        $this->fromUser = $fromUser;

        return $this;
    }

    /**
     * Get fromUser
     *
     * @return \OM\APIBundle\Entity\User 
     */
    public function getFromUser()
    {
        return $this->fromUser;
    }

    /**
     * Set toUser
     *
     * @param \OM\APIBundle\Entity\User $toUser
     * @return Message
     */
    public function setToUser(\OM\APIBundle\Entity\User $toUser = null)
    {
        $this->toUser = $toUser;

        return $this;
    }

    /**
     * Get toUser
     *
     * @return \OM\APIBundle\Entity\User 
     */
    public function getToUser()
    {
        return $this->toUser;
    }

    /**
     * Set from_id
     *
     * @param integer $fromId
     * @return Message
     */
    public function setFromId($fromId)
    {
        $this->from_id = $fromId;

        return $this;
    }

    /**
     * Get from_id
     *
     * @return integer
     */
    public function getFromId()
    {
        return $this->from_id;
    }

    /**
     * Set to_id
     *
     * @param integer $toId
     * @return Message
     */
    public function setToId($toId)
    {
        $this->to_id = $toId;

        return $this;
    }

    /**
     * Get to_id
     *
     * @return integer
     */
    public function getToId()
    {
        return $this->to_id;
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
            'id' => $this->id,
            'from_id' => $this->from_id,
            'to_id' => $this->to_id,
            'text' => $this->getText(),
            'created' => $this->getCreated(),
        );
    }

}

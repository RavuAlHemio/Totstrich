<?php

namespace RavuAlHemio\TotstrichBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Model
 * @ORM\Table(name="deadlines")
 */
class Deadline
{
    /**
     * @var string
     * @ORM\Column(name="deadline_id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $numID;

    /**
     * @var string
     * @ORM\Column(name="description", type="string", length=256, nullable=false)
     */
    public $strDescription;

    /**
     * @var \DateTime
     * @ORM\Column(name="deadline", type="datetimetz", nullable=false)
     */
    public $dtmDeadline;

    /**
     * @var boolean
     * @ORM\Column(name="complete", nullable=false)
     */
    public $blnComplete;
}

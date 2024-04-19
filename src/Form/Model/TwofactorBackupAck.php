<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class TwofactorBackupAck
{
    /** @var bool */
    #[Assert\IsTrue(message: 'form.twofactor-backup-ack-missing')]
    public $ack = false;
}

<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class TwofactorBackupAck
{
    #[Assert\IsTrue(message: 'form.twofactor-backup-ack-missing')]
    public bool $ack = false;
}

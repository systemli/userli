<?php

namespace App\Form\Model;

/**
 * @author louis <louis@systemli.org>
 */
class Registration
{
    /**
     * @var string
     */
    private $voucher;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $plainPassword;

    /**
     * @return string
     */
    public function getVoucher()
    {
        return $this->voucher;
    }

    /**
     * @param string $voucher
     */
    public function setVoucher(string $voucher)
    {
        $this->voucher = $voucher;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email)
    {
        $this->email = strtolower($email);
    }

    /**
     * @return string
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @param string $plainPassword
     */
    public function setPlainPassword(string $plainPassword)
    {
        $this->plainPassword = $plainPassword;
    }
}

<?php

namespace App\Tests;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserUnitTest extends TestCase
{
    public function testIsTrue()
    {
        $user = new User();

        $user
            ->setEmail('true@test.com')
            ->setFirstname('prenom')
            ->setLastname('nom');

        $this->assertTrue('true@test.com' === $user->getEmail());
        $this->assertTrue('prenom' === $user->getFirstname());
        $this->assertTrue('nom' === $user->getLastname());
    }

    public function testIsFalse()
    {
        $user = new User();

        $user
            ->setEmail('true@test.com')
            ->setFirstname('prenom')
            ->setLastname('nom');

        $this->assertFalse('false@test.com' === $user->getEmail());
        $this->assertFalse('false' === $user->getFirstname());
        $this->assertFalse('false' === $user->getLastname());
    }

    public function testIsEmpty()
    {
        $user = new User();

        $this->assertEmpty($user->getEmail());
        $this->assertEmpty($user->getFirstname());
        $this->assertEmpty($user->getLastname());
    }
}

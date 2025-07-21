<?php 

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\User;

class UserTest extends TestCase
{
    public function testEmailSetterGetter(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $this->assertEquals('wperrot@example.com', $user->getEmail()); //vrai user
    }
}

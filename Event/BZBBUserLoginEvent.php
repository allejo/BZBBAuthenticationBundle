<?php

namespace allejo\BZBBAuthenticationBundle\Event;

use Symfony\Component\Security\Core\User\UserInterface;

class BZBBUserLoginEvent
{
    const NAME = 'bzbb.auth.user_login';

    /**
     * @var UserInterface
     */
    private $user;

    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }

    /**
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }
}

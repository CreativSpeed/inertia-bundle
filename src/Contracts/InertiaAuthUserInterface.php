<?php

namespace Creativspeed\InertiaBundle\Contracts;

interface InertiaAuthUserInterface
{
    /**
     * Return the array of user data to share with Inertia frontend.
     * * @return array<string, mixed>
     */
    public function getInertiaAuthData(): array;
}

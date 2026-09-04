<?php

namespace CreativSpeed\InertiaBundle\Contracts;

interface InertiaAuthUserInterface
{
    /**
     * Return the array of user data to share with the Inertia frontend.
     *
     * @return array<string, mixed>
     */
    public function getInertiaAuthData(): array;
}

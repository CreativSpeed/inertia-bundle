<?php
namespace CreativSpeed\InertiaBundle\Tests\Controller;

use Creativspeed\InertiaBundle\Test\InertiaTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardTest extends WebTestCase
{
use InertiaTestTrait;

public function testIndex()
{
$client = static::createClient();
$client->request('GET', '/dashboard');

$response = $client->getResponse();

// Assert it is an Inertia response
$this->assertInertia($response);

// Assert specific component and props
$this->assertInertia($response, 'Dashboard/Index', [
'role' => 'admin'
]);
}
}

<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PartnerControllerTest extends WebTestCase
{
    /** @dataProvider adminRoutes */
    public function testAnonymousUserIsRedirectedToLogin(string $method, string $path): void
    {
        $client = static::createClient();
        $client->request($method, $path);

        self::assertResponseRedirects('http://localhost/');
    }

    public static function adminRoutes(): iterable
    {
        yield 'list' => ['GET', '/admin/partenaire/'];
        yield 'new' => ['GET', '/admin/partenaire/ajouter'];
        yield 'show' => ['GET', '/admin/partenaire/1'];
        yield 'edit' => ['GET', '/admin/partenaire/1/modifier'];
        yield 'delete' => ['POST', '/admin/partenaire/1'];
    }
}

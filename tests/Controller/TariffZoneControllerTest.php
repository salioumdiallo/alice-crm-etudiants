<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TariffZoneControllerTest extends WebTestCase
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
        yield 'list' => ['GET', '/admin/tariff_zone/'];
        yield 'new' => ['GET', '/admin/tariff_zone/ajouter'];
        yield 'show' => ['GET', '/admin/tariff_zone/1'];
        yield 'edit' => ['GET', '/admin/tariff_zone/1/modifier'];
        yield 'delete' => ['POST', '/admin/tariff_zone/1'];
    }
}

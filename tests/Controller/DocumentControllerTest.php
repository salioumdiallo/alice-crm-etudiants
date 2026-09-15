<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DocumentControllerTest extends WebTestCase
{
    /** @dataProvider protectedDocumentRoutes */
    public function testAnonymousUserIsRedirectedToLogin(string $method, string $path): void
    {
        $client = static::createClient();
        $client->request($method, $path);

        self::assertResponseRedirects('/');
    }

    public static function protectedDocumentRoutes(): iterable
    {
        yield 'list' => ['GET', '/document/'];
        yield 'new' => ['GET', '/document/nouveau'];
        yield 'show' => ['GET', '/document/1'];
        yield 'file' => ['GET', '/document/1/fichier'];
        yield 'edit' => ['GET', '/document/1/modifier'];
        yield 'delete' => ['POST', '/document/1'];
    }
}

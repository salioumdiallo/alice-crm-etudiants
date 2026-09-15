<?php

namespace App\Tests\Controller;

use App\Entity\TariffZone;
use App\Entity\User;
use App\Repository\TariffZoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TariffZoneControllerTest extends WebTestCase
{
    /** @dataProvider adminRoutes */
    public function testAnonymousUserIsRedirectedToLogin(string $method, string $path): void
    {
        $client = static::createClient();
        $client->request($method, $path);

        self::assertResponseRedirects('/');
    }

    public static function adminRoutes(): iterable
    {
        yield 'list' => ['GET', '/admin/tariff_zone/'];
        yield 'new' => ['GET', '/admin/tariff_zone/ajouter'];
        yield 'show' => ['GET', '/admin/tariff_zone/1'];
        yield 'edit' => ['GET', '/admin/tariff_zone/1/modifier'];
        yield 'delete' => ['POST', '/admin/tariff_zone/1'];
    }

    public function testRegularUserCannotAccessTariffZoneAdministration(): void
    {
        $client = static::createClient();
        $client->loginUser($this->persistUser(['ROLE_USER']));

        $client->request('GET', '/admin/tariff_zone/');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanCreateEditAndDeleteTariffZone(): void
    {
        $client = static::createClient();
        $client->loginUser($this->persistUser(['ROLE_ADMIN']));

        $client->request('GET', '/admin/tariff_zone/ajouter');
        self::assertResponseIsSuccessful();
        $client->submitForm('Enregistrer', [
            'tariff_zone[name]' => 'Zone test',
            'tariff_zone[amount]' => '42.50',
        ]);
        self::assertResponseRedirects('/admin/tariff_zone/', 303);

        $repository = static::getContainer()->get(TariffZoneRepository::class);
        $tariffZone = $repository->findOneBy(['name' => 'Zone test']);
        self::assertInstanceOf(TariffZone::class, $tariffZone);
        self::assertSame('42.5', $tariffZone->getAmount());
        $tariffZoneId = $tariffZone->getId();

        $client->request('GET', sprintf('/admin/tariff_zone/%d/modifier', $tariffZoneId));
        self::assertResponseIsSuccessful();
        $client->submitForm('Enregistrer', [
            'tariff_zone[name]' => 'Zone modifiée',
            'tariff_zone[amount]' => '57.75',
        ]);
        self::assertResponseRedirects('/admin/tariff_zone/', 303);

        $repository = static::getContainer()->get(TariffZoneRepository::class);
        $tariffZone = $repository->find($tariffZoneId);
        self::assertInstanceOf(TariffZone::class, $tariffZone);
        self::assertSame('Zone modifiée', $tariffZone->getName());
        self::assertSame('57.75', $tariffZone->getAmount());

        $crawler = $client->request('GET', sprintf('/admin/tariff_zone/%d', $tariffZoneId));
        self::assertResponseIsSuccessful();
        $client->submit($crawler->selectButton('Supprimer')->form());
        self::assertResponseRedirects('/admin/tariff_zone/', 303);
        $repository = static::getContainer()->get(TariffZoneRepository::class);
        self::assertNull($repository->find($tariffZoneId));
    }

    private function persistUser(array $roles): User
    {
        $user = (new User())
            ->setEmail(sprintf('%s-tariff@example.test', strtolower(str_replace('ROLE_', '', $roles[0]))))
            ->setPassword('test-password-hash')
            ->setFirstname('Test')
            ->setLastname('User')
            ->setSlug('test-tariff-user')
            ->setRoles($roles)
            ->setIsVerified(true);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}

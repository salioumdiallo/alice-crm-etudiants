<?php

namespace App\Tests\Controller;

use App\Entity\Partner;
use App\Entity\User;
use App\Repository\PartnerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PartnerControllerTest extends WebTestCase
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
        yield 'list' => ['GET', '/admin/partenaire/'];
        yield 'new' => ['GET', '/admin/partenaire/ajouter'];
        yield 'show' => ['GET', '/admin/partenaire/1'];
        yield 'edit' => ['GET', '/admin/partenaire/1/modifier'];
        yield 'delete' => ['POST', '/admin/partenaire/1'];
    }

    public function testRegularUserCannotAccessPartnerAdministration(): void
    {
        $client = static::createClient();
        $client->loginUser($this->persistUser(['ROLE_USER']));

        $client->request('GET', '/admin/partenaire/');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanCreateEditAndDeletePartner(): void
    {
        $client = static::createClient();
        $client->loginUser($this->persistUser(['ROLE_ADMIN']));

        $client->request('GET', '/admin/partenaire/ajouter');
        self::assertResponseIsSuccessful();

        $client->submitForm('Enregistrer', [
            'partner[name]' => 'Partenaire test',
            'partner[discountRate]' => 12,
        ]);
        self::assertResponseRedirects('/admin/partenaire/', 303);

        $repository = static::getContainer()->get(PartnerRepository::class);
        $partner = $repository->findOneBy(['name' => 'Partenaire test']);
        self::assertInstanceOf(Partner::class, $partner);
        self::assertSame(12, $partner->getDiscountRate());
        $partnerId = $partner->getId();

        $client->request('GET', sprintf('/admin/partenaire/%d/modifier', $partnerId));
        self::assertResponseIsSuccessful();
        $client->submitForm('Modifier', [
            'partner[name]' => 'Partenaire modifié',
            'partner[discountRate]' => 18,
        ]);
        self::assertResponseRedirects('/admin/partenaire/', 303);

        $repository = static::getContainer()->get(PartnerRepository::class);
        $partner = $repository->find($partnerId);
        self::assertInstanceOf(Partner::class, $partner);
        self::assertSame('Partenaire modifié', $partner->getName());
        self::assertSame(18, $partner->getDiscountRate());

        $crawler = $client->request('GET', sprintf('/admin/partenaire/%d', $partnerId));
        self::assertResponseIsSuccessful();
        $client->submit($crawler->selectButton('Supprimer')->form());
        self::assertResponseRedirects('/admin/partenaire/', 303);
        $repository = static::getContainer()->get(PartnerRepository::class);
        self::assertNull($repository->find($partnerId));
    }

    private function persistUser(array $roles): User
    {
        $user = (new User())
            ->setEmail(sprintf('%s@example.test', strtolower(str_replace('ROLE_', '', $roles[0]))))
            ->setPassword('test-password-hash')
            ->setFirstname('Test')
            ->setLastname('User')
            ->setSlug('test-user')
            ->setRoles($roles)
            ->setIsVerified(true);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}

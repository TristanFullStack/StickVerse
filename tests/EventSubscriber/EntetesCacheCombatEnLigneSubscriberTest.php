<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\EntetesCacheCombatEnLigneSubscriber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class EntetesCacheCombatEnLigneSubscriberTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function fournirRoutesProtegees(): iterable
    {
        yield 'état du combat' => [
            'app_combat_en_ligne_etat',
        ];
        yield 'état du salon' => [
            'app_salon_combat_en_ligne_etat',
        ];
        yield 'interface des combats' => [
            'app_combats_en_ligne',
        ];
        yield 'rapport du combat' => [
            'app_rapport_combat_en_ligne',
        ];
    }

    #[DataProvider('fournirRoutesProtegees')]
    public function testInterditLaMiseEnCacheDesRoutesProtegees(
        string $route,
    ): void {
        $reponse = $this->declencherAbonne(
            $route,
            HttpKernelInterface::MAIN_REQUEST,
        );

        self::assertTrue(
            $reponse->headers->hasCacheControlDirective('private'),
        );
        self::assertTrue(
            $reponse->headers->hasCacheControlDirective('no-store'),
        );
        self::assertTrue(
            $reponse->headers->hasCacheControlDirective('no-cache'),
        );
        self::assertTrue(
            $reponse->headers->hasCacheControlDirective(
                'must-revalidate',
            ),
        );
        self::assertSame(0, $reponse->getMaxAge());
        self::assertSame('no-cache', $reponse->headers->get('Pragma'));
        self::assertSame(
            'Thu, 01 Jan 1970 00:00:00 GMT',
            $reponse->headers->get('Expires'),
        );
    }

    public function testNeModifiePasUneAutreRoute(): void
    {
        $reponse = $this->declencherAbonne(
            'app_wiki',
            HttpKernelInterface::MAIN_REQUEST,
        );

        self::assertFalse(
            $reponse->headers->hasCacheControlDirective('no-store'),
        );
        self::assertNull($reponse->headers->get('Pragma'));
    }

    public function testIgnoreUneSousRequete(): void
    {
        $reponse = $this->declencherAbonne(
            'app_combat_en_ligne_etat',
            HttpKernelInterface::SUB_REQUEST,
        );

        self::assertFalse(
            $reponse->headers->hasCacheControlDirective('no-store'),
        );
        self::assertNull($reponse->headers->get('Pragma'));
    }

    private function declencherAbonne(
        string $route,
        int $typeRequete,
    ): Response {
        $requete = Request::create('/test');
        $requete->attributes->set('_route', $route);
        $reponse = new Response();
        $kernel = $this->createStub(HttpKernelInterface::class);
        $evenement = new ResponseEvent(
            $kernel,
            $requete,
            $typeRequete,
            $reponse,
        );

        (new EntetesCacheCombatEnLigneSubscriber())($evenement);

        return $reponse;
    }
}

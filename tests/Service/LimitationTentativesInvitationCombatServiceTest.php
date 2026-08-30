<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\LimitationTentativesInvitationCombatService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

final class LimitationTentativesInvitationCombatServiceTest extends TestCase
{
    public function testAutoriseUneTentativeDisponible(): void
    {
        $joueur = (new User())
            ->setEmail('joueur@example.com');
        $limiteur = $this->createMock(LimiterInterface::class);
        $limiteur
            ->expects(self::once())
            ->method('consume')
            ->willReturn(
                new RateLimit(
                    9,
                    new DateTimeImmutable('+1 minute'),
                    true,
                    10,
                )
            );

        $fabrique = $this->createMock(
            RateLimiterFactoryInterface::class
        );
        $fabrique
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(
                static fn (string $cle): bool =>
                    strlen($cle) === 64
                    && ctype_xdigit($cle)
            ))
            ->willReturn($limiteur);

        $service = new LimitationTentativesInvitationCombatService(
            $fabrique
        );

        self::assertNull(
            $service->consommer($joueur, '198.51.100.10')
        );
    }

    public function testRetourneLaDateApresDepassement(): void
    {
        $joueur = (new User())
            ->setEmail('joueur@example.com');
        $dateReessai = new DateTimeImmutable('+1 minute');
        $limiteur = $this->createMock(LimiterInterface::class);
        $limiteur
            ->expects(self::once())
            ->method('consume')
            ->willReturn(
                new RateLimit(0, $dateReessai, false, 10)
            );

        $fabrique = $this->createMock(
            RateLimiterFactoryInterface::class
        );
        $fabrique
            ->expects(self::once())
            ->method('create')
            ->willReturn($limiteur);

        $service = new LimitationTentativesInvitationCombatService(
            $fabrique
        );

        self::assertSame(
            $dateReessai,
            $service->consommer($joueur, null),
        );
    }
}

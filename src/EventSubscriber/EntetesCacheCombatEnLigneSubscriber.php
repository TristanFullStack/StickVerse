<?php

namespace App\EventSubscriber;

use DateTimeImmutable;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

#[AsEventListener]
final class EntetesCacheCombatEnLigneSubscriber
{
    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $route = $event->getRequest()->attributes->get('_route');

        if (!is_string($route) || !$this->routeEstProtegee($route)) {
            return;
        }

        $reponse = $event->getResponse();
        $reponse->setPrivate();
        $reponse->setMaxAge(0);
        $reponse->setExpires(new DateTimeImmutable('@0'));
        $reponse->headers->addCacheControlDirective('no-store');
        $reponse->headers->addCacheControlDirective('no-cache');
        $reponse->headers->addCacheControlDirective('must-revalidate');
        $reponse->headers->set('Pragma', 'no-cache');
    }

    private function routeEstProtegee(string $route): bool
    {
        return str_starts_with(
            $route,
            'app_combat_en_ligne_',
        ) || str_starts_with(
            $route,
            'app_salon_combat_en_ligne_',
        ) || in_array(
            $route,
            [
                'app_combats_en_ligne',
                'app_rapport_combat_en_ligne',
            ],
            true,
        );
    }
}

<?php

namespace App\EventSubscriber;

use App\Entity\Grade;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class GradeEventSubscriber implements EventSubscriberInterface
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Grade) {
            return;
        }

        // Si la date de création n'est pas définie, la définir à maintenant
        if ($entity->getCreatedAt() === null) {
            $entity->setCreatedAt(new \DateTimeImmutable());
        }
    }
}

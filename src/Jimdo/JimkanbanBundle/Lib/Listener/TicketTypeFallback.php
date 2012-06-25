<?php

namespace Jimdo\JimkanbanBundle\Lib\Listener;

use \Doctrine\ORM\Event\LifecycleEventArgs;
use \Jimdo\JimkanbanBundle\Entity\TicketType;

class TicketTypeFallback
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var \Jimdo\JimkanbanBundle\Entity\TicketType
     */
    private $entity;

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $this->entity = $args->getEntity();
        $em = $this->entityManager = $args->getEntityManager();

        if (!$entity instanceof TicketType) {
            return;
        } else {
            if ($this->isSupposedToBeFallback()) {
                $this->unsetCurrentFallbackTicketType();
            }
        }
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        return $this->postPersist($args);
    }

    private function isSupposedToBeFallback()
    {
        return $this->entity->getIsFallback();
    }

    private function unsetCurrentFallbackTicketType()
    {
        /**
         * @var \Jimdo\JimkanbanBundle\Entity\TicketTypeRepository
         */
        $repository = $this->entityManager->getRepository('JimdoJimkanbanBundle:TicketType');

        foreach ($repository->findBeingFallbackAndNotBeingEntity($this->entity->getId()) as $entity) {
            $entity->setIsFallback(false);
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
        }
    }


}
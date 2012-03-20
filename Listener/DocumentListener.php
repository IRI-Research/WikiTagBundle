<?php
/*
 * This file is part of the WikiTagBundle package.
 *
 * (c) IRI <http://www.iri.centrepompidou.fr/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IRI\Bundle\WikiTagBundle\Listener;

use Doctrine\ORM\UnitOfWork;

use IRI\Bundle\WikiTagBundle\Event\WikiTagEvents;

use IRI\Bundle\WikiTagBundle\Event\DocumentTagEvent;

use Doctrine\ORM\Tools\ToolEvents;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Doctrine ORM listener updating the document index
 *
 * @author ymh
 *
 */
class DocumentListener implements EventSubscriber
{
    
    /**
    * @var ContainerInterface
    */
    private $container;
    
    /**
     * Constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    public function getContainer()
    {
        return $this->container;
    }
    
    public function getSubscribedEvents()
    {
        return array(
        Events::preRemove,
        Events::postPersist,
        Events::postUpdate,
        Events::postRemove,
        Events::onFlush,
        );
    }
    
    private function updateTagsStr($doc)
    {
        $this->getContainer()->get('doctrine')->getRepository("WikiTagBundle:Document")->updateTagsStr($doc);
        $event_dispatcher = $this->getContainer()->get('event_dispatcher');
        $event = new DocumentTagEvent($doc);
        $event_dispatcher->dispatch(WikiTagEvents::onTagChanged, $event);
    }

    
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $logger = $this->container->get('logger');
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();
        $class = $this->getContainer()->getParameter("wiki_tag.document_class");
        
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (is_a($entity, $class)) {
                $this->writeDoc($entity, $em);
            }
        }
        
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (is_a($entity, $class)) {
                $this->writeDoc($entity, $em);
            }
        }
        
    }
    
    private function writeDoc($entity, $em)
    {
        $logger = $this->container->get('logger');
        $classmd = $em->getClassMetadata("WikiTagBundle:Document");
        $uow = $em->getUnitOfWork();
        
        $logger->debug("treating document : " . $entity->getId());
        $doc = $this->container->get('doctrine')->getRepository("WikiTagBundle:Document")->writeDocument($entity, $this->getContainer()->getParameter("wiki_tag.document_id_column"), $this->getContainer()->getParameter("wiki_tag.fields"));
        $uow->computeChangeSet($classmd, $doc);
        
    }
    
    public function postPersist(LifecycleEventArgs $args)
    {
        $logger = $this->container->get('logger');
        $logger->debug("HandleEvent : PERSISTS");
        $this->handleEvent($args);
        
    }
    
    public function postUpdate(LifecycleEventArgs $args)
    {
        $logger = $this->container->get('logger');
        $logger->debug("HandleEvent : UPDATE");
        $this->handleEvent($args);
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $logger = $this->container->get('logger');
        $entity = $args->getEntity();
        $class = $this->getContainer()->getParameter("wiki_tag.document_class");
        if (is_a($entity, $class))
        {
            $this->container->get('doctrine')->getRepository("WikiTagBundle:Document")->removeDocument($entity, $this->getContainer()->getParameter("wiki_tag.document_id_column"));
        }
    }
    
    public function postRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if (is_a($entity, "IRI\Bundle\WikiTagBundle\Model\DocumentTagInterface"))
        {
            $doc = $entity->getDocument();
            $state = $args->getEntityManager()->getUnitOfWork()->getEntityState($doc);
            if($state !== UnitOfWork::STATE_REMOVED) {
                $this->updateTagsStr($doc);
            }
        }
    }
    
    
    private function handleEvent(LifecycleEventArgs $args)
    {
        $logger = $this->container->get('logger');
        $entity = $args->getEntity();
        
        if (is_a($entity, "IRI\Bundle\WikiTagBundle\Model\DocumentTagInterface"))
        {
            $doc = $entity->getDocument();
            $state = $args->getEntityManager()->getUnitOfWork()->getEntityState($doc);
            if($state !== UnitOfWork::STATE_REMOVED) {
                $this->updateTagsStr($doc);
            }
        }
        elseif (is_a($entity, "IRI\Bundle\WikiTagBundle\Model\TagInterface"))
        {
            $documents = $entity->getDocuments();
            if(!is_null($documents))
            {
                foreach($documents as $doctag)
                {
                    $doc = $doctag->getDocument();
                    $state = $args->getEntityManager()->getUnitOfWork()->getEntityState($doc);
                    if($state !== UnitOfWork::STATE_REMOVED) {
                        $this->updateTagsStr($doc);
                    }
                }
            }
        }
        
    }
    
}
<?php

namespace App\Model\Image;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use App\Model\Image\Config\ImageConfig;

class ImageDeleteDoctrineListener
{
    /**
     * @var \App\Model\Image\Config\ImageConfig
     */
    protected $imageConfig;

    /**
     * @var \App\Model\Image\ImageFacade
     */
    protected $imageFacade;

    /**
     * @param \App\Model\Image\Config\ImageConfig $imageConfig
     * @param \App\Model\Image\ImageFacade $imageFacade
     */
    public function __construct(
        ImageConfig $imageConfig,
        ImageFacade $imageFacade
    ) {
        $this->imageConfig = $imageConfig;
        $this->imageFacade = $imageFacade;
    }

    /**
     * Prevent ServiceCircularReferenceException (DoctrineListener cannot be dependent on the EntityManager)
     *
     * @return \App\Model\Image\ImageFacade
     */
    protected function getImageFacade()
    {
        return $this->imageFacade;
    }

    /**
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($this->imageConfig->hasImageConfig($entity)) {
            $this->deleteEntityImages($entity, $args->getEntityManager());
        } elseif ($entity instanceof Image) {
            $this->getImageFacade()->deleteImageFiles($entity);
        }
    }

    /**
     * @param object $entity
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    protected function deleteEntityImages($entity, EntityManagerInterface $em)
    {
        $images = $this->getImageFacade()->getAllImagesByEntity($entity);
        foreach ($images as $image) {
            $em->remove($image);
        }
    }
}

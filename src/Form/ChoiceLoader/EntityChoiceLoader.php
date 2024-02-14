<?php

declare(strict_types=1);

namespace App\Form\ChoiceLoader;

use App\Entity\EntityWithId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Uid\Uuid;

/**
 * This loaded will load all records from a doctrine entity. Since we do not know exactly what to display (for instance, their email address,
 * or a dossier title etc), we pass a callable that will be called for each entity to get the display value.
 */
class EntityChoiceLoader implements ChoiceLoaderInterface
{
    protected EntityManagerInterface $doctrine;
    /** @var class-string */
    protected string $entityClass;
    /** @var callable */
    protected $nameCallable;

    /**
     * @param class-string $entityClass
     */
    public function __construct(EntityManagerInterface $doctrine, string $entityClass, callable $nameCallable)
    {
        $this->doctrine = $doctrine;
        $this->entityClass = $entityClass;
        $this->nameCallable = $nameCallable;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function loadChoiceList(?callable $value = null): ChoiceListInterface
    {
        $repository = $this->doctrine->getRepository($this->entityClass);
        $entities = $repository->findAll();

        $choices = [];
        foreach ($entities as $entity) {
            /** @var EntityWithId $entity */
            $name = call_user_func($this->nameCallable, $entity);
            $choices[$name] = (string) $entity->getId();
        }

        return new ArrayChoiceList($choices);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function loadChoicesForValues(array $values, ?callable $value = null): array
    {
        $choices = [];
        foreach ($values as $choice) {
            if (! $choice) {
                continue;
            }
            $choices[] = $this->doctrine->getRepository($this->entityClass)->find(Uuid::fromString($choice));
        }

        return $choices;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function loadValuesForChoices(array $choices, ?callable $value = null): array
    {
        $values = [];
        foreach ($choices as $choice) {
            /** @var EntityWithId $choice */
            if ($choice === null || empty($choice)) {
                continue;
            }

            $values[] = (string) $choice->getId();
        }

        return $values;
    }
}

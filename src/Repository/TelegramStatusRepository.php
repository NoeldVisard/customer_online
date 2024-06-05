<?php

namespace App\Repository;

use App\Entity\TelegramStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramStatus>
 */
class TelegramStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramStatus::class);
    }

    public function save(TelegramStatus $telegramStatus): void
    {
        $entityManager = $this->getEntityManager();

        $existingStatus = $this->findOneByChat($telegramStatus->getChat());

        if ($existingStatus) {
            $existingStatus->setStatus($telegramStatus->getStatus());
        } else {
            $entityManager->persist($telegramStatus);
        }

        $entityManager->flush();
    }

    public function findOneByChat(int $chat)
    {
        return $this->findOneBy(['chat' => $chat]);
    }

    //    /**
    //     * @return TelegramStatus[] Returns an array of TelegramStatus objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?TelegramStatus
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

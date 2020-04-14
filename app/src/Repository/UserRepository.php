<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\LazyCriteriaCollection;
use Gedmo\Tool\Wrapper\EntityWrapper;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use http\Exception\InvalidArgumentException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends  NestedTreeRepository implements PasswordUpgraderInterface
{

    /**
     * Get nested set children nodes in boundaries of limit and offset
     *
     * @param $node
     * @param Criteria $criteria
     * @param bool $directChildren
     * @return mixed
     */
    public function findChildrenByCriteria($node, Criteria $criteria,  bool $directChildren = false):LazyCriteriaCollection
    {
        if ($directChildren) {
            $criteria->where(Criteria::expr()->eq('parent', $node));
        } else {
            $criteria->where(Criteria::expr()->lt('rgt', $node->getRgt()));
            $criteria->andWhere(Criteria::expr()->gt('lft', $node->getLft()));
        }
        $criteria->andWhere(Criteria::expr()->eq('tree_root', $node->getTreeRoot()));

        return $this->matching($criteria);
    }

    /**
     * Get nested set children nodes in boundaries of limit and offset
     *
     * @param $node
     * @param QueryBuilder $qb
     * @param bool $directChildren
     * @param bool $includeNode
     * @throws InvalidArgumentException
     * @return QueryBuilder
     */
    public function extendChildrenQueryBuilder($node, $qb,  bool $directChildren = false, bool $includeNode = false):QueryBuilder
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->_em, $meta->name);

        $qb->select('node')
            ->from($config['useObjectClass'], 'node');
            if ($node instanceof $meta->name) {
                $wrapped = new EntityWrapper($node, $this->_em);
                if (!$wrapped->hasValidIdentifier()) {
                    throw new \InvalidArgumentException("Node is not managed by UnitOfWork");
                }
                if ($directChildren) {
                    $qb->where($qb->expr()->eq('node.'.$config['parent'], ':pid'));
                    $qb->setParameter('pid', $wrapped->getIdentifier());
                } else {
                    $left = $wrapped->getPropertyValue($config['left']);
                    $right = $wrapped->getPropertyValue($config['right']);
                    if ($left && $right) {
                        $qb->where($qb->expr()->lt('node.'.$config['right'], $right));
                        $qb->andWhere($qb->expr()->gt('node.'.$config['left'], $left));
                    }
                }
                if (isset($config['root'])) {
                    $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
                    $qb->setParameter('rid', $wrapped->getPropertyValue($config['root']));
                }
                if ($includeNode) {
                    $idField = $meta->getSingleIdentifierFieldName();
                    $qb->where('('.$qb->getDqlPart('where').') OR node.'.$idField.' = :rootNode');
                    $qb->setParameter('rootNode', $node);
                }
            } else {
                throw new \InvalidArgumentException("Node is not related to this repository");
            }


        return $qb;
    }

    /**
     * Count children for a specific node
     *
     * @param $node
     * @param bool|false $directChildren
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @return mixed
     */
    public function countChildren($node, bool $directChildren = false)
    {
        if($directChildren) {
            $count = $this->countDirectChildren($node);
        } else {
            $count = $this->countAllChildren($node);
        }

        return $count;
    }

    /**
     * Checks whether parentNode is a siblings of a childNode or not
     *
     * @param $parentNode
     * @param $childNode
     * @return bool
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isNodeAChild($parentNode, $childNode):bool
    {
        $qBuilder = $this->createQueryBuilder('node');
        $count = $qBuilder
            ->select('COUNT(node)')
            ->where('node.lft > :lft')
            ->andWhere('node.rgt <= :rgt')
            ->andWhere('node.tree_root = :tree_root')
            ->andWhere('node.lvl > :level')
            ->andWhere('node.id = :child_id')
            ->setParameter('lft', $parentNode->getLft())
            ->setParameter('rgt', $parentNode->getRgt())
            ->setParameter('tree_root', $parentNode->getTreeRoot())
            ->setParameter('child_id', $childNode->getId())
            ->setParameter('level', $parentNode->getLvl())
            ->getQuery()
            ->getSingleScalarResult();

        return (bool)$count;
    }

    /**
     * Return Query Builder for nodes with same tree
     *
     * @param $parentNode
     * @return QueryBuilder
     */
    public function getSameTreeUserQuery($parentNode):QueryBuilder
    {
        return $this->createQueryBuilder('node')
            ->select('node')
            ->andWhere('node.tree_root = :tree_root')
            ->setParameter('tree_root', $parentNode->getTreeRoot())
            ->orderBy('node.name', 'ASC');
    }

    /**
     * Checks whether parentNode and childNode in the one tree or not
     *
     * @param $parentNode
     * @param $childNode
     * @return bool
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function areBelongedToTheSameTree($parentNode, $childNode):bool
    {
        $qBuilder = $this->createQueryBuilder('node');
        $count = $qBuilder
            ->select('COUNT(node)')
            ->andWhere('node.tree_root = :tree_root')
            ->andWhere('node.id < :child_id')
            ->setParameter('tree_root', $parentNode->getTreeRoot())
            ->setParameter('child_id', $childNode->getId())
            ->getQuery()
            ->getSingleScalarResult();

        return (bool)$count;
    }

    /**
     * @param $parentNode
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countAllChildren($parentNode)
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->_em, $meta->name);
        $wrapped = new EntityWrapper($parentNode, $this->_em);

        $qBuilder = $this->createQueryBuilder('node');
        $count = $qBuilder
            ->select('COUNT(node)')
            ->where($qBuilder->expr()->gt('node.' . $config['left'], ':lft'))
            ->andWhere($qBuilder->expr()->lte('node.' . $config['right'], ':rgt'))
            ->andWhere($qBuilder->expr()->lte('node.' . $config['root'], ':tree_root'))
            ->setParameter('lft', $wrapped->getPropertyValue($config['left']))
            ->setParameter('rgt', $wrapped->getPropertyValue($config['right']))
            ->setParameter('tree_root', $wrapped->getPropertyValue($config['root']))
            ->getQuery()
            ->getSingleScalarResult();

        return $count;
    }

    /**
     * @param $parentNode
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countDirectChildren($parentNode)
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->_em, $meta->name);
        $wrapped = new EntityWrapper($parentNode, $this->_em);

        $qBuilder = $this->createQueryBuilder('node');
        $count = $qBuilder
            ->select('COUNT(node)')
            ->where($qBuilder->expr()->eq('node.'.$config['parent'], ':pid'))
            ->setParameter('pid', $wrapped->getIdentifier())
            ->getQuery()
            ->getSingleScalarResult();

        return $count;
    }
    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function generateToken(UserInterface $user, JWTTokenManagerInterface $jWTManager):string
    {
        $token = $$jWTManager->create(['name' => $user->getName(), 'date' => time()]);
        $user->setApiToken($token);
        $this->_em->persist($user);
        $this->_em->flush();
        return $token;
    }
    /**
     * Checks whether email valid or not
     *
     * @param [type] $email
     * @return boolean
     */
    public function isEmailUnique($email):bool
    {
        $count = $this->count(['email' => $email]);
    
        return (bool)$count;
    }

}

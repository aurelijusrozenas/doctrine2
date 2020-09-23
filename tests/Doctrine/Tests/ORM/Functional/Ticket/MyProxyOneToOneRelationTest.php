<?php
namespace Doctrine\Tests\Functional\Ticket;

use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Tests\OrmFunctionalTestCase;

final class MyProxyOneToOneRelationTest extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(GHParent::class),
            $this->_em->getClassMetadata(GHChild::class),
        ]);
    }

    public function testOneToOneRelationChangeBeforeProxyLoad() : void
    {
        /* setup */
        $childId = 1;
        $childId2 = 2;
        $child = (new GHChild())->setId($childId)->setName('$name');
        $child2 = (new GHChild())->setId($childId2)->setName('$name 2');
        $parentId = 10;
        $parent = (new GHParent())->setId($parentId)->setChild($child);
        $this->_em->persist($child);
        $this->_em->persist($child2);
        $this->_em->persist($parent);
        $this->_em->flush();
        /* ensure created correct data */
        $this->_em->clear();
        $parent = $this->_em->find(GHParent::class, $parentId);
        $child = $this->_em->find(GHChild::class, $childId);
        self::assertSame($child, $parent->getChild(), 'Parent must have child set.');
        /* clear and load from DB */
        $this->_em->clear();
        $parent = $this->_em->find(GHParent::class, $parentId);
        $child = $this->_em->find(GHChild::class, $childId);
        $child2 = $this->_em->find(GHChild::class, $childId2);
        self::assertInstanceOf(Proxy::class, $child, 'Verifying that $child is a proxy before using proxy API');
        // set new child relation
        $parent->setChild($child2);

        // load proxy
        $child->getName(); // <----- only difference between tests

        self::assertSame($childId2, $parent->getChild()->getId(), 'Parent->child relation must not change after proxy load.');
        /* save */
        $this->_em->flush();
        /* reload from DB and same assert */
        $this->_em->clear();
        $parent = $this->_em->find(GHParent::class, $parentId);
        self::assertSame($childId2, $parent->getChild()->getId(), 'Parent->child relation must not change after proxy load.');
    }

    public function testOneToOneRelationChangeWithoutProxyLoad() : void
    {
        /* setup */
        $childId = 1;
        $childId2 = 2;
        $child = (new GHChild())->setId($childId)->setName('$name');
        $child2 = (new GHChild())->setId($childId2)->setName('$name 2');
        $parentId = 10;
        $parent = (new GHParent())->setId($parentId)->setChild($child);
        $this->_em->persist($child);
        $this->_em->persist($child2);
        $this->_em->persist($parent);
        $this->_em->flush();
        /* ensure created correct data */
        $this->_em->clear();
        $parent = $this->_em->find(GHParent::class, $parentId);
        $child = $this->_em->find(GHChild::class, $childId);
        self::assertSame($child, $parent->getChild(), 'Parent must have child set.');
        /* clear and load from DB */
        $this->_em->clear();
        $parent = $this->_em->find(GHParent::class, $parentId);
        $child = $this->_em->find(GHChild::class, $childId);
        $child2 = $this->_em->find(GHChild::class, $childId2);
        self::assertInstanceOf(Proxy::class, $child, 'Verifying that $child is a proxy before using proxy API');
        // set new child relation
        $parent->setChild($child2);
        self::assertSame($childId2, $parent->getChild()->getId(), 'Parent->child relation must not change after proxy load.');
        /* save */
        $this->_em->flush();
        /* reload from DB and same assert */
        $this->_em->clear();
        $parent = $this->_em->find(GHParent::class, $parentId);
        self::assertSame($childId2, $parent->getChild()->getId(), 'Parent->child relation must not change after proxy load.');
    }
}

/**
 * @Entity
 */
class GHParent
{
    /** @Id @Column(type="integer")*/
    private $id;

    /** @OneToOne(targetEntity="GHChild", inversedBy="parent") */
    private $child;

    public function getId()
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getChild(): GHChild
    {
        return $this->child;
    }

    public function setChild(?GHChild $child): self
    {
        $this->child = $child;

        return $this;
    }
}

/**
 * @Entity
 */
class GHChild
{
    /** @Id @Column(type="integer")*/
    private $id;

    /** @OneToOne(targetEntity="GHParent", mappedBy="child") */
    private $parent;

    /** @Column */
    private $name;

    public function getId()
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent(GHParent $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}

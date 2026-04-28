<?php

namespace Shared\Entity;

use Shared\Repository\SonataUserUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap as MappingDiscriminatorMap;
use Doctrine\ORM\Mapping\InheritanceType;
use Sonata\UserBundle\Entity\BaseUser3;
use Zeiterfassung\Entity\FaUser;


#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(name: 'discr', type: 'string')]
#[MappingDiscriminatorMap(['sonatauseruser' => SonataUserUser::class, 'fauser' => FaUser::class])]
#[ORM\Entity(repositoryClass: SonataUserUserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
#[ORM\Table(name: 'user__user')]
class SonataUserUser extends BaseUser3 implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    protected $id;
}

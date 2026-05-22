<?php

namespace App\Command;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:fix-admin-roles',
    description: 'Fix admin user roles to ensure ROLE_ADMIN',
)]
class FixAdminRolesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->em->getRepository(Users::class)->findOneBy(['email' => 'admin@shoes.com']);

        if (!$user) {
            $output->writeln('<error>Admin user not found!</error>');
            return Command::FAILURE;
        }

        $user->setRoles(['ROLE_ADMIN']);
        $this->em->flush();

        $output->writeln('<info>Admin user roles updated successfully!</info>');
        $output->writeln('Roles: ROLE_ADMIN');

        return Command::SUCCESS;
    }
}


<?php

namespace App\Command;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create an admin user account',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        $output->writeln('Create Admin User');
        $output->writeln('================');

        $nameQuestion = new Question('Enter full name: ');
        $name = $helper->ask($input, $output, $nameQuestion);

        $emailQuestion = new Question('Enter email: ');
        $email = $helper->ask($input, $output, $emailQuestion);

        $passwordQuestion = new Question('Enter password: ');
        $passwordQuestion->setHidden(true);
        $password = $helper->ask($input, $output, $passwordQuestion);

        if (!$name || !$email || !$password) {
            $output->writeln('<error>All fields are required!</error>');
            return Command::FAILURE;
        }

        $user = new Users();
        $user->setName($name);
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setIsActive(true);

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln('<info>Admin user created successfully!</info>');
        $output->writeln("Email: {$email}");

        return Command::SUCCESS;
    }
}


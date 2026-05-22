<?php

namespace App\Command;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin-simple',
    description: 'Create a default admin user (non-interactive)',
)]
class CreateAdminSimpleCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = 'admin@shoes.com';
        $password = 'admin123';
        $name = 'Admin User';

        // Check if admin already exists
        $existingUser = $this->em->getRepository(Users::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $output->writeln("<info>Admin user already exists!</info>");
            $output->writeln("Email: <comment>{$email}</comment>");
            $output->writeln("Password: <comment>{$password}</comment>");
            return Command::SUCCESS;
        }

        // Create admin user
        $user = new Users();
        $user->setName($name);
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setIsActive(true);
        $user->setIsVerified(true);

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln("<info>Admin user created successfully!</info>");
        $output->writeln("Email: <comment>{$email}</comment>");
        $output->writeln("Password: <comment>{$password}</comment>");
        $output->writeln("");
        $output->writeln("You can now login at http://127.0.0.1:8000/login");

        return Command::SUCCESS;
    }
}


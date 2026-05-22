<?php

namespace App\Command;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:user:set-password',
    description: 'Set a user password by email (use on Railway if login fails)',
)]
class SetUserPasswordCommand extends Command
{
    public function __construct(
        private UsersRepository $usersRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('password', InputArgument::REQUIRED, 'New plain-text password');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) $input->getArgument('email');
        $password = (string) $input->getArgument('password');

        $user = $this->usersRepository->findOneBy(['email' => $email]);
        if (!$user instanceof Users) {
            $io->error(sprintf('No user found with email "%s".', $email));

            return Command::FAILURE;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setIsActive(true);
        $user->setIsVerified(true);
        $this->usersRepository->getEntityManager()->flush();

        $io->success(sprintf('Password updated for %s. Try logging in again.', $email));

        return Command::SUCCESS;
    }
}

<?php

require __DIR__.'/vendor/autoload.php';

use App\Entity\Users;
use App\Kernel;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');
$passwordHasher = $container->get('security.user_password_hasher');

// Default admin credentials
$email = 'admin@shoes.com';
$password = 'admin123';
$name = 'Admin User';

// Check if admin already exists
$existingUser = $em->getRepository(Users::class)->findOneBy(['email' => $email]);
if ($existingUser) {
    echo "Admin user already exists with email: {$email}\n";
    echo "Password: {$password}\n";
    exit(0);
}

// Create admin user
$user = new Users();
$user->setName($name);
$user->setEmail($email);
$user->setRoles(['ROLE_ADMIN']);
$hashedPassword = $passwordHasher->hashPassword($user, $password);
$user->setPassword($hashedPassword);
$user->setIsActive(true);

$em->persist($user);
$em->flush();

echo "Admin user created successfully!\n";
echo "Email: {$email}\n";
echo "Password: {$password}\n";
echo "\nYou can now login at http://127.0.0.1:8000/login\n";


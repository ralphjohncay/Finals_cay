<?php

require __DIR__.'/vendor/autoload.php';

use App\Entity\Users;
use App\Kernel;

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

$user = $em->getRepository(Users::class)->findOneBy(['email' => 'admin@shoes.com']);

if ($user) {
    $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
    $em->flush();
    echo "Admin user roles updated successfully!\n";
    echo "Roles: ROLE_USER, ROLE_ADMIN\n";
} else {
    echo "Admin user not found!\n";
}


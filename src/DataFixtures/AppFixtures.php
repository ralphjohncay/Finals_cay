<?php

namespace App\DataFixtures;

use App\Entity\ActivityLog;
use App\Entity\Category;
use App\Entity\OrderItem;
use App\Entity\Orders;
use App\Entity\Products;
use App\Entity\Services;
use App\Entity\Users;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Ensure some product images exist where Twig expects them:
        // asset('uploads/products/' ~ product.image)
        $this->ensureProductUploadImages([
            '1.webp',
            '2.jpg',
            '3.avif',
        ]);

        // -------------------------
        // Users
        // -------------------------
        $admin = (new Users())
            ->setEmail('admin@shoes.com')
            ->setName('Admin User')
            ->setRoles(['ROLE_ADMIN'])
            ->setIsActive(true)
            ->setIsVerified(true);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        $staff = (new Users())
            ->setEmail('staff@shoes.com')
            ->setName('Staff User')
            ->setRoles(['ROLE_STAFF'])
            ->setIsActive(true)
            ->setIsVerified(true);
        $staff->setPassword($this->passwordHasher->hashPassword($staff, 'staff123'));
        $manager->persist($staff);

        $customer = (new Users())
            ->setEmail('customer@shoes.com')
            ->setName('Customer User')
            ->setRoles(['ROLE_USER'])
            ->setIsActive(true)
            ->setIsVerified(true);
        $customer->setPassword($this->passwordHasher->hashPassword($customer, 'customer123'));
        $manager->persist($customer);

        // -------------------------
        // Categories
        // -------------------------
        $categoryNames = [
            'Clothing' => 'Everyday essentials and seasonal items.',
            'Footwear' => 'Shoes for work, sport, and casual wear.',
            'Accessories' => 'Bags, belts, and small add-ons.',
        ];

        $categories = [];
        foreach ($categoryNames as $name => $description) {
            $category = (new Category())
                ->setName($name)
                ->setDescription($description)
                ->setIsActive(true)
                ->setCreatedBy($admin);
            $manager->persist($category);
            $categories[$name] = $category;
        }

        // -------------------------
        // Products
        // -------------------------
        $products = [];
        $productsData = [
            [
                'name' => 'Classic Tee',
                'price' => '19.99',
                'description' => 'Soft cotton tee with a classic fit.',
                'category' => 'Clothing',
                'stock' => 25,
                'image' => '2.jpg',
            ],
            [
                'name' => 'Everyday Sneakers',
                'price' => '54.95',
                'description' => 'Comfortable sneakers built for all-day wear.',
                'category' => 'Footwear',
                'stock' => 14,
                'image' => '3.avif',
            ],
            [
                'name' => 'Minimal Backpack',
                'price' => '39.50',
                'description' => 'Lightweight backpack with a clean look.',
                'category' => 'Accessories',
                'stock' => 10,
                'image' => '1.webp',
            ],
        ];

        foreach ($productsData as $row) {
            $product = (new Products())
                ->setName($row['name'])
                ->setPrice($row['price'])
                ->setDescription($row['description'])
                ->setCategory($row['category'])
                ->setStock($row['stock'])
                ->setImage($row['image'])
                ->setIsActive(true)
                ->setCreatedBy($admin);
            $manager->persist($product);
            $products[] = $product;
        }

        // -------------------------
        // Services
        // -------------------------
        $services = [];
        $servicesData = [
            [
                'name' => 'Gift Wrap',
                'description' => 'Neat gift wrapping with premium paper.',
                'price' => '4.99',
                'category' => 'Accessories',
            ],
            [
                'name' => 'Express Handling',
                'description' => 'Priority handling before shipping.',
                'price' => '7.50',
                'category' => 'Clothing',
            ],
        ];

        foreach ($servicesData as $row) {
            $service = (new Services())
                ->setName($row['name'])
                ->setDescription($row['description'])
                ->setPrice($row['price'])
                ->setCategory($row['category'])
                ->setIsActive(true);
            $manager->persist($service);
            $services[] = $service;
        }

        // -------------------------
        // Orders + Order items
        // -------------------------
        $order = (new Orders())
            ->setCustomer($customer)
            ->setStatus('approved');

        $item1 = (new OrderItem())
            ->setProduct($products[0])
            ->setName($products[0]->getName() ?? 'Product')
            ->setPrice($products[0]->getPrice() ?? '0.00')
            ->setQuantity(2);
        $order->addOrderItem($item1);

        $item2 = (new OrderItem())
            ->setService($services[0])
            ->setName($services[0]->getName() ?? 'Service')
            ->setPrice($services[0]->getPrice() ?? '0.00')
            ->setQuantity(1);
        $order->addOrderItem($item2);

        $manager->persist($order);

        // -------------------------
        // Activity logs
        // -------------------------
        $log1 = (new ActivityLog())
            ->setUser($admin)
            ->setAction('LOGIN')
            ->setEntityType('User')
            ->setEntityId($admin->getId())
            ->setDescription('Admin logged in (seeded).')
            ->setIpAddress('127.0.0.1');
        $manager->persist($log1);

        $log2 = (new ActivityLog())
            ->setUser($admin)
            ->setAction('CREATE')
            ->setEntityType('Product')
            ->setEntityId(null)
            ->setAffectedData(json_encode(['count' => count($products)], JSON_THROW_ON_ERROR))
            ->setDescription('Seeded initial products.')
            ->setIpAddress('127.0.0.1');
        $manager->persist($log2);

        $manager->flush();
    }

    private function ensureProductUploadImages(array $filenames): void
    {
        $projectDir = \dirname(__DIR__, 2);
        $sourceDir = $projectDir . \DIRECTORY_SEPARATOR . 'assets' . \DIRECTORY_SEPARATOR . 'images';
        $targetDir = $projectDir . \DIRECTORY_SEPARATOR . 'public' . \DIRECTORY_SEPARATOR . 'uploads' . \DIRECTORY_SEPARATOR . 'products';

        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }

        foreach ($filenames as $filename) {
            $source = $sourceDir . \DIRECTORY_SEPARATOR . $filename;
            $target = $targetDir . \DIRECTORY_SEPARATOR . $filename;

            if (!is_file($source)) {
                continue;
            }
            if (is_file($target)) {
                continue;
            }

            @copy($source, $target);
        }
    }
}

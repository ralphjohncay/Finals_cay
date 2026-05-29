<?php

namespace App\Controller;

use App\Entity\Products;
use App\Form\ProductsType;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ActivityLogService;
use App\Entity\Users;

#[Route('/products')]
#[IsGranted('ROLE_ADMIN')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(Request $request, ProductsRepository $productsRepository): Response
    {
        $categoryFilter = $request->query->get('category');
        
        $products = $categoryFilter 
            ? $productsRepository->findBy(['category' => $categoryFilter])
            : $productsRepository->findAll();
        
        // Get unique categories for filter dropdown
        $allProducts = $productsRepository->findAll();
        $categories = array_unique(array_filter(array_map(function($p) {
            return $p->getCategory();
        }, $allProducts)));
        sort($categories);
        
        return $this->render('product/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'selectedCategory' => $categoryFilter,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $em, 
        ActivityLogService $logService,
        SluggerInterface $slugger,
    ): Response {
        $product = new Products();
        
        // Pre-fill category if provided from category creation
        $category = $request->query->get('category');
        if ($category) {
            $product->setCategory($category);
        }
        
        $form = $this->createForm(ProductsType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('danger', $error->getMessage());
                }
            } else {
                $uploadError = $this->handleProductImageUpload($form->get('imageFile')->getData(), $product, $slugger);
                if ($uploadError !== null) {
                    $this->addFlash('danger', $uploadError);
                } else {
                    $this->finalizeProductBeforeSave($product);

                    $user = $this->getUser();
                    if ($user instanceof Users) {
                        $product->setCreatedBy($user);
                    }

                    $em->persist($product);
                    $em->flush();

                    if ($user instanceof Users && $product->getId() !== null) {
                        $logService->logCreate(
                            $user,
                            'Product',
                            $product->getId(),
                            "Product: {$product->getName()} (ID: {$product->getId()})"
                        );
                    }
                    $this->addFlash('success', 'Product created successfully!');
                    return $this->redirectToRoute('app_product_index');
                }
            }
        }

        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(
        Products $product, 
        Request $request, 
        EntityManagerInterface $em, 
        ActivityLogService $logService,
        SluggerInterface $slugger,
    ): Response {
        $user = $this->getUser();
        
        // Staff can only edit their own records, admin can edit all
        if ($user instanceof Users && !$this->isGranted('ROLE_ADMIN')) {
            if ($product->getCreatedBy() === null || $product->getCreatedBy()->getId() !== $user->getId()) {
                $this->addFlash('danger', 'Access denied. You can only edit your own records.');
                return $this->redirectToRoute('app_product_index');
            }
        }
        
        $oldImage = $product->getImage();
        
        $form = $this->createForm(ProductsType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadError = $this->handleProductImageUpload(
                $form->get('imageFile')->getData(),
                $product,
                $slugger,
                $oldImage
            );
            if ($uploadError !== null) {
                $this->addFlash('danger', $uploadError);
            } else {
                $this->finalizeProductBeforeSave($product);
                $em->flush();
                if ($user instanceof Users) {
                    $logService->logUpdate($user, 'Product', $product->getId(), "Product: {$product->getName()} (ID: {$product->getId()})");
                }
                $this->addFlash('success', 'Product updated successfully!');
                return $this->redirectToRoute('app_product_index');
            }
        }

        return $this->render('product/edit.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_product_delete', methods: ['POST'])]
    public function delete(
        Products $product,
        Request $request,
        EntityManagerInterface $em,
        ActivityLogService $logService,
    ): Response
    {
        $user = $this->getUser();
        
        // Staff can only delete their own records, admin can delete all
        if ($user instanceof Users && !$this->isGranted('ROLE_ADMIN')) {
            if ($product->getCreatedBy() === null || $product->getCreatedBy()->getId() !== $user->getId()) {
                $this->addFlash('danger', 'Access denied. You can only delete your own records.');
                return $this->redirectToRoute('app_product_index');
            }
        }
        
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $productId = $product->getId();
            $productName = $product->getName();
            
            // Delete associated image file
            $image = $product->getImage();
            if ($image) {
                $imagePath = $this->getParameter('product_images_directory') . '/' . $image;
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            $em->remove($product);
            $em->flush();
            if ($user instanceof Users) {
                $logService->logDelete($user, 'Product', $productId, "Product: {$productName} (ID: {$productId})");
            }
            $this->addFlash('success', 'Product deleted successfully!');
        }
        return $this->redirectToRoute('app_product_index');
    }

    #[Route('/{id}/view', name: 'app_product_view', methods: ['GET'])]
    public function view(Products $product): Response
    {
        return $this->render('product/view.html.twig', [
            'product' => $product,
        ]);
    }

    private function ensureProductImagesDirectory(): string
    {
        $dir = (string) $this->getParameter('product_images_directory');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    private function finalizeProductBeforeSave(Products $product): void
    {
        $category = $product->getCategory();
        if ($category === null || trim($category) === '') {
            $product->setCategory('General');
        }

        $product->setIsActive(true);
    }

    /**
     * @return string|null Error message, or null on success / no file
     */
    private function handleProductImageUpload(
        ?UploadedFile $imageFile,
        Products $product,
        SluggerInterface $slugger,
        ?string $oldImageToDelete = null,
    ): ?string {
        if (!$imageFile) {
            return null;
        }

        if ($oldImageToDelete) {
            $oldImagePath = $this->ensureProductImagesDirectory() . '/' . $oldImageToDelete;
            if (is_file($oldImagePath)) {
                @unlink($oldImagePath);
            }
        }

        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $originalFilename !== ''
            ? (string) $slugger->slug($originalFilename)
            : 'image';
        $extension = $imageFile->guessExtension() ?: $imageFile->getClientOriginalExtension() ?: 'bin';
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

        try {
            $imageFile->move($this->ensureProductImagesDirectory(), $newFilename);
            $product->setImage($newFilename);
        } catch (FileException $e) {
            return 'Error uploading file: ' . $e->getMessage();
        }

        return null;
    }
}

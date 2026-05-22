<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\ActivityLogService;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/category')]
#[IsGranted('ROLE_ADMIN')]
class CategoryController extends AbstractController
{
    #[Route('/', name: 'app_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('category/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ActivityLogService $logService): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set createdBy to current user
            $user = $this->getUser();
            if ($user instanceof Users) {
                $category->setCreatedBy($user);
            }
            
            $em->persist($category);
            $em->flush();

            if ($user instanceof Users) {
                $logService->logCreate($user, 'Category', $category->getId(), "Category: {$category->getName()} (ID: {$category->getId()})");
            }

            $this->addFlash('success', 'Category created successfully!');
            return $this->redirectToRoute('app_category_index');
        }

        return $this->render('category/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(Category $category, Request $request, EntityManagerInterface $em, ActivityLogService $logService): Response
    {
        $user = $this->getUser();
        
        // Staff can only edit their own records, admin can edit all
        if ($user instanceof Users && !$this->isGranted('ROLE_ADMIN')) {
            if ($category->getCreatedBy() === null || $category->getCreatedBy()->getId() !== $user->getId()) {
                $this->addFlash('danger', 'Access denied. You can only edit your own records.');
                return $this->redirectToRoute('app_category_index');
            }
        }
        
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            if ($user instanceof Users) {
                $logService->logUpdate($user, 'Category', $category->getId(), "Category: {$category->getName()} (ID: {$category->getId()})");
            }

            $this->addFlash('success', 'Category updated successfully!');
            return $this->redirectToRoute('app_category_index');
        }

        return $this->render('category/edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_category_delete', methods: ['POST'])]
    public function delete(Category $category, Request $request, EntityManagerInterface $em, ActivityLogService $logService): Response
    {
        $user = $this->getUser();
        
        // Staff can only delete their own records, admin can delete all
        if ($user instanceof Users && !$this->isGranted('ROLE_ADMIN')) {
            if ($category->getCreatedBy() === null || $category->getCreatedBy()->getId() !== $user->getId()) {
                $this->addFlash('danger', 'Access denied. You can only delete your own records.');
                return $this->redirectToRoute('app_category_index');
            }
        }
        
        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
            $categoryId = $category->getId();
            $categoryName = $category->getName();
            
            $em->remove($category);
            $em->flush();

            if ($user instanceof Users) {
                $logService->logDelete($user, 'Category', $categoryId, "Category: {$categoryName} (ID: {$categoryId})");
            }

            $this->addFlash('success', 'Category deleted successfully!');
        }

        return $this->redirectToRoute('app_category_index');
    }
}


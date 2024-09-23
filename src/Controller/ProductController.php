<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/product')]
final class ProductController extends AbstractController
{
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $searchQuery = $request->query->get('searchProductQuery');  // Get the search query from the request

        $qb = $productRepository->createQueryBuilder('p');

        if ($searchQuery) {
            $orX = $qb->expr()->orX();
            $orX->add('p.name LIKE :query');
            $orX->add('p.description LIKE :query');
            $orX->add('p.price LIKE :query');
            $orX->add('p.quantity LIKE :query');

            $qb->where($orX)
                ->setParameter('query', "%{$searchQuery}%");
        } else {
            $qb->where('p.name != :name')
                ->setParameter('name', '');
        }

        $allProductsQuery = $qb->getQuery();

        // Paginate the results of the query
        $products = $paginator->paginate(
            // Doctrine Query, not results
            $allProductsQuery,
            // Define the page parameter
            $request->query->getInt('page', 1),
            // Items per page
            3
        );

        // Render the twig view
        return $this->render('product/index.html.twig', [
            'products' => $products,
            'searchProductQuery' => $searchQuery
        ]);
    }

    #[Route('/create', name: 'app_product_create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, data: $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $now = new \DateTime();
            $product->setCreatedAt($now);
            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/create.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $now = new \DateTime();
            $product->setUpdatedAt($now);

            $entityManager->flush();

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    public function importCsv(Request $request)
    {

    }

    public function exportCsv(Request $request)
    {

    }
}

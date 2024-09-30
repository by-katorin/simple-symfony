<?php

namespace App\Controller;

use League\Csv\Reader;
use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/product', name: 'app_product_')]
class ProductController extends AbstractController
{
    #[Route(name: 'index', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        $searchQuery = $request->query->get('searchProductQuery');
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

        $currentPage = $request->query->getInt('page', 1);
        $itemsPerPage = 3;

        //  Export
        if ($request->query->has('export')) {
            $offset = ($currentPage - 1) * $itemsPerPage;

            $paginatedProductsQuery = $qb->setFirstResult($offset)
                ->setMaxResults($itemsPerPage)
                ->getQuery();

            $products = $paginatedProductsQuery->getResult();
            return $this->exportProducts($products);
        }

        $paginatedProducts = $paginator->paginate(
            $allProductsQuery,
            $currentPage,
            $itemsPerPage,
        );

        return $this->render('product/index.html.twig', [
            'products' => $paginatedProducts,
            'searchProductQuery' => $searchQuery
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
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

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'PATCH'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product, [
            'method' => 'PATCH'
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $now = new \DateTime();
            $product->setUpdatedAt($now);
            $entityManager->flush();

            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->getPayload()->getString('_token'))) {
            try {
                $entityManager->remove($product);
                $entityManager->flush();
                $this->addFlash('success', "Product '{$product->getName()}' successfully deleted!");
            } catch (\Exception $e) {
                $this->addFlash('error', "Unable to delete '{$product->getName()}': {$e->getMessage()}");
            }
        }

        return $this->redirectToRoute('app_product_index');
    }

    #[Route('/csv/import', name: 'import', methods: ['POST'])]
    public function import(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->files->has('import_products')) {
            $csvFile = $request->files->get('import_products');

            if ($csvFile instanceof UploadedFile && $csvFile->isValid()) {
                try {
                    $this->importProducts($csvFile, $entityManager);
                    $this->addFlash('success', 'Products imported successfully!');
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            } else {
                $this->addFlash('error', 'Invalid CSV file uploaded.');
            }
        }

        return $this->redirectToRoute('app_product_index');
    }

    private function importProducts(UploadedFile $csvFile, EntityManagerInterface $entityManager)
    {
        try {
            $originalFilename = pathinfo($csvFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = transliterator_transliterate(
                'Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()',
                $originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $csvFile->guessExtension();
    
            $uploadsDirectory = $this->getParameter('kernel.project_dir') . $this->getParameter('app.uploads_directory');
    
            try {
                $csvFile->move($uploadsDirectory, $newFilename);
            } catch (IOException $e) {
                throw new \Exception('Error uploading CSV file: ' . $e->getMessage());
            }
    
            $csv = Reader::createFromPath("{$uploadsDirectory}/{$newFilename}", 'r');
            $csv->setHeaderOffset(0); // First row contains the header/columns
    
            foreach ($csv->getRecords() as $record) {
                $product = new Product();
                $product->setName($record['Product Name']);
                $product->setDescription($record['Description']);
                $product->setPrice($record['Price']);
                $product->setQuantity($record['Stock Quantity']);
                $record['Created']
                    ? $product->setCreatedAt($record['Created']->format('Y-m-d H:i:s'))
                    : $product->setCreatedAt(new \DateTime('now'));
    
                $entityManager->persist($product);
            }
    
            $entityManager->flush();
        } catch (\Exception $e) {
            throw new \Exception('Unable to store Product data from CSV file: ' . $e->getMessage());
        }
    }

    #[Route('/csv/download-template', name: 'download_csv_template', methods: ['GET'])]
    public function downloadCsvTemplate(): StreamedResponse
    {
        $fileName = '(Template) Import Products.csv';
        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename={$fileName}");

        $response->setCallback(function () {
            $handle = fopen('php://output', 'w+');

            // CSV header (columns)
            fputcsv($handle, ['Product Name', 'Description', 'Price', 'Stock Quantity', 'Created']);

            fclose($handle);
        });

        return $response;
    }

    private function exportProducts($products): StreamedResponse
    {
        $fileName = 'Exported Products.csv';
        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename={$fileName}");

        $response->setCallback(function () use ($products) {
            $handle = fopen('php://output', 'w+');

            // CSV header (columns)
            fputcsv($handle, ['Product Name', 'Description', 'Price', 'Stock Quantity', 'Created']);

            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->getName(),
                    $product->getDescription(),
                    $product->getPrice(),
                    $product->getQuantity(),
                    $product->getCreatedAt() ? $product->getCreatedAt()->format('Y-m-d H:i:s') : '-'
                ]);
            }

            fclose($handle);
        });

        return $response;
    }
}

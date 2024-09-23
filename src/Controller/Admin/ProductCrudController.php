<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Repository\ProductRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Doctrine\ORM\EntityManagerInterface;

use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function configureCrud(Crud $crud): Crud
    {
        // return parent::configureCrud($crud)
        //     ->overrideTemplate('crud/index', 'admin/product/index.html.twig');
        
        return $crud
            ->overrideTemplates([
                'crud/index' => 'admin/product/index.html.twig',
            ])
            ->showEntityActionsInlined()
            ;
            // ->setPageTitle('index', '%entity_label_singular% Management')
            // ->setPageTitle('detail', fn(Product $product) => sprintf('Viewing <b>%s</b>', $product->getName()))
            // ->setPageTitle('edit', fn(Product $product) => sprintf('Editing <b>%s</b>', $product->getName()))
            // ->setEntityLabelInSingular('Product')
            // ->setEntityLabelInPlural('Products')
            // ->setSearchFields(['name', 'description', 'price', 'stock', 'created_at'])
            // ->setDefaultSort(['created_at' => 'DESC']);
    }

    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore
    {
        $products = $this->entityManager->getRepository(Product::class);

        if (Crud::PAGE_INDEX === $responseParameters->get('pageName')) {
            $responseParameters->set('products', $products->findAll());
        }

        return $responseParameters;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_EDIT, Action::INDEX);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            'name',
            'description',
            'price',
            'quantity',
            DateTimeField::new('created_at')->onlyOnDetail(),
        ];
    }
}

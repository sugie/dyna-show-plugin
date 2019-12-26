<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\DynaShow\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\ProductListOrderBy;
use Eccube\Form\Type\AddCartType;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\Master\ProductListMaxRepository;
use Eccube\Repository\Master\ProductListOrderByRepository;
use Knp\Component\Pager\Paginator;
//use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Eccube\Repository\ProductRepository;

class DynaShowController extends AbstractController
{
    const DEFAULT_ORDER_BY_ID = 1;
    const DEFAULT_LIMIT_NUMBER = 20;
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var $productListOrderByRepository
     */
    private $productListOrderByRepository;

    /**
     * @var $productListMaxRepository
     */
    private $productListMaxRepository;

    /**
     * CouponSearchModelController constructor.
     *
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        ProductListOrderByRepository $productListOrderByRepository,
        ProductListMaxRepository $productListMaxRepository
    )
    {
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->productListOrderByRepository = $productListOrderByRepository;
        $this->productListMaxRepository = $productListMaxRepository;
    }

    /**
     * @param Request $request
     * @param int $page_no
     * @param Paginator $paginator
     * @Route("/dynashow")
     * @Route("/dynashow/page/{page_no}")
     * @Template("@DynaShow/default/dynashow_item.twig")
     */
    public function searchProduct(Request $request, $page_no = null, Paginator $paginator, ContainerInterface $container)
    {
        $itemPerPage = $request->request->get('limit');
        if (empty($itemPerPage)) $itemPerPage = self::DEFAULT_LIMIT_NUMBER;
        log_info('#dsc85: itemPerPage=' . $itemPerPage);
        $oderById = $request->request->get('orderby');
        if (is_null($oderById)) $oderById = self::DEFAULT_ORDER_BY_ID;
        $orderBy = $this->productListOrderByRepository->find($oderById);

        $productListMax = $this->productListMaxRepository->find(20);
        $name = $request->request->get('name');
        $searchData = [
            'mode' => null,
            "category_id" => null,
            'name' => $name,
            "pageno" => $page_no,
            "disp_number" => $productListMax,
            'orderby' => $orderBy,
        ];

        $qb = $this->productRepository->getQueryBuilderBySearchData($searchData);
        $query = $qb->getQuery()
            ->useResultCache(true, $this->eccubeConfig['eccube_result_cache_lifetime_short']);

        /** @var \Knp\Component\Pager\Pagination\SlidingPagination $pagination */
        $pagination = $paginator->paginate(
            $query,
            $page_no,
            $itemPerPage
        );

        $ids = [];
        foreach ($pagination as $Product) {
            $ids[] = $Product->getId();
        }
        $ProductsAndClassCategories = $this->productRepository->findProductsWithSortedClassCategories($ids, 'p.id');

        if (true) {
            // addCart form
            $forms = [];
            foreach ($pagination as $Product) {
                /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
                $builder = $this->formFactory->createNamedBuilder(
                    '',
                    AddCartType::class,
                    null,
                    [
                        'product' => $ProductsAndClassCategories[$Product->getId()],
                        'allow_extra_fields' => true,
                    ]
                );
                $addCartForm = $builder->getForm();

                $forms[$Product->getId()] = $addCartForm->createView();
            }
        } else {
            $forms = [];
        }

        $res = [
            'pagination' => $pagination,
            'forms' => $forms,
        ];

        return $res;
    }
}

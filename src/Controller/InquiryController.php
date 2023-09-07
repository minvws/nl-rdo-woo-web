<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Inquiry;
use App\Service\InquiryService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InquiryController extends AbstractController
{
    protected const MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        protected EntityManagerInterface $doctrine,
        protected Security $security,
        protected InquiryService $inquiryService,
        private readonly PaginatorInterface $paginator,
    ) {
    }

    #[Route('/inquiry/{token}', name: 'app_inquiry_detail', methods: ['GET'])]
    public function detail(Inquiry $inquiry, Request $request): Response
    {
        $this->inquiryService->saveInquiry($inquiry);

        // Split the documents by judgement for display purposes
        $publicDocs = [];
        $notPublicDocs = [];
        foreach ($inquiry->getDocuments() as $document) {
            if ($document->getJudgement()?->isAtLeastPartialPublic()) {
                $publicDocs[] = $document;
            } else {
                $notPublicDocs[] = $document;
            }
        }

        $publicPagination = $this->paginator->paginate(
            $publicDocs,
            $request->query->getInt('pu', 1),
            self::MAX_ITEMS_PER_PAGE,
            ['pageParameterName' => 'pu'],
        );
        $notPublicPagination = $this->paginator->paginate(
            $notPublicDocs,
            $request->query->getInt('pn', 1),
            self::MAX_ITEMS_PER_PAGE,
            ['pageParameterName' => 'pn'],
        );

        return $this->render('inquiry/index.html.twig', [
            'inquiry' => $inquiry,
            'public_docs' => $publicPagination,
            'not_public_docs' => $notPublicPagination,
        ]);
    }
}

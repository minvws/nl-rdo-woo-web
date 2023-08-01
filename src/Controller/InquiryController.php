<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Inquiry;
use App\Service\InquiryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InquiryController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $doctrine,
        protected Security $security,
        protected InquiryService $inquiryService
    ) {
    }

    #[Route('/inquiry/{token}', name: 'app_inquiry_detail', methods: ['GET'])]
    public function detail(Inquiry $inquiry): Response
    {
        $this->inquiryService->saveInquiry($inquiry);

        return $this->render('inquiry/index.html.twig', [
            'inquiry' => $inquiry,
        ]);
    }
}

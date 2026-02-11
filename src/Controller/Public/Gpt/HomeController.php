<?php

declare(strict_types=1);

namespace Shared\Controller\Public\Gpt;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function strval;

class HomeController extends AbstractController
{
    #[Route('/woo-gpt', name: 'app_gpt_home', methods: ['GET'], condition: "env('HAS_FEATURE_WOO_GPT') === 'true'")]
    public function index(Request $request): Response
    {
        $query = strval($request->query->get('q'));
        if (! $query) {
            return $this->render('public/gpt/home.html.twig');
        }

        return $this->render('public/gpt/answer.html.twig', [
            'query' => $query,
        ]);
    }
}

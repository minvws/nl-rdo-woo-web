<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\TemplateController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * This controller is used to render templates. It is used to render localized templates by replacing {locale} in the
 * template name with the current locale. Then it is passed to the TemplateController.
 */
class LocalizedTemplateController extends AbstractController
{
    protected ?Environment $twig;

    public function __construct(Environment $twig = null)
    {
        $this->twig = $twig;
    }

    /**
     * @param string[] $context
     */
    public function __invoke(
        Request $request,
        string $template,
        int $maxAge = null,
        int $sharedAge = null,
        bool $private = null,
        array $context = [],
        int $statusCode = 200
    ): Response {
        $locale = $request->getLocale();
        $localizedTemplate = str_replace('{locale}', $locale, $template);
        if (! is_string($localizedTemplate)) {
            $localizedTemplate = $template;
        }

        $controller = new TemplateController($this->twig);

        return $controller($localizedTemplate, $maxAge, $sharedAge, $private, $context, $statusCode);
    }
}

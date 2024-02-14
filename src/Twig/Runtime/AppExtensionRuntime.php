<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use App\Utils;
use Carbon\Carbon;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;

class AppExtensionRuntime implements RuntimeExtensionInterface
{
    protected string $projectPath;
    protected RequestStack $requestStack;

    public function __construct(string $projectPath, RequestStack $requestStack)
    {
        $this->projectPath = $projectPath;
        $this->requestStack = $requestStack;
    }

    public function basename(string $value): string
    {
        return basename($value);
    }

    public function size(string $value): string
    {
        return Utils::size($value);
    }

    public function carbon(\DateTimeInterface|string|null $value): Carbon
    {
        return Carbon::parse($value);
    }

    public function getChoiceAttribute(FormView $choiceView, string $attribute): ?string
    {
        $choiceAttr = $choiceView->vars['choice_attr'] ?? [];

        return $choiceAttr[$choiceView->vars['value']][$attribute] ?? null;
    }

    /**
     * @return array<string, string>|mixed[]
     */
    public function appVersion(): array
    {
        $json = file_get_contents($this->projectPath . '/public/version.json');
        if ($json === false) {
            return [];
        }

        /** @var array<string,string> $version */
        $version = json_decode($json, true);

        return is_array($version) ? $version : [];
    }

    /**
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function dieTwig(): void
    {
        exit;
    }

    public function isInstanceOf(mixed $var, string $instance): bool
    {
        return $var instanceof $instance;
    }

    public function isBackend(): bool
    {
        $request = $this->requestStack->getCurrentRequest() ?? new Request();

        return str_starts_with($request->getPathInfo(), '/balie');
    }
}

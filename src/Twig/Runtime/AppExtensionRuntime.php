<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use Carbon\Carbon;
use Symfony\Component\Form\FormView;
use Twig\Extension\RuntimeExtensionInterface;

class AppExtensionRuntime implements RuntimeExtensionInterface
{
    protected string $projectPath;

    public function __construct(string $projectPath)
    {
        $this->projectPath = $projectPath;
    }

    public function basename(string $value): string
    {
        return basename($value);
    }

    public function size(string $value): string
    {
        $value = (int) $value;

        if ($value < 1024) {
            return $value . ' bytes';
        } elseif ($value < 1048576) {
            return round($value / 1024, 2) . ' KB';
        } elseif ($value < 1073741824) {
            return round($value / 1048576, 2) . ' MB';
        } else {
            return round($value / 1073741824, 2) . ' GB';
        }
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
    public function appVersion()
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
}

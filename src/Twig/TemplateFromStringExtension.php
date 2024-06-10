<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 *
 */
class TemplateFromStringExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('template_from_string', [$this, 'templateFromString'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    public function templateFromString($environment, $templateCode)
    {
        $template = $environment->createTemplate($templateCode);
        return $template->render();
    }
}

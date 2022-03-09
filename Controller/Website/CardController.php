<?php

declare(strict_types=1);

namespace Pixel\DirectoryBundle\Controller\Website;

use Pixel\DirectoryBundle\Entity\Card;
use Sulu\Bundle\PreviewBundle\Preview\Preview;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\WebsiteBundle\Resolver\TemplateAttributeResolverInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class CardController extends AbstractController
{
    /**
     * @return string[]
     */
    public static function getSubscribedServices()
    {
        $subscribedServices = parent::getSubscribedServices();

        $subscribedServices['sulu_core.webspace.webspace_manager'] = WebspaceManagerInterface::class;
        $subscribedServices['sulu.repository.route'] = RouteRepositoryInterface::class;
        $subscribedServices['sulu_website.resolver.template_attribute'] = TemplateAttributeResolverInterface::class;

        return $subscribedServices;
    }

    public function indexAction(Card $card, $attributes = [], $preview = false, $partial = false): Response
    {


        $parameters = $this->get('sulu_website.resolver.template_attribute')->resolve([
            'card' => $card,
            'localizations' => $this->getLocalizationsArrayForEntity($card),
        ]);


        if ($partial) {
            $content = $this->renderBlock(
                '@Directory/card.html.twig',
                'content',
                $parameters
            );
        } else if ($preview) {
            $content = $this->renderPreview(
                '@Directory/card.html.twig',
                $parameters
            );
        } else {
            if (!$card->getIsActive()) throw $this->createNotFoundException();
            $content = $this->renderView(
                '@Directory/card.html.twig',
                $parameters
            );
        }

        return new Response($content);
    }

    /**
     * @return array<string, array>
     */
    protected function getLocalizationsArrayForEntity(Card $entity): array
    {
        $routes = $this->get('sulu.repository.route')->findAllByEntity(Card::class, (string)$entity->getId());

        $localizations = [];
        foreach ($routes as $route) {
            $url = $this->get('sulu_core.webspace.webspace_manager')->findUrlByResourceLocator(
                $route->getPath(),
                null,
                $route->getLocale()
            );

            $localizations[$route->getLocale()] = ['locale' => $route->getLocale(), 'url' => $url];
        }

        return $localizations;
    }

    /**
     * Returns rendered part of template specified by block.
     *
     * @param mixed $template
     * @param mixed $block
     * @param mixed $attributes
     */
    protected function renderBlock($template, $block, $attributes = [])
    {
        $twig = $this->get('twig');
        $attributes = $twig->mergeGlobals($attributes);

        $template = $twig->load($template);

        $level = ob_get_level();
        ob_start();

        try {
            $rendered = $template->renderBlock($block, $attributes);
            ob_end_clean();

            return $rendered;
        }
        catch (\Exception $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }
    }

    protected function renderPreview(string $view, array $parameters = []): string
    {
        $parameters['previewParentTemplate'] = $view;
        $parameters['previewContentReplacer'] = Preview::CONTENT_REPLACER;
        //$album = $parameters['album'];

        return $this->renderView('@SuluWebsite/Preview/preview.html.twig', $parameters);
    }
}
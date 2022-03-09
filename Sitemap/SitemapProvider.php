<?php

declare(strict_types=1);

namespace Pixel\DirectoryBundle\Sitemap;

use Pixel\DirectoryBundle\Repository\CardRepository;
use Sulu\Bundle\WebsiteBundle\Sitemap\Sitemap;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class SitemapProvider implements SitemapProviderInterface
{
    /**
     * @var CardRepository
     */
    private CardRepository $cardRepository;
    private WebspaceManagerInterface $webspaceManager;
    private array $locales = [];

    /**
     * @param CardRepository $cardRepository
     */
    public function __construct(CardRepository $cardRepository, WebspaceManagerInterface $webspaceManager)
    {
        $this->cardRepository = $cardRepository;
        $this->webspaceManager = $webspaceManager;
    }

    public function build($page, $scheme, $host): array
    {

        $locale = $this->getLocaleByHost($host);
        $result = [];
        foreach ($this->cardRepository->findAllForSitemap((int)$page, (int)self::PAGE_SIZE) as $card) {
            //$card->setLocale($locale);
            $result[] = new SitemapUrl(
                $scheme . '://' . $host . $card->getRoutePath(),
                $card->getLocale(),
                $card->getLocale(),
                new \DateTime()
            );
        }

        return $result;
    }

    private function getLocaleByHost($host)
    {
        if (!\array_key_exists($host, $this->locales)) {
            $portalInformation = $this->webspaceManager->getPortalInformations();
            foreach ($portalInformation as $hostName => $portal) {
                if ($hostName === $host) {
                    $this->locales[$host] = $portal->getLocale();
                }
            }
        }
        if (isset($this->locales[$host])) return $this->locales[$host];
    }

    public function createSitemap($scheme, $host): Sitemap
    {
        return new Sitemap($this->getAlias(), $this->getMaxPage($scheme, $host));
    }

    public function getAlias(): string
    {
        return 'directory';
    }

    public function getMaxPage($scheme, $host)
    {
        return ceil($this->cardRepository->countForSitemap() / self::PAGE_SIZE);
    }
}

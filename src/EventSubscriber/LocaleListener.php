<?php

declare(strict_types=1);

namespace Shared\EventSubscriber;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use function in_array;
use function strval;

/**
 * This listener will set the locale based on the session or the _locale query parameter.
 */
class LocaleListener
{
    protected const LOCALE_KEY = '_locale';

    /**
     * @param list<string> $allowedLocales
     */
    public function __construct(protected array $allowedLocales, protected string $defaultLocale)
    {
    }

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $locale = strval($request->query->get(self::LOCALE_KEY));
        if ($locale) {
            // If _locale is given on the query string, set the locale
            $locale = in_array($locale, $this->allowedLocales) ? $locale : $this->defaultLocale;
            $request->getSession()->set(self::LOCALE_KEY, $locale);

            $request->setLocale($locale);
        } else {
            // Don't fetch the session if it's not started yet
            if ($request->hasSession(true) === false) {
                return;
            }

            // Try and fetch the locale from the session, otherwise use the default locale if none is found
            $request->setLocale(strval($request->getSession()->get(self::LOCALE_KEY, $this->defaultLocale)));
        }
    }
}

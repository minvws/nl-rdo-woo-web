<?php

// Kernel::getCacheDir() nests the cache per tenant and application, and Kernel::getContainerClass()
// names the container after both, so there is one preload file per tenant/application combination
// rather than the single var/cache/prod/<Kernel>Container.preload.php the Symfony default assumes.
foreach (glob(dirname(__DIR__) . '/var/cache/*/*/prod/*.preload.php') ?: [] as $file) {
    require $file;
}

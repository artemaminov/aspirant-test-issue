<?php
use Doctrine\ORM\Tools\Console\ConsoleRunner;

// replace with file to your own project bootstrap
require_once 'bootstrap.php';

$entityManager = $container->get('Doctrine\ORM\EntityManager');

return ConsoleRunner::createHelperSet($entityManager);
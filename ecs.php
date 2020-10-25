<?php

/**
 * Rules for Nette Coding Standard
 * https://github.com/nette/coding-standard
 */

declare(strict_types=1);


return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$containerConfigurator->import(PRESET_DIR . '/php71.php');

	$parameters = $containerConfigurator->parameters();

	$parameters->set('skip', [
		// use function
		PhpCsFixer\Fixer\Import\SingleImportPerStatementFixer::class => ['src/Routing/Route.php'],
		PhpCsFixer\Fixer\Import\OrderedImportsFixer::class => ['src/Routing/Route.php'],
	]);
};

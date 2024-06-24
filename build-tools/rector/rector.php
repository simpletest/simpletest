<?php

/**
 * Rector
 *
 * https://getrector.com/documentation/
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\Set\ValueObject\LevelSetList;

$workspace_root = dirname(__DIR__, 2);

return RectorConfig::configure()
    ->withPaths([
        $workspace_root . '/src',
        $workspace_root . '/tests',
        $workspace_root . '/extensions',
    ])
    ->withSkipPath('tests/test_with_parse_error.php')
    ->withParallel(120,16,10)
    ->withCache($workspace_root . '/build/cache/rector')
    ->withSets(
        [
            # === Current Refactoring Run ===
            #SetList::DEAD_CODE,

            # === Passed Refactoring Runs ===

            # Level Up To X
            # -------------------------------
            #LevelSetList::UP_TO_PHP_84,

            # PHP
            # -------------------------------
            #SetList::PHP_84
            #SetList::PHP_83
            #SetList::PHP_82
            #SetList::PHP_81
            #SetList::PHP_80
            #SetList::PHP_74
            #SetList::PHP_73
            #SetList::PHP_72
            #SetList::PHP_71
            #SetList::PHP_70
            #SetList::PHP_56
            #SetList::PHP_55
            #SetList::PHP_54

            # Additional Sets
            # -------------------------------
            #SetList::STRICT_BOOLEANS,
            SetList::CODE_QUALITY
        ]
    )
    ->withSkip([
        // from SetList::STRICT_BOOLEANS
        Rector\Strict\Rector\If_\BooleanInIfConditionRuleFixerRector::class,
        Rector\Strict\Rector\BooleanNot\BooleanInBooleanNotRuleFixerRector::class,
        // from SetList::CODE_QUALITY
        Rector\CodeQuality\Rector\If_\CombineIfRector::class,
        Rector\CodeQuality\Rector\Concat\JoinStringConcatRector::class,
        Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector::class,
        Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector::class => [
            $workspace_root . '/tests/mock_objects_test.php',
        ],
        Rector\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector::class => [
            $workspace_root . '/src/php_parser.php',
        ]
    ])
    ->withPHPStanConfigs([dirname(__DIR__) . '/phpstan/phpstan.neon.dist'])
    ;

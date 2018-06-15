<?php

/*
 * This file is part of the Fxp RequireAssetBundle package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Bundle\RequireAssetBundle\DependencyInjection;

use Fxp\Component\RequireAsset\Config\AssetReplacementConfiguration;
use Fxp\Component\RequireAsset\Config\CommonAssetConfiguration;
use Fxp\Component\RequireAsset\Config\FileExtensionConfiguration;
use Fxp\Component\RequireAsset\Config\LocaleConfiguration;
use Fxp\Component\RequireAsset\Config\PackageConfiguration;
use Fxp\Component\RequireAsset\Config\PatternConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @var string
     */
    protected $projectDir;

    /**
     * @var string
     */
    protected $defaultLocale;

    /**
     * Constructor.
     *
     * @param string $projectDir
     * @param string $defaultLocale
     */
    public function __construct($projectDir, $defaultLocale)
    {
        $this->projectDir = $projectDir;
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('fxp_require_asset');

        $rootNode
            ->children()
                ->booleanNode('twig')->defaultValue(class_exists('Twig_Environment'))->end()
                ->booleanNode('assetic')->defaultValue(class_exists('Assetic\AssetManager'))->end()
                ->scalarNode('output_prefix')->defaultValue('assets')->end()
                ->scalarNode('output_prefix_debug')->defaultValue('assets-dev')->end()
                ->scalarNode('composer_installed_path')
                    ->defaultValue($this->projectDir.'/vendor/composer/installed.json')
                    ->end()
                ->booleanNode('native_bower')->defaultTrue()->end()
                ->booleanNode('native_npm')->defaultTrue()->end()
                ->scalarNode('base_dir')->defaultValue($this->projectDir)->end()
                ->scalarNode('default_locale')->defaultValue($this->defaultLocale)->end()
                ->scalarNode('fallback_locale')->defaultNull()->end()
                ->booleanNode('auto_configuration')->defaultTrue()->end()
                ->scalarNode('less_assetic_filter')->defaultValue('less')->end()
                ->append($this->getWebpackNode())
            ->end()
        ;
        $this->appendGlobalConfig($rootNode);

        return $treeBuilder;
    }

    /**
     * Append global config.
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function appendGlobalConfig(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->append($this->getDefaultForPackageNode())
            ->append($this->getOutputRewritesNode())
            ->append(PackageConfiguration::getNodeDefinition())
            ->append(AssetReplacementConfiguration::getNodeDefinition())
            ->append(LocaleConfiguration::getNodeDefinition())
            ->append(CommonAssetConfiguration::getNodeDefinition())
        ;
    }

    /**
     * Get default config node for package.
     *
     * @return NodeDefinition
     */
    private function getDefaultForPackageNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('default');

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('replace_extensions')->defaultFalse()->end()
                ->append(FileExtensionConfiguration::getNodeDefinition())
                ->append(PatternConfiguration::getNodeDefinition())
            ->end()
        ;

        return $node;
    }

    /**
     * Get output rewrites config node.
     *
     * @return NodeDefinition
     */
    private function getOutputRewritesNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('output_rewrites');

        $node
            ->useAttributeAsKey('name')
            ->normalizeKeys(false)
            ->prototype('variable')->end()
        ;

        return $node;
    }

    /**
     * Get webpack config node.
     *
     * @return NodeDefinition
     */
    private function getWebpackNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('webpack');

        $node
            ->canBeDisabled()
            ->addDefaultsIfNotSet()
            ->children()
                ->enumNode('adapter')
                    ->values(['auto', 'manifest', 'assets'])
                    ->defaultValue('auto')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('manifest_adapter')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('file')->defaultNull()->end()
                    ->end()
                ->end()
                ->arrayNode('assets_adapter')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('file')->defaultValue($this->projectDir.'/assets.json')->end()
                        ->arrayNode('cache')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')->defaultValue(null)->end()
                                ->booleanNode('key')->defaultValue('fxp_require_asset_webpack_assets')->end()
                                ->scalarNode('service_id')->defaultValue('cache.app')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}

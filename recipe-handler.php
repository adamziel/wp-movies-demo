<?php

class RecipeHandler
{

    static public function register($recipe)
    {
        $handler = new RecipeHandler($recipe);
        $handler->register_taxonomies();
        $handler->register_post_types();
        $handler->recipe_auto_register_block_types();
        $handler->register_scripts();
        $handler->register_styles();
        $handler->install_themes();
        $handler->install_plugins();
        $handler->import_wxrs();
    }

    public function __construct($recipe)
    {
        $this->recipe = $recipe;
    }

    public function register_taxonomies()
    {
        $translation_domain = $this->recipe['translationDomain'];

        foreach ($this->recipe['taxonomies'] as $slug => $taxonomy) {
            $object_type = $taxonomy['objectType'];
            unset($taxonomy['objectType']);
            if (isset($taxonomy['labels']) && is_array($taxonomy['labels'])) {
                foreach ($taxonomy['labels'] as $key => $label) {
                    $taxonomy['labels'][$key] = esc_html__($label, $translation_domain);
                }
            }
            if (isset($taxonomy['label']) && is_string($taxonomy['label'])) {
                $taxonomy['label'] = esc_html__($taxonomy['label'], $translation_domain);
            }
            register_taxonomy($slug, $object_type, $taxonomy);
        }
    }

    public function register_post_types()
    {
        $translation_domain = $this->recipe['translationDomain'];

        foreach ($this->recipe['postTypes'] as $slug => $post_type) {
            if (isset($post_type['labels']) && is_array($post_type['labels'])) {
                foreach ($post_type['labels'] as $key => $label) {
                    $post_type['labels'][$key] = esc_html__($label, $translation_domain);
                }
            }
            if (isset($post_type['label']) && is_string($post_type['label'])) {
                $post_type['label'] = esc_html__($post_type['label'], $translation_domain);
            }
            register_post_type($slug, $post_type);
        }
    }

    function recipe_auto_register_block_types()
    {
        foreach ($this->recipe['blocks'] as $block_glob) {
            if (substr($block_glob, 0, 1) !== '/') {
                $block_glob = __DIR__ . '/' . $block_glob;
            }

            $results = glob($block_glob);
            foreach ($results as $block_path) {
                if (str_ends_with($block_path, 'block.json')) {
                    $block_path = dirname($block_path);
                }
                register_block_type($block_path);
            }
        }
    }

    function register_scripts()
    {
        if (!isset($this->recipe['scripts'])) {
            return;
        }
        foreach ($this->recipe['scripts'] as $script) {
            wp_register_script(
                $script['handle'],
                $script['src'],
                $script['deps'] ?? array(),
                $script['version'] ?? null,
                $script['in_footer'] ?? false
            );
            if (isset($script['enqueue_on_hooks'])) {
                foreach ($script['enqueue_on_hooks'] as $hook) {
                    add_action($hook, function () use ($script) {
                        wp_enqueue_script($script['handle']);
                    });
                }
            }
        }
    }

    function register_styles()
    {
        if (!isset($this->recipe['styles'])) {
            return;
        }
        foreach ($this->recipe['styles'] as $style) {
            wp_register_style(
                $style['handle'],
                $style['src'],
                $style['deps'] ?? array(),
                $style['version'] ?? null,
                $style['media'] ?? 'all'
            );
            if (isset($style['enqueue_on_hooks'])) {
                foreach ($style['enqueue_on_hooks'] as $hook) {
                    add_action($hook, function () use ($style) {
                        wp_enqueue_style($style['handle']);
                    });
                }
            }
        }
    }

    function install_themes()
    {
        if (!isset($this->recipe['themes'])) {
            return;
        }
        foreach ($this->recipe['themes'] as $theme_recipe) {
            if (!is_array($theme_recipe)) {
                $theme_recipe = array('name' => $theme_recipe, 'activate' => false);
            }
            $theme = wp_get_theme($theme_recipe['path']);
            if (!$theme->exists()) {
                wp_die("Theme {$theme_recipe['path']} does not exist");
            }
            if (!$theme->is_installed()) {
                $theme->install();
            }
            if ($theme_recipe['activate']) {
                if (!$theme->is_active()) {
                    $theme->activate();
                }
            }
        }
    }

    function install_plugins()
    {
        // @TODO
    }

    function import_wxrs()
    {
        // @TODO
    }
}

add_action('init', function () {
    $recipe = json_decode(file_get_contents(__DIR__ . '/recipe.json'), true);
    RecipeHandler::register($recipe);
});

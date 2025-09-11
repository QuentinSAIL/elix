<?php

return [
    /**
     * Mapping elements to class names for styling with Tailwind CSS
     */
    'classes' => [
        // Headings
        'h1' => 'mt-6 mb-4 text-3xl md:text-4xl font-bold tracking-tight
                 border-b border-slate-200 dark:border-slate-700 pb-2
                 text-slate-900 dark:text-slate-100',
        'h2' => 'mt-8 mb-3 text-2xl md:text-3xl font-semibold
                 border-b border-slate-200 dark:border-slate-700 pb-1
                 text-slate-900 dark:text-slate-100',
        'h3' => 'mt-6 mb-2 text-xl md:text-2xl font-semibold
                 text-slate-900 dark:text-slate-100',
        'h4' => 'mt-5 mb-2 text-lg md:text-xl font-semibold
                 text-slate-900 dark:text-slate-100',
        'h5' => 'mt-4 mb-2 text-base font-semibold
                 text-slate-900 dark:text-slate-100',
        'h6' => 'mt-4 mb-2 text-sm font-semibold
                 text-slate-900 dark:text-slate-100',

        // Text & links
        'p'   => 'my-4 leading-7 text-slate-800 dark:text-slate-200',
        'a'   => 'text-blue-600 dark:text-blue-400 underline underline-offset-2
                  decoration-blue-300 dark:decoration-blue-500 hover:decoration-2',

        // Lists
        'ul'     => 'my-3 ms-6 list-disc space-y-1 marker:text-slate-400 dark:marker:text-slate-500',
        'ol'     => 'my-3 ms-6 list-decimal space-y-1 marker:text-slate-400 dark:marker:text-slate-500',
        'li'     => 'text-slate-800 dark:text-slate-200',
        'ul ul'  => 'ms-6 list-disc',
        'ol ol'  => 'ms-6 list-decimal',

        // Code (inline & blocks)
        'code'       => 'px-1.5 py-0.5 rounded-md font-mono text-sm
                         bg-slate-100 text-slate-800 border border-slate-200
                         dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700',
        'inline-code'=> 'px-1.5 py-0.5 rounded-md font-mono text-sm
                         bg-slate-100 text-slate-800 border border-slate-200
                         dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700',
        'pre'        => 'relative my-4 rounded-xl overflow-x-auto shadow-sm
                         border border-slate-200 bg-slate-50
                         dark:border-slate-700 dark:bg-slate-900',
        'pre code'   => 'block p-4 text-sm leading-6 font-mono bg-transparent
                         text-slate-800 dark:text-slate-200',

        // Avoid colored backgrounds on syntax-highlight spans by default
        'code span'  => 'bg-transparent',

        // Emphasis
        'strong' => 'font-semibold text-slate-900 dark:text-slate-100',
        'em'     => 'italic',

        // Images
        'img' => 'my-4 max-w-full h-auto rounded-lg shadow-sm
                  ring-1 ring-slate-200 dark:ring-slate-700',

        // Blockquote
        'blockquote' => 'my-4 border-l-4 pl-4 pr-2 italic
                         border-slate-300 bg-slate-50 text-slate-700
                         dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300
                         rounded-r-lg',

        'hr' => 'my-8 border-t border-slate-200 dark:border-slate-700',

        // Tables
        'table' => 'my-6 w-full border-collapse rounded-lg overflow-hidden',
        'thead' => '',
        'tbody' => '',
        'th' => 'px-4 py-2 text-left font-semibold
                 bg-slate-100 text-slate-700 border-b border-slate-200
                 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700',
        'td' => 'px-4 py-2 text-slate-800 border-b border-slate-200
                 dark:text-slate-200 dark:border-slate-700',
        'tr' => 'odd:bg-slate-50 hover:bg-slate-100
                 dark:odd:bg-slate-900 dark:hover:bg-slate-800',
    ],

    /**
     * Options for CommonMark parser
     */
    'commonmark' => [
        'renderer' => [
            'block_separator' => "\n",
            'inner_separator' => "\n",
            'soft_break'      => "\n",
        ],

        'commonmark' => [
            'enable_em'               => true,
            'enable_strong'           => true,
            'use_asterisk'            => true,
            'use_underscore'          => true,
            'unordered_list_markers'  => ['-', '+', '*'],
        ],

        'html_input'         => 'strip',
        'allow_unsafe_links' => true,
        'max_nesting_level'  => PHP_INT_MAX,
    ],

    'commonmark_extensions' => [
        League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension::class, // Required
        League\CommonMark\Extension\Autolink\AutolinkExtension::class,
        League\CommonMark\Extension\Strikethrough\StrikethroughExtension::class,
    ],

    'code_highlight' => [
        'enabled'  => false,
        'theme'    => 'github',
        'languages'=> ['javascript','php','css'],
    ],

    'links' => [
        'enabled'        => true,
        'elements'       => ['h2', 'h3', 'h4'],
        'slug_delimiter' => '-',
        'add_anchor'     => true,
        'position'       => 'prepend',
    ],
];

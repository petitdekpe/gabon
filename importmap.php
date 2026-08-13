<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '8.0.23',
    ],
    '@material/web/button/filled-button.js' => [
        'version' => '2.5.0',
    ],
    'tslib' => [
        'version' => '2.8.1',
    ],
    'lit/decorators.js' => [
        'version' => '3.3.3',
    ],
    'lit' => [
        'version' => '3.3.3',
    ],
    'lit/directives/class-map.js' => [
        'version' => '3.3.3',
    ],
    '@lit/reactive-element/decorators/custom-element.js' => [
        'version' => '2.1.2',
    ],
    '@lit/reactive-element/decorators/property.js' => [
        'version' => '2.1.2',
    ],
    '@lit/reactive-element/decorators/state.js' => [
        'version' => '2.1.2',
    ],
    '@lit/reactive-element/decorators/event-options.js' => [
        'version' => '2.1.2',
    ],
    '@lit/reactive-element/decorators/query.js' => [
        'version' => '2.1.2',
    ],
    '@lit/reactive-element/decorators/query-all.js' => [
        'version' => '2.1.2',
    ],
    '@lit/reactive-element/decorators/query-async.js' => [
        'version' => '2.1.2',
    ],
    '@lit/reactive-element/decorators/query-assigned-elements.js' => [
        'version' => '2.1.2',
    ],
    '@lit/reactive-element/decorators/query-assigned-nodes.js' => [
        'version' => '2.1.2',
    ],
    '@lit/reactive-element' => [
        'version' => '2.1.2',
    ],
    'lit-html' => [
        'version' => '3.3.2',
    ],
    'lit-element/lit-element.js' => [
        'version' => '4.2.2',
    ],
    'lit-html/is-server.js' => [
        'version' => '3.3.2',
    ],
    'lit-html/directives/class-map.js' => [
        'version' => '3.3.2',
    ],
    '@material/web/button/outlined-button.js' => [
        'version' => '2.5.0',
    ],
    '@material/web/button/text-button.js' => [
        'version' => '2.5.0',
    ],
    '@material/web/textfield/outlined-text-field.js' => [
        'version' => '2.5.0',
    ],
    '@material/web/textfield/filled-text-field.js' => [
        'version' => '2.5.0',
    ],
    '@material/web/select/outlined-select.js' => [
        'version' => '2.5.0',
    ],
    '@material/web/select/select-option.js' => [
        'version' => '2.5.0',
    ],
    '@material/web/checkbox/checkbox.js' => [
        'version' => '2.5.0',
    ],
    '@material/web/radio/radio.js' => [
        'version' => '2.5.0',
    ],
    '@material/web/labs/card/outlined-card.js' => [
        'version' => '2.5.0',
    ],
    '@material/web/labs/card/filled-card.js' => [
        'version' => '2.5.0',
    ],
    '@material/web/fab/fab.js' => [
        'version' => '2.5.0',
    ],
    '@material/web/progress/linear-progress.js' => [
        'version' => '2.5.0',
    ],
    '@material/web/iconbutton/icon-button.js' => [
        'version' => '2.5.0',
    ],
    '@material/web/icon/icon.js' => [
        'version' => '2.5.0',
    ],
    'lit/static-html.js' => [
        'version' => '3.3.3',
    ],
    'lit/directives/live.js' => [
        'version' => '3.3.3',
    ],
    'lit/directives/style-map.js' => [
        'version' => '3.3.3',
    ],
    'lit-html/static.js' => [
        'version' => '3.3.2',
    ],
    'lit-html/directives/live.js' => [
        'version' => '3.3.2',
    ],
    'lit-html/directives/style-map.js' => [
        'version' => '3.3.2',
    ],
    '@material/web/dialog/dialog.js' => [
        'version' => '2.5.0',
    ],
    '@material/web/list/list.js' => [
        'version' => '2.5.0',
    ],
    '@material/web/list/list-item.js' => [
        'version' => '2.5.0',
    ],
    '@material/web/divider/divider.js' => [
        'version' => '2.5.0',
    ],
    'libphonenumber-js' => [
        'version' => '1.13.10',
    ],
];

/**
 * Bundled by jsDelivr using Rollup v4.62.2 and esbuild v0.28.1.
 * Original file: /npm/@material/web@2.5.0/divider/divider.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
import{__decorate as t}from"tslib";import{property as i,customElement as n}from"lit/decorators.js";import{LitElement as d,css as a}from"lit";class e extends d{constructor(){super(...arguments),this.inset=!1,this.insetStart=!1,this.insetEnd=!1}}t([i({type:Boolean,reflect:!0})],e.prototype,"inset",void 0),t([i({type:Boolean,reflect:!0,attribute:"inset-start"})],e.prototype,"insetStart",void 0),t([i({type:Boolean,reflect:!0,attribute:"inset-end"})],e.prototype,"insetEnd",void 0);const r=a`:host{box-sizing:border-box;color:var(--md-divider-color, var(--md-sys-color-outline-variant, #cac4d0));display:flex;height:var(--md-divider-thickness, 1px);width:100%}:host([inset]),:host([inset-start]){padding-inline-start:16px}:host([inset]),:host([inset-end]){padding-inline-end:16px}:host::before{background:currentColor;content:"";height:100%;width:100%}@media(forced-colors: active){:host::before{background:CanvasText}}
`;r.styleSheet;let o=class extends e{};o.styles=[r],o=t([n("md-divider")],o);export{o as MdDivider};

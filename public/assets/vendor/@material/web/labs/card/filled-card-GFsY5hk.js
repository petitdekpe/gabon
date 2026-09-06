/**
 * Bundled by jsDelivr using Rollup v4.62.2 and esbuild v0.28.1.
 * Original file: /npm/@material/web@2.5.0/labs/card/filled-card.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
import{__decorate as l}from"tslib";import{customElement as t}from"lit/decorators.js";import{LitElement as n,html as i,css as o}from"lit";class v extends n{connectedCallback(){super.connectedCallback(),this.setAttribute("aria-hidden","true")}render(){return i`<span class="shadow"></span>`}}const s=o`:host,.shadow,.shadow::before,.shadow::after{border-radius:inherit;inset:0;position:absolute;transition-duration:inherit;transition-property:inherit;transition-timing-function:inherit}:host{display:flex;pointer-events:none;transition-property:box-shadow,opacity}.shadow::before,.shadow::after{content:"";transition-property:box-shadow,opacity;--_level: var(--md-elevation-level, 0);--_shadow-color: var(--md-elevation-shadow-color, var(--md-sys-color-shadow, #000))}.shadow::before{box-shadow:0px calc(1px*(clamp(0,var(--_level),1) + clamp(0,var(--_level) - 3,1) + 2*clamp(0,var(--_level) - 4,1))) calc(1px*(2*clamp(0,var(--_level),1) + clamp(0,var(--_level) - 2,1) + clamp(0,var(--_level) - 4,1))) 0px var(--_shadow-color);opacity:.3}.shadow::after{box-shadow:0px calc(1px*(clamp(0,var(--_level),1) + clamp(0,var(--_level) - 1,1) + 2*clamp(0,var(--_level) - 2,3))) calc(1px*(3*clamp(0,var(--_level),2) + 2*clamp(0,var(--_level) - 2,3))) calc(1px*(clamp(0,var(--_level),4) + 2*clamp(0,var(--_level) - 4,1))) var(--_shadow-color);opacity:.15}
`;s.styleSheet;let r=class extends v{};r.styles=[s],r=l([t("md-elevation")],r);class p extends n{render(){return i`
      <md-elevation part="elevation"></md-elevation>
      <div class="background"></div>
      <slot></slot>
      <div class="outline"></div>
    `}}const d=o`:host{--_container-color: var(--md-filled-card-container-color, var(--md-sys-color-surface-container-highest, #e6e0e9));--_container-elevation: var(--md-filled-card-container-elevation, 0);--_container-shadow-color: var(--md-filled-card-container-shadow-color, var(--md-sys-color-shadow, #000));--_container-shape: var(--md-filled-card-container-shape, var(--md-sys-shape-corner-medium, 12px))}
`;d.styleSheet;const c=o`:host{border-radius:var(--_container-shape);box-sizing:border-box;display:flex;flex-direction:column;position:relative;z-index:0}md-elevation,.background,.outline{border-radius:inherit;inset:0;pointer-events:none;position:absolute}.background{background:var(--_container-color);z-index:-1}.outline{border:1px solid rgba(0,0,0,0);z-index:1}md-elevation{z-index:-1;--md-elevation-level: var(--_container-elevation);--md-elevation-shadow-color: var(--_container-shadow-color)}slot{border-radius:inherit}
`;c.styleSheet;let e=class extends p{};e.styles=[c,d],e=l([t("md-filled-card")],e);export{e as MdFilledCard};

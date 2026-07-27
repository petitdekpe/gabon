// Each @material/web component is fetched as its own self-contained bundle
// (via jsDelivr's per-file +esm resolver used by AssetMapper's importmap:require),
// so shared internal elements (md-focus-ring, md-ripple, md-elevation…) are
// duplicated across bundles instead of being deduplicated as a single module.
// The first bundle to load registers them; every later bundle would otherwise
// throw on customElements.define and abort its own component's registration.
// This guard makes re-registration a no-op instead of a fatal error.
//
// Must be imported before any @material/web module — ES module imports in a
// file all evaluate before that file's own top-level code runs, so this lives
// in its own module and is imported first.
const nativeDefine = customElements.define.bind(customElements);
customElements.define = (name, constructor, options) => {
    if (customElements.get(name)) {
        return;
    }
    nativeDefine(name, constructor, options);
};

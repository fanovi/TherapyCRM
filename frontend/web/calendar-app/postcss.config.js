export default {
  plugins: {
    tailwindcss: {},
    autoprefixer: {},
    "postcss-prefix-selector": {
      prefix: ".calendar-app-wrapper",
      transform: function (prefix, selector, prefixedSelector) {
        // Trasforma :root in .calendar-app-wrapper
        if (selector === ":root") {
          return ".calendar-app-wrapper";
        }

        // Ignora completamente html e body
        if (
          selector === "html" ||
          selector === "body" ||
          selector.includes(":host")
        ) {
          return selector;
        }

        // TEMP FIX: Ignora componenti Dialog/Modal per evitare problemi portal
        if (
          selector.includes("[data-radix") ||
          selector.includes(".radix-") ||
          selector.includes("[data-state=") ||
          selector.includes("[data-dialog") ||
          selector.includes(".dialog") ||
          selector.includes(".overlay")
        ) {
          return selector;
        }

        // Per tutti gli altri selettori, usa il comportamento predefinito
        return prefixedSelector;
      },
    },
  },
};

// Rileva automaticamente la modalità
const isStandalone = process.env.NODE_ENV === "development";

console.log(
  `📦 PostCSS Config: ${isStandalone ? "STANDALONE" : "COMPILED"} mode`
);
console.log(
  `📦 Prefix selector: ${
    isStandalone ? "DISABLED" : "ENABLED (.calendar-app-wrapper)"
  }`
);

const plugins = {
  tailwindcss: {},
  autoprefixer: {},
};

// Applica il prefix selector SOLO in modalità compilata (build)
if (!isStandalone) {
  plugins["postcss-prefix-selector"] = {
    prefix: "",
    transform: function (prefix, selector, prefixedSelector) {
      // Trasforma :root in .calendar-app-wrapper
      // if (selector === ":root") {
      //   return ".calendar-app-wrapper";
      // }

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
  };
}

export default {
  plugins,
};

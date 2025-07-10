export default {
  plugins: {
    tailwindcss: {},
    autoprefixer: {},
    "postcss-prefix-selector": {
      prefix: ".calendar-app-wrapper",
      exclude: [":root", ":host", "html", "body"],
    },
  },
};

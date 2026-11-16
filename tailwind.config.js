/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./resources/site/index.html", "./public/site.js"],
  theme: {
    extend: {
      colors: {
        ivory: "#F7F4F1",
        sand: "#E7E1DA",
        stone: "#D6CFC7",
        sage: "#A7A69A",
        ink: "#2E2E2E",
        blush: "#F2D7D0",
        paper: "#FFFCFA",
        muted: "#6A655F",
        banner: "#F6EBE6",
      },
      fontFamily: {
        sans: ["Inter", "ui-sans-serif", "system-ui", "sans-serif"],
        display: ["Playfair Display", "Georgia", "ui-serif", "serif"],
      },
      boxShadow: {
        sheet: "0 24px 60px -36px rgba(46, 46, 46, 0.28)",
      },
    },
  },
  plugins: [],
};

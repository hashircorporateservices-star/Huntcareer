import type { Config } from "tailwindcss";

/**
 * HuntCareer design tokens. Deliberately not JobCopilot's violet:
 * a deep pine-green brand on warm stone neutrals, with a mono face for figures.
 */
const config: Config = {
  content: ["./src/**/*.{ts,tsx}"],
  theme: {
    extend: {
      colors: {
        // Pine — the brand. Used generously (buttons, links, active, positive).
        brand: {
          50: "#eef6f1",
          100: "#d5e9dd",
          200: "#abd3bb",
          300: "#79b795",
          400: "#4a9a71",
          500: "#2f7d57",
          600: "#1f6344", // primary
          700: "#1a4f38",
          800: "#163f2e",
          900: "#123324",
        },
      },
      fontFamily: {
        // Display: characterful grotesque, used with restraint on headings.
        display: ["'Space Grotesk'", "system-ui", "sans-serif"],
        // Body: clean, legible.
        sans: ["'Inter'", "system-ui", "sans-serif"],
        // Utility: every figure (match %, counts, salaries) renders in mono — the signature.
        mono: ["'IBM Plex Mono'", "ui-monospace", "monospace"],
      },
      borderRadius: {
        xl: "0.9rem",
        "2xl": "1.25rem",
      },
    },
  },
  plugins: [],
};

export default config;

import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";
import flowbite from "flowbite/plugin";
import typography from "@tailwindcss/typography";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./vendor/laravel/jetstream/**/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
        "node_modules/flowbite-vue/**/*.{js,jsx,ts,tsx,vue}",
        "node_modules/flowbite/**/*.{js,jsx,ts,tsx}",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                vet: {
                    bg: "var(--color-background)",
                    surface: "var(--color-surface)",
                    text: "var(--color-text-base)",
                    muted: "var(--color-text-muted)",
                    primary: "var(--color-primary)",
                    "primary-hover": "var(--color-primary-hover)",
                    border: "var(--color-border)",
                },
            },
        },
    },

    plugins: [forms, typography, flowbite],
};

import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";
import typography from "@tailwindcss/typography";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./vendor/laravel/jetstream/**/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./index.html",
        "./src/**/*.{js,ts,jsx,tsx}",
        "./node_modules/flowbite/**/*.js",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Poppins", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                matisse: {
                    50: "#f3f7fc",
                    100: "#e6f0f8",
                    200: "#c7dff0",
                    300: "#95c4e4",
                    400: "#5ca7d4",
                    500: "#378bc0",
                    600: "#246694",
                    700: "#215983",
                    800: "#1f4c6d",
                    900: "#1f415b",
                    950: "#142a3d",
                },
                gray: {
                    50: "#f6f6f6",
                    100: "#e7e7e7",
                    200: "#d1d1d1",
                    300: "#b0b0b0",
                    400: "#808080",
                    500: "#6d6d6d",
                    600: "#5d5d5d",
                    700: "#4f4f4f",
                    800: "#454545",
                    900: "#3d3d3d",
                    950: "#262626",
                },
                black: {
                    DEFAULT: "#212121",
                    full : "000000",
                },
                white: {
                    DEFAULT: "#FAFAFA",
                    full : "#FFFFFF",
                },
            },
        },
    },

    plugins: [forms, typography],
};

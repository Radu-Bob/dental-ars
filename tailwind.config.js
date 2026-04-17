/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  safelist: [
    'bg-red-500', 'bg-white',
    'text-white', 'text-gray-800',
    'shadow-lg', 'shadow-md', 'shadow-red-300',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
};


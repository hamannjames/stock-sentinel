const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    purge: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
            },
            minHeight: {
                'screen-20': '20vh',
                'screen-25': '25vh',
                'screen-30': '30vh',
                'screen-35': '35vh',
                'screen-40': '40vh',
            },
            colors: {
                primary: '#086aa3',
                'primary-dark': '#064970',
                'primary-light': '#3E88B3',
                accent: '#f0c224',
                'accent-dark': '#BD971C',
                'accent-light': '#F2D36D',
                secondary: '#F05D24',
                'secondary-dark': '#BD4A1C',
                'secondary-light': '#F2936D',
                'grey-dark': '#333333',
                'grey-medium': '#576159',
                'grey-light': '#d3d3d3',
                action: '#D65320',
                'action-dark': '#A34018',
                'action-light': '#DD8663',
                'D': '#00b',
                'R': '#cc0000',
                'ID': '#ff4500'
            },
            rotate: {
                '30': '30deg',
                '-30': '-30deg',
                '70': '70deg',
                '-70': '-70deg'
            },
            inset: {
                '-2px': '-2px'
            }
        },
    },

    variants: {
        extend: {
            opacity: [
                'disabled',
                'before',
                'after'
            ],
            position: [
                'before',
                'after'
            ],
            inset: [
                'before',
                'after'
            ],
            transform: [
                'before',
                'after'
            ],
            translate: [
                'before',
                'after'
            ],
            transitionProperty: [
                'before',
                'after'
            ],
            transitionDuration: [
                'before',
                'after'
            ],
            textColor: [
                'before',
                'after'
            ]
        },
    },

    plugins: [
        require('@tailwindcss/forms'),
        require('tailwindcss-pseudo-elements')
    ],
};

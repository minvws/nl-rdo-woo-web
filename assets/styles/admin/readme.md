# Styling the admin app

- Since we use `Tailwind`, we write our styles in css files. We no longer need to use Sass.
- Our components are prefixed with `.woo-`, so for example `.woo-button`
- We make our selectors as short as possible (no indenting). Check [this post](https://csswizardry.com/2012/05/keep-your-css-selectors-short/)
- We use [BEM notation](https://getbem.com/introduction/)
  - Block: `.woo-button`
  - Element: `.woo-button__icon` (a child of `.woo-button`)
  - Modifier: `.woo-button--primary` (a variation of `.woo-button`)

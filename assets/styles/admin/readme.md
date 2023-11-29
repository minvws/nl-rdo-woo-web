# Styling the admin app

- Since we use `Tailwind`, we write our styles in css files. We no longer need to use Sass.
- Our components are prefixed with `.bhr-`, so for example `.bhr-button`
- We make our selectors as short as possible (no indenting). Check [this post](https://csswizardry.com/2012/05/keep-your-css-selectors-short/)
- We use [BEM notation](https://getbem.com/introduction/)
  - Block: `.bhr-button`
  - Element: `.bhr-button__icon` (a child of `.bhr-button`)
  - Modifier: `.bhr-button--primary` (a variation of `.bhr-button`)

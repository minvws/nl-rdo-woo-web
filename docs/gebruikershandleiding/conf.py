# Configuration file for the Sphinx documentation builder.
#
# For the full list of built-in configuration values, see the documentation:
# https://www.sphinx-doc.org/en/master/usage/configuration.html

# -- Project information -----------------------------------------------------
# https://www.sphinx-doc.org/en/master/usage/configuration.html#project-information

project = 'Woo publicatieplatform'
author = 'The Woo team'
#release = "2.0.0"

# -- General configuration ---------------------------------------------------
# https://www.sphinx-doc.org/en/master/usage/configuration.html#general-configuration

extensions = ['myst_parser']

templates_path = ['_templates']
exclude_patterns = ['_build', 'Thumbs.db', '.DS_Store']

language = 'nl'

# -- Options for HTML output -------------------------------------------------
# https://www.sphinx-doc.org/en/master/usage/configuration.html#options-for-html-output

html_theme = "sphinx-rijkshuisstijl-2008"
html_theme_path = ["."]
html_theme_options = {
    "logo": "/documentatie/_static/img/ro-logo.svg",
    "home_url": "/documentatie",
    "show_copyright_privacy_block_footer": "false",
    "copyright_url": "/documentatie/copyright.html",
    "privacy_url": "https://irealisatie.nl/privacy"
}

locale_dirs = ['locales/']

myst_enable_extensions = [
    'deflist',
    'colon_fence',
]

html_static_path = ["_static"]
html_css_files = ["custom.css"]

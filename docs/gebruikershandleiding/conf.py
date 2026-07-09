# Configuration file for the Sphinx documentation builder.
#
# For the full list of built-in configuration values, see the documentation:
# https://www.sphinx-doc.org/en/master/usage/configuration.html

# -- Project information -----------------------------------------------------
# https://www.sphinx-doc.org/en/master/usage/configuration.html#project-information

project = 'Woo Publicatieplatform'
author = 'The Woo team'
#release = "2.0.0"

# -- General configuration ---------------------------------------------------
# https://www.sphinx-doc.org/en/master/usage/configuration.html#general-configuration

extensions = ['myst_parser', "sphinx.ext.extlinks"]

templates_path = ['_templates']
exclude_patterns = ['_build', 'Thumbs.db', '.DS_Store']

language = 'nl'

# -- Options for HTML output -------------------------------------------------
# https://www.sphinx-doc.org/en/master/usage/configuration.html#options-for-html-output

html_theme = "sphinx_rijkshuisstijl_2008"
html_theme_options = {
    "logo": "/documentatie/_static/img/ro-logo.svg",
    "logo_text": "", # tenant specific
    "slogan": "Woo Publicatieplatform",
    "meta_footer": "",
    "home_url": "/documentatie",
    "show_copyright_privacy_block_footer": "false",
    "copyright_url": "/documentatie/copyright.html",
    "privacy_url": "https://irealisatie.nl/privacy",
    "show_relbar_bottom": "true",
    "show_related": "true"
}

locale_dirs = ['locales/']

myst_enable_extensions = [
    'substitution',
    'deflist',
    'colon_fence',
]

html_static_path = ["_static"]
html_css_files = ["custom.css"]

# extlinks configuration
extlinks = {}
extlinks_detect_hardcoded_links = True

# -- Tenant-specific configuration -------------------------------------------
import importlib.util
import os
import pathlib

_tenant = os.environ.get("SPHINX_TENANT", "minvws")
_tenant_path = pathlib.Path(__file__).parent / "tenants" / f"{_tenant}.py"
if not _tenant_path.exists():
    raise RuntimeError(f"Unknown tenant '{_tenant}' — no config file at {_tenant_path}")

_spec = importlib.util.spec_from_file_location("tenant_conf", _tenant_path)
_mod = importlib.util.module_from_spec(_spec)
_spec.loader.exec_module(_mod)

# {**a, **b} merges two dicts; keys in `b` (tenant) override keys in `a` (base).
# Tenant files only need to declare the keys they want to change.
extlinks           = {**extlinks,           **getattr(_mod, "extlinks", {})}
html_theme_options = {**html_theme_options, **getattr(_mod, "html_theme_options", {})}
myst_substitutions = getattr(_mod, "myst_substitutions", {})

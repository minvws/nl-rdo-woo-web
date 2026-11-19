_portal_name = "open.minienw.nl"
_balie_name = "balie.open.minienw.nl"

myst_substitutions = {
    "portal_name": f"{_portal_name}",
    "portal_url": f"https://{_portal_name}",
    "portal_link": f"[{_portal_name}](https://{_portal_name})",
    "balie_url": f"https://{_balie_name}/balie",
    "balie_link": f"[https://{_balie_name}/balie](https://{_balie_name}/balie)",
}

html_theme_options = {
    "logo_text": "Ministerie van Infrastructuur en Waterstaat",
}

extlinks = {
    "public": (f"{myst_substitutions['portal_url']}/%s", f"{_portal_name}/%s"),
    "balie": (f"{myst_substitutions['balie_url']}/%s", f"{_balie_name}/balie/%s"),
}
